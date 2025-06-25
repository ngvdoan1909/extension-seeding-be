<?php
namespace App\Services;

use App\Models\Commission;
use App\Models\Worker;

class WorkerService
{
    protected $worker;
    protected $commission;

    public function __construct(
        Worker $worker,
        Commission $commission,
    ) {
        $this->worker = $worker;
        $this->commission = $commission;
    }

    // lấy nhiệm vụ cho người dùng:
    // điều kiện pass: chưa bị ng dùng làm trong ngày, chưa đủ lượt daily_limit (lưojt truy cập cần), có lượt hoàn thành ít nhất trước (daily_completed)
    public function startWorker(array $data = [])
    {
        $imageData = [];
        $bucket = env('MINIO_BUCKET', 'extension-seeding');
        $userId = $data['user_id'];
        $ip = $data['ip'];

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
            // ->select('commission_id', 'key_word_image')
            ->orderBy('daily_completed', 'asc')
            ->first();
        // dd($commission);

        if (!$commission) {
            throw new \Exception('Không còn nhiệm vụ');
        }

        $infoFake = getRandomFakeInfo();
        // dd($infoFake);

        $worker = $this->worker->create([
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
            $imageData[] = \Storage::disk('minio')->url($bucket . '/' . $img['image']);
        }
        // dd($imageData);

        $imagesUserInfo = generateTextImage(
            [
                'SĐT: ' . $infoFake['phone'],
                'Tên: ' . $infoFake['name'],
            ],
            'fake',
            'public',
        );

        // dd($imagesUserInfo);

        $data = [
            'keyWordImage' => \Storage::disk('minio')->url($bucket . '/' . $commission->key_word_image),
            'imageData' => $imageData,
            'imagesUserInfo' => \Storage::disk('public')->url($imagesUserInfo)
        ];

        return $data;
    }
}