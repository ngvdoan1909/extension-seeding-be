<?php
namespace App\Services;

use App\Models\Commission;
use App\Models\Worker;
use App\Models\WorkerSession;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class WorkerService
{
    protected $worker;
    protected $commission;
    protected $workerSession;

    const PATH_FAKE = 'fake';

    public function __construct(
        Worker $worker,
        Commission $commission,
        WorkerSession $workerSession,
    ) {
        $this->worker = $worker;
        $this->commission = $commission;
        $this->workerSession = $workerSession;
    }

    // lấy nhiệm vụ cho người dùng:
    // điều kiện pass: chưa bị ng dùng làm trong ngày, chưa đủ lượt daily_limit (lưojt truy cập cần), có lượt hoàn thành ít nhất trước (daily_completed)
    public function startWorker(array $data = [])
    {
        $imageData = [];
        $userId = $data['user_id'];
        $ip = $data['ip'];
        $numberRand = rand(3, 5);

        // lấy nhiệm vụ
        $commission = $this->commission
            ->whereColumn('daily_completed', '<', 'daily_limit')
            ->whereDoesntHave('workers', function ($query) use ($userId, $ip) {
                $query->whereDate('executed_at', now()->toDateString())
                    ->where(function ($q) use ($userId, $ip) {
                        $q->where('user_id', $userId)
                            ->orWhere('ip', $ip);
                    });
            })
            ->with('images')
            ->orderBy('daily_completed', 'asc')
            ->first();
        // dd($commission);

        if (!$commission) {
            throw new \Exception('Không còn nhiệm vụ', Response::HTTP_NOT_FOUND);
        }

        $infoFake = getRandomFakeInfo();
        // dd($infoFake);

        $work = $this->worker->create([
            'worker_id' => \Str::uuid(),
            'user_id' => $userId,
            'user_name' => $infoFake['name'],
            'user_phone' => $infoFake['phone'],
            'commission_id' => $commission->commission_id,
            'ip' => $ip,
            'executed_at' => now()->toDateString(),
            'is_completed' => false,
        ]);

        $imageIntructions = $commission->images;

        foreach ($imageIntructions as $img) {
            $imageData[] = \Storage::disk('minio')->url($img['image']);
        }
        // dd($imageData);

        $imagesUserInfo = generateTextImage(
            [
                'SĐT: ' . $infoFake['phone'],
                'Tên: ' . $infoFake['name'],
            ],
            self::PATH_FAKE,
            'minio',
        );

        // dd($imagesUserInfo);

        $data = [
            'worker_id' => $work->worker_id,
            'keyWordImage' => \Storage::disk('minio')->url($commission->key_word_image),
            'imageData' => $imageData,
            'imagesUserInfo' => \Storage::disk('minio')->url($imagesUserInfo),
            'numberRand' => $numberRand
        ];

        return $data;
    }

    public function cancelWorker(string $id)
    {
        $worker = $this->worker->where('worker_id', $id)
            ->first();

        // dd($worker);

        if (!$worker) {
            throw new \Exception('Không tìm thấy nhiệm vụ', Response::HTTP_NOT_FOUND);
        }

        $worker->delete();

        return $worker;
    }

    // kiểm tra sđt từ trang seeding gửi lên
    public function checkPhone($data)
    {
        $phone = $data['user_phone'];
        $ip = $data['ip'];

        $workerUser = $this->worker->select('user_phone', 'ip', 'is_completed')
            ->where('user_phone', $phone)
            ->where('ip', $ip)
            ->where('is_completed', false)
            ->first();

        if (!$workerUser) {
            throw new \Exception('Có vẻ sai', Response::HTTP_PRECONDITION_FAILED);
        }

        $lastThree = intval(substr($phone, -3));
        $cacheKey = $phone . '_' . $ip;

        if (Cache::has($cacheKey)) {
            return [
                'timeOut' => Cache::get($cacheKey)
            ];
        }

        $waitTime = ($lastThree >= 10 && $lastThree <= 70)
            ? $lastThree
            : rand(20, 80);

        Cache::put($cacheKey, $waitTime, now()->addSeconds($waitTime + 5));

        return [
            'timeOut' => $waitTime
        ];
    }

    // kiểm tra và trả về code
    public function getCode(array $data = [])
    {
        $phone = $data['user_phone'];
        $ip = $data['ip'];

        $workerUser = $this->worker->select('user_phone', 'ip', 'is_completed')
            ->where('user_phone', $phone)
            ->where('ip', $ip)
            ->where('is_completed', false)
            ->first();

        if (!$workerUser) {
            throw new \Exception('Có vẻ sai', Response::HTTP_PRECONDITION_FAILED);
        }

        $cacheKey = $phone . '_' . $ip;

        if (Cache::has($cacheKey)) {
            Cache::forget($cacheKey);
        }

        $code = \Str::random(9);

        return [
            'code' => $code
        ];
    }

    public function startWorkerSession(array $data = [])
    {

    }
}
