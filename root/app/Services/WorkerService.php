<?php
namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionUrl;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerSession;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class WorkerService
{
    protected $deposit;
    protected $user;
    protected $worker;
    protected $commission;
    protected $commissionUrl;
    protected $workerSession;

    const PATH_FAKE = 'fake';
    const AMOUNT = 2000;
    const LIMIT_TRY = 3;

    public function __construct(
        Deposit $deposit,
        User $user,
        Worker $worker,
        Commission $commission,
        CommissionUrl $commissionUrl,
        WorkerSession $workerSession,
    ) {
        $this->deposit = $deposit;
        $this->user = $user;
        $this->worker = $worker;
        $this->commission = $commission;
        $this->commissionUrl = $commissionUrl;
        $this->workerSession = $workerSession;
    }

    public function startWorker(array $data = [])
    {
        $imageData = [];
        $userId = $data['user_id'];
        $ip = $data['ip'];
        $numberRand = 2;
        // $numberRand = random_int(3, 5);

        $commission = $this->commission
            ->whereColumn('daily_completed', '<', 'daily_limit')
            ->whereDoesntHave('workers', function ($query) use ($userId, $ip) {
                $query->whereDate('executed_at', now()->toDateString())
                    ->where(function ($q) use ($userId, $ip) {
                        $q->where('user_id', $userId)
                            ->orWhere('ip', $ip);
                    });
            })
            ->with([
                'urls' => function ($query) {
                    $query->with('images');
                }
            ])
            ->orderBy('daily_completed', 'asc')
            ->first();

        if (!$commission) {
            throw new \Exception('Không còn nhiệm vụ', Response::HTTP_NOT_FOUND);
        }

        $randomUrl = $commission->urls->random();
        $infoFake = getRandomFakeInfo();

        $work = $this->worker->create([
            'worker_id' => \Str::uuid(),
            'user_id' => $userId,
            'user_name' => $infoFake['name'],
            'user_phone' => $infoFake['phone'],
            'commission_id' => $commission->commission_id,
            'commission_url_id' => $randomUrl->id,
            'ip' => $ip,
            'executed_at' => now()->toDateString(),
            'is_completed' => false,
        ]);

        $cacheLimit = $work->worker_id . '_' . $userId;

        Cache::put('limit_try' . $cacheLimit, self::LIMIT_TRY, now()->addMinutes(10));
        Cache::put('worker_limit_' . $work->worker_id, $numberRand, now()->addMinutes(10));

        $cacheUrlsKey = "worker_urls_" . $work->worker_id . $ip;
        Cache::put($cacheUrlsKey, $commission->urls->keyBy('id')->toArray(), now()->addMinutes(10));

        $cacheCurrentUrlKey = "current_url_" . $work->worker_id . $ip;
        Cache::put($cacheCurrentUrlKey, $randomUrl->id, now()->addMinutes(10));

        foreach ($randomUrl->images as $img) {
            $imageData[] = \Storage::disk('minio')->url($img->image);
        }

        $imagesUserInfo = generateTextImage(
            ['SĐT: ' . $infoFake['phone'], 'Tên: ' . $infoFake['name']],
            self::PATH_FAKE,
            'minio',
        );

        return [
            'worker_id' => $work->worker_id,
            'keyWordImage' => \Storage::disk('minio')->url($randomUrl->key_word_image),
            'imageData' => $imageData,
            'imagesUserInfo' => \Storage::disk('minio')->url($imagesUserInfo),
            'numberRand' => $numberRand,
            'url' => $randomUrl->url
        ];
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

    public function checkPhone($data)
    {
        $phone = $data['user_phone'];
        $ip = $data['ip'];
        $cacheWaitTimeKey = $phone . '_' . $ip;

        $workerUser = $this->worker->select('user_phone', 'ip', 'is_completed')
            ->where('user_phone', $phone)
            ->where('ip', $ip)
            ->where('is_completed', false)
            ->first();

        if (!$workerUser) {
            throw new \Exception('Số điện thoại không đúng', Response::HTTP_PRECONDITION_FAILED);
        }

        if (Cache::has($cacheWaitTimeKey)) {
            $expireAt = Cache::get($cacheWaitTimeKey);
            $remaining = $expireAt - now()->timestamp;

            return [
                'timeOut' => max(0, $remaining)
            ];
        }

        $lastThree = intval(substr($phone, -3));
        // $waitTime = ($lastThree >= 10 && $lastThree <= 70) ? $lastThree : rand(20, 80);
        $waitTime = 10;

        $expiredAt = now()->addSeconds($waitTime)->timestamp;

        Cache::put($cacheWaitTimeKey, $expiredAt, now()->addMinutes(10));

        return [
            'timeOut' => $waitTime
        ];
    }

    public function getCode(array $data = [])
    {
        $phone = $data['user_phone'];
        $ip = $data['ip'];
        $url = $data['url'];
        $cacheWaitTimeKey = $phone . '_' . $ip;

        $workerUser = $this->worker->select('worker_id', 'user_phone', 'ip', 'is_completed')
            ->where('user_phone', $phone)
            ->where('ip', $ip)
            ->where('is_completed', false)
            ->first();

        if (!$workerUser) {
            throw new \Exception('Số điện thoại không đúng', Response::HTTP_PRECONDITION_FAILED);
        }

        $cacheCurrentUrlKey = "current_url_" . $workerUser->worker_id . $ip;
        $currentUrlId = Cache::get($cacheCurrentUrlKey);

        $cacheUrlsKey = "worker_urls_" . $workerUser->worker_id . $ip;
        $cachedUrls = Cache::get($cacheUrlsKey, []);

        $currentUrl = $cachedUrls[$currentUrlId] ?? null;

        if (!$currentUrl || $currentUrl['url'] !== $url) {
            throw new \Exception('URL không hợp lệ', Response::HTTP_PRECONDITION_FAILED);
        }

        if (!Cache::has($cacheWaitTimeKey)) {
            throw new \Exception(
                'Bạn cần xác minh số điện thoại',
                Response::HTTP_PRECONDITION_REQUIRED
            );
        }

        $expireAt = Cache::get($cacheWaitTimeKey);
        $remaining = $expireAt - now()->timestamp;

        if ($remaining > 0) {
            throw new \Exception(
                'Vui lòng chờ ' . $remaining . ' giây trước khi nhận mã',
                Response::HTTP_TOO_EARLY
            );
        }

        Cache::forget($cacheWaitTimeKey);

        $cacheCodeKey = 'worker_code_' . $workerUser->worker_id;

        if (Cache::has($cacheCodeKey)) {
            return [
                'code' => Cache::get($cacheCodeKey)
            ];
        }

        $code = \Str::random(9);
        Cache::put($cacheCodeKey, $code, now()->addMinutes(10));

        return [
            'code' => $code
        ];
    }

    public function startWorkerSession(array $data = [])
    {
        $workerId = $data['worker_id'];
        $inputCode = $data['code'];
        $ip = $data['ip'];

        $worker = $this->worker->where('worker_id', $workerId)->first();

        if (!$worker) {
            throw new \Exception('Không tìm thấy nhiệm vụ', Response::HTTP_NOT_FOUND);
        }

        $cacheCodeKey = 'worker_code_' . $workerId;
        $cacheRepeatKey = 'worker_repeat_' . $workerId;
        $cacheLimitKey = 'worker_limit_' . $workerId;
        $cacheMatchCountKey = 'worker_match_count_' . $workerId;
        $cacheTryKey = 'limit_try' . $workerId . '_' . $worker->user_id;

        $cacheUrlsKey = "worker_urls_" . $workerId . $ip;
        $cacheCurrentUrlKey = "current_url_" . $workerId . $ip;
        $cacheUsedUrlsKey = "used_urls_{$workerId}";

        if (!Cache::has($cacheCodeKey) || !Cache::has($cacheLimitKey)) {
            throw new \Exception('Code hoặc giới hạn lượt đã hết hạn', Response::HTTP_BAD_REQUEST);
        }

        $cachedCode = Cache::get($cacheCodeKey);
        $repeatLimit = Cache::get($cacheLimitKey);
        $isMatched = $cachedCode === $inputCode;

        $matchCount = Cache::get($cacheMatchCountKey, 0);
        $currentRepeat = Cache::get($cacheRepeatKey, 0);
        $limitTry = Cache::get($cacheTryKey, self::LIMIT_TRY);

        if ($currentRepeat > $repeatLimit) {
            throw new \Exception('Đã vượt quá số lần nhập mã cho phép', Response::HTTP_FORBIDDEN);
        }

        if ($isMatched) {
            $matchCount++;
            $currentRepeat++;
            Cache::forget($cacheCodeKey);
            Cache::put($cacheMatchCountKey, $matchCount, now()->addMinutes(10));
        } else {
            $limitTry--;

            if ($limitTry <= 0) {
                $currentRepeat++;
                Cache::put($cacheRepeatKey, $currentRepeat, now()->addMinutes(10));
                $limitTry = self::LIMIT_TRY;
            }

            Cache::put($cacheTryKey, $limitTry, now()->addMinutes(10));
        }

        $this->workerSession->create([
            'worker_session_id' => \Str::uuid(),
            'worker_id' => $workerId,
            'code' => $cachedCode,
            'is_matched' => $isMatched
        ]);

        $worker->update(['is_completed' => true]);

        Cache::put($cacheRepeatKey, $currentRepeat, now()->addMinutes(10));

        $newUrlData = null;

        if (($isMatched || $limitTry <= 0) && $currentRepeat < $repeatLimit) {
            $usedUrls = Cache::get($cacheUsedUrlsKey, []);
            $cachedUrls = Cache::get($cacheUrlsKey, []);

            $availableUrls = array_filter($cachedUrls, function ($url) use ($usedUrls) {
                return !in_array($url['id'], $usedUrls);
            });

            if (!empty($availableUrls)) {
                $newUrl = $availableUrls[array_rand($availableUrls)];
                $newUrlId = $newUrl['id'];

                $usedUrls[] = $newUrlId;
                Cache::put($cacheUsedUrlsKey, $usedUrls, now()->addMinutes(10));
                Cache::put($cacheCurrentUrlKey, $newUrlId, now()->addMinutes(10));

                $newCode = \Str::random(9);
                Cache::put($cacheCodeKey, $newCode, now()->addMinutes(10));

                $newWorkerId = \Str::uuid();
                $infoFake = getRandomFakeInfo();

                $newWorker = $this->worker->create([
                    'worker_id' => $newWorkerId,
                    'user_id' => $worker->user_id,
                    'user_name' => $infoFake['name'],
                    'user_phone' => $infoFake['phone'],
                    'commission_id' => $worker->commission_id,
                    'commission_url_id' => $newUrlId,
                    'ip' => $worker->ip,
                    'executed_at' => now()->toDateString(),
                    'is_completed' => false,
                ]);

                $newCacheLimit = $newWorkerId . '_' . $worker->user_id;
                Cache::put('limit_try' . $newCacheLimit, self::LIMIT_TRY, now()->addMinutes(10));
                Cache::put('worker_limit_' . $newWorkerId, 1, now()->addMinutes(10));

                Cache::put("worker_urls_" . $newWorkerId . $ip, $cachedUrls, now()->addMinutes(10));
                Cache::put("current_url_" . $newWorkerId . $ip, $newUrlId, now()->addMinutes(10));
                Cache::put("used_urls_{$newWorkerId}", $usedUrls, now()->addMinutes(10));

                $imagesUserInfo = generateTextImage(
                    ['SĐT: ' . $infoFake['phone'], 'Tên: ' . $infoFake['name']],
                    self::PATH_FAKE,
                    'minio',
                );

                $imageData = [];
                foreach ($newUrl['images'] as $img) {
                    $imageData[] = \Storage::disk('minio')->url($img['image']);
                }

                $newUrlData = [
                    'worker_id' => $newWorkerId,
                    'url' => $newUrl['url'],
                    'keyWordImage' => \Storage::disk('minio')->url($newUrl['key_word_image']),
                    'imageData' => $imageData,
                    'imagesUserInfo' => \Storage::disk('minio')->url($imagesUserInfo),
                ];
            }
        }

        if ($currentRepeat === $repeatLimit) {
            if ($matchCount === $repeatLimit) {

                $this->deposit->create([
                    'user_id' => $worker->user_id,
                    'id_transaction' => 'D_' . \Str::random(8),
                    'amount' => self::AMOUNT,
                    'from' => null,
                    'note' => 'done task'
                ]);
            }

            Cache::forget($cacheCodeKey);
            Cache::forget($cacheRepeatKey);
            Cache::forget($cacheLimitKey);
            Cache::forget($cacheMatchCountKey);
            Cache::forget($cacheTryKey);
            Cache::forget($cacheUrlsKey);
            Cache::forget($cacheCurrentUrlKey);
            Cache::forget($cacheUsedUrlsKey);
        }

        $user = $this->user->where('user_id', $worker->user_id)->first();

        return [
            'numberRandLeft' => max(0, $repeatLimit - $currentRepeat),
            'isMatched' => $isMatched,
            'isLastAttempt' => $currentRepeat === $repeatLimit,
            'totalPoint' => $user->getPointAttribute(),
            'limitTry' => $limitTry,
            'newUrl' => $newUrlData
        ];
    }
}