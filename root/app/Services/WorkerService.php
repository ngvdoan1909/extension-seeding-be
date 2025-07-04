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
        $numberRand = random_int(3, 5);

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

        $cacheLimitKey = 'worker_limit_' . $work->worker_id;
        $cacheRepeatKey = 'worker_repeat_' . $work->worker_id;
        $cacheMatchCountKey = 'worker_match_count_' . $work->worker_id;
        $cacheTryKey = 'limit_try_' . $work->worker_id;
        $cacheUrlsKey = "worker_urls_" . $work->worker_id . $ip;
        $cacheCurrentUrlKey = "current_url_" . $work->worker_id . $ip;

        Cache::put($cacheLimitKey, $numberRand, now()->addMinutes(10));
        Cache::put($cacheRepeatKey, 0, now()->addMinutes(10));
        Cache::put($cacheMatchCountKey, 0, now()->addMinutes(10));
        Cache::put($cacheTryKey, self::LIMIT_TRY, now()->addMinutes(10));
        Cache::put($cacheUrlsKey, $commission->urls->keyBy('id')->toArray(), now()->addMinutes(10));
        Cache::put($cacheCurrentUrlKey, $randomUrl->id, now()->addMinutes(10));

        foreach ($randomUrl->images as $img) {
            $imageData[] = \Storage::disk('minio')->url($img->image);
        }

        $imagesUserInfo = generateTextImage(
            ['SĐT: ' . $infoFake['phone'], 'Tên: ' . $infoFake['name']],
            self::PATH_FAKE,
            'minio'
        );

        $response = [
            'worker_id' => $work->worker_id,
            'keyWordImage' => \Storage::disk('minio')->url($randomUrl->key_word_image),
            'imageData' => $imageData,
            'imagesUserInfo' => \Storage::disk('minio')->url($imagesUserInfo),
            'numberRand' => $numberRand,
            'url' => $randomUrl->url
        ];

        return $response;
    }

    public function cancelWorker(string $id)
    {
        $worker = $this->worker->where('worker_id', $id)
            ->first();

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
        $cacheTryKey = 'limit_try_' . $workerId;
        $cacheUrlsKey = "worker_urls_" . $workerId . $ip;
        $cacheUsedUrlsKey = "used_urls_" . $workerId;

        if (!Cache::has($cacheCodeKey)) {
            throw new \Exception('Code đã hết hạn', Response::HTTP_BAD_REQUEST);
        }

        $cachedCode = Cache::get($cacheCodeKey);
        $repeatLimit = Cache::get($cacheLimitKey, 0);
        $isMatched = $cachedCode === $inputCode;
        $matchCount = Cache::get($cacheMatchCountKey, 0);
        $currentRepeat = Cache::get($cacheRepeatKey, 0);
        $limitTry = Cache::get($cacheTryKey, self::LIMIT_TRY);

        if ($currentRepeat >= $repeatLimit) {
            throw new \Exception('Đã vượt quá số lần nhập mã cho phép', Response::HTTP_FORBIDDEN);
        }

        $this->workerSession->create([
            'worker_session_id' => \Str::uuid(),
            'worker_id' => $workerId,
            'code' => $cachedCode,
            'is_matched' => $isMatched
        ]);

        $worker->update(['is_completed' => true]);

        $checkNotMatch = $currentRepeat;
        $shouldCreateNewUrl = $isMatched || $limitTry <= 1;

        if ($isMatched) {
            $matchCount++;
            Cache::put($cacheMatchCountKey, $matchCount, now()->addMinutes(10));
        } else {
            $limitTry--;
            Cache::put($cacheTryKey, $limitTry, now()->addMinutes(10));
        }

        if ($shouldCreateNewUrl) {
            $checkNotMatch = $currentRepeat + 1;
            Cache::put($cacheRepeatKey, $checkNotMatch, now()->addMinutes(10));
        }

        if ($limitTry <= 0) {
            $limitTry = self::LIMIT_TRY;
            Cache::put($cacheTryKey, $limitTry, now()->addMinutes(10));
        }

        $newUrlData = null;

        if ($checkNotMatch < $repeatLimit && $shouldCreateNewUrl) {

            $cachedUrls = Cache::get($cacheUrlsKey, function () use ($worker) {
                $urls = $this->commissionUrl
                    ->where('commission_id', $worker->commission_id)
                    ->with('images')
                    ->get()
                    ->keyBy('id')
                    ->toArray();

                return $urls;
            });

            $usedUrls = Cache::get($cacheUsedUrlsKey, []);

            $currentUrlId = (string) $worker->commission_url_id;
            $usedUrls[] = $currentUrlId;

            $usedUrls = array_filter($usedUrls, function ($value) {
                return is_string($value) || is_int($value);
            });

            $availableUrls = array_diff_key($cachedUrls, array_flip($usedUrls));

            if (empty($availableUrls)) {
                $usedUrls = [];
                $availableUrls = $cachedUrls;
            }

            if (!empty($availableUrls)) {
                $newUrlId = array_rand($availableUrls);
                $newUrlId = (string) $newUrlId;
                $newUrl = $availableUrls[$newUrlId];

                $usedUrls[] = $newUrlId;
                Cache::put($cacheUsedUrlsKey, $usedUrls, now()->addMinutes(10));

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

                Cache::put('worker_repeat_' . $newWorkerId, $checkNotMatch, now()->addMinutes(10));
                Cache::put('worker_limit_' . $newWorkerId, $repeatLimit, now()->addMinutes(10));
                Cache::put('worker_match_count_' . $newWorkerId, $matchCount, now()->addMinutes(10));
                Cache::put('limit_try_' . $newWorkerId, self::LIMIT_TRY, now()->addMinutes(10));

                $newCacheUrlsKey = "worker_urls_" . $newWorkerId . $ip;
                Cache::put($newCacheUrlsKey, $cachedUrls, now()->addMinutes(10));

                $newCacheCurrentUrlKey = "current_url_" . $newWorkerId . $ip;
                Cache::put($newCacheCurrentUrlKey, $newUrlId, now()->addMinutes(10));

                $imagesUserInfo = generateTextImage(
                    ['SĐT: ' . $infoFake['phone'], 'Tên: ' . $infoFake['name']],
                    self::PATH_FAKE,
                    'minio'
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

        if ($checkNotMatch === $repeatLimit) {
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
            Cache::forget($cacheUsedUrlsKey);
        }

        $user = $this->user->where('user_id', $worker->user_id)->first();

        $response = [
            'numberRandLeft' => max(0, $repeatLimit - $checkNotMatch),
            'isMatched' => $isMatched,
            'isLastAttempt' => $checkNotMatch === $repeatLimit,
            'totalPoint' => $user ? $user->getPointAttribute() : 0,
            'limitTry' => $limitTry,
            'newUrl' => $newUrlData
        ];

        return $response;
    }
}