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
        \Log::info('startWorker started', [
            'user_id' => $data['user_id'],
            'ip' => $data['ip']
        ]);

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
            \Log::error('No commission available', ['user_id' => $userId]);
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

        \Log::info('Worker created', [
            'worker_id' => $work->worker_id,
            'user_id' => $userId,
            'commission_id' => $commission->commission_id
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

        \Log::info('Cache initialized', [
            'cache_limit_key' => $cacheLimitKey,
            'number_rand' => $numberRand,
            'cache_repeat_key' => $cacheRepeatKey,
            'cache_match_count_key' => $cacheMatchCountKey,
            'cache_try_key' => $cacheTryKey,
            'cache_urls_key' => $cacheUrlsKey,
            'cache_current_url_key' => $cacheCurrentUrlKey
        ]);

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

        \Log::info('startWorker response', $response);

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
        \Log::info('startWorkerSession started', [
            'worker_id' => $data['worker_id'],
            'input_code' => $data['code'],
            'ip' => $data['ip']
        ]);

        $workerId = $data['worker_id'];
        $inputCode = $data['code'];
        $ip = $data['ip'];

        $worker = $this->worker->where('worker_id', $workerId)->first();

        if (!$worker) {
            \Log::error('Worker not found', ['worker_id' => $workerId]);
            throw new \Exception('Không tìm thấy nhiệm vụ', Response::HTTP_NOT_FOUND);
        }

        \Log::info('Worker found', [
            'worker_id' => $workerId,
            'user_id' => $worker->user_id,
            'commission_id' => $worker->commission_id,
            'commission_url_id' => $worker->commission_url_id
        ]);

        $cacheCodeKey = 'worker_code_' . $workerId;
        $cacheRepeatKey = 'worker_repeat_' . $workerId;
        $cacheLimitKey = 'worker_limit_' . $workerId;
        $cacheMatchCountKey = 'worker_match_count_' . $workerId;
        $cacheTryKey = 'limit_try_' . $workerId;
        $cacheUrlsKey = "worker_urls_" . $workerId . $ip;
        $cacheUsedUrlsKey = "used_urls_" . $workerId;

        if (!Cache::has($cacheCodeKey)) {
            \Log::error('Code expired', ['cache_code_key' => $cacheCodeKey]);
            throw new \Exception('Code đã hết hạn', Response::HTTP_BAD_REQUEST);
        }

        $cachedCode = Cache::get($cacheCodeKey);
        $repeatLimit = Cache::get($cacheLimitKey, 0);
        $isMatched = $cachedCode === $inputCode;
        $matchCount = Cache::get($cacheMatchCountKey, 0);
        $currentRepeat = Cache::get($cacheRepeatKey, 0);
        $limitTry = Cache::get($cacheTryKey, self::LIMIT_TRY);

        \Log::info('Initial cache values', [
            'cached_code' => $cachedCode,
            'repeat_limit' => $repeatLimit,
            'current_repeat' => $currentRepeat,
            'match_count' => $matchCount,
            'limit_try' => $limitTry,
            'is_matched' => $isMatched
        ]);

        if ($currentRepeat >= $repeatLimit) {
            \Log::error('Exceeded repeat limit', [
                'current_repeat' => $currentRepeat,
                'repeat_limit' => $repeatLimit
            ]);
            throw new \Exception('Đã vượt quá số lần nhập mã cho phép', Response::HTTP_FORBIDDEN);
        }

        // Lưu session
        $this->workerSession->create([
            'worker_session_id' => \Str::uuid(),
            'worker_id' => $workerId,
            'code' => $cachedCode,
            'is_matched' => $isMatched
        ]);

        \Log::info('Worker session created', [
            'worker_id' => $workerId,
            'is_matched' => $isMatched
        ]);

        // Cập nhật trạng thái worker
        $worker->update(['is_completed' => true]);
        \Log::info('Worker status updated', ['worker_id' => $workerId, 'is_completed' => true]);

        // Xử lý logic lặp
        $checkNotMatch = $currentRepeat;
        $shouldCreateNewUrl = $isMatched || $limitTry <= 1; // Sửa đổi: dùng <= 1 để bắt đầu tạo khi còn 1 lượt

        if ($isMatched) {
            $matchCount++;
            Cache::put($cacheMatchCountKey, $matchCount, now()->addMinutes(10));
            \Log::info('Code matched, incremented matchCount', [
                'match_count' => $matchCount,
                'cache_match_count_key' => $cacheMatchCountKey
            ]);
        } else {
            $limitTry--;
            \Log::info('Code not matched, decremented limitTry', [
                'limit_try' => $limitTry,
                'cache_try_key' => $cacheTryKey
            ]);
            Cache::put($cacheTryKey, $limitTry, now()->addMinutes(10));
        }

        // Tăng currentRepeat nếu mã khớp hoặc hết lượt thử
        if ($shouldCreateNewUrl) {
            $checkNotMatch = $currentRepeat + 1;
            Cache::put($cacheRepeatKey, $checkNotMatch, now()->addMinutes(10));
            \Log::info('Incremented currentRepeat', [
                'current_repeat' => $checkNotMatch,
                'cache_repeat_key' => $cacheRepeatKey
            ]);
        }

        // Đặt lại limitTry nếu hết lượt thử (sau khi kiểm tra tạo newUrl)
        if ($limitTry <= 0) {
            $limitTry = self::LIMIT_TRY;
            Cache::put($cacheTryKey, $limitTry, now()->addMinutes(10));
            \Log::info('Reset limitTry due to exhaustion', ['limit_try' => $limitTry]);
        }

        $newUrlData = null;

        // Tạo newUrl nếu chưa đạt repeatLimit và (mã khớp hoặc hết lượt thử)
        if ($checkNotMatch < $repeatLimit && $shouldCreateNewUrl) {
            \Log::info('Attempting to create new URL', [
                'current_repeat' => $checkNotMatch,
                'repeat_limit' => $repeatLimit
            ]);

            $cachedUrls = Cache::get($cacheUrlsKey, function () use ($worker) {
                $urls = $this->commissionUrl
                    ->where('commission_id', $worker->commission_id)
                    ->with('images')
                    ->get()
                    ->keyBy('id')
                    ->toArray();
                \Log::info('Fetched URLs from database', [
                    'commission_id' => $worker->commission_id,
                    'urls_count' => count($urls)
                ]);
                return $urls;
            });

            $usedUrls = Cache::get($cacheUsedUrlsKey, []);
            \Log::info('Retrieved used URLs', [
                'used_urls' => $usedUrls,
                'cache_used_urls_key' => $cacheUsedUrlsKey
            ]);

            $currentUrlId = (string) $worker->commission_url_id;
            $usedUrls[] = $currentUrlId;

            $usedUrls = array_filter($usedUrls, function ($value) {
                return is_string($value) || is_int($value);
            });

            $availableUrls = array_diff_key($cachedUrls, array_flip($usedUrls));
            \Log::info('Calculated available URLs', [
                'available_urls_count' => count($availableUrls),
                'used_urls' => $usedUrls
            ]);

            if (empty($availableUrls)) {
                $usedUrls = [];
                $availableUrls = $cachedUrls;
                \Log::info('Reset used URLs due to no available URLs', [
                    'available_urls_count' => count($availableUrls)
                ]);
            }

            if (!empty($availableUrls)) {
                $newUrlId = array_rand($availableUrls);
                $newUrlId = (string) $newUrlId;
                $newUrl = $availableUrls[$newUrlId];

                $usedUrls[] = $newUrlId;
                Cache::put($cacheUsedUrlsKey, $usedUrls, now()->addMinutes(10));
                \Log::info('Selected new URL and updated used URLs', [
                    'new_url_id' => $newUrlId,
                    'new_url' => $newUrl['url'],
                    'used_urls' => $usedUrls
                ]);

                $newCode = \Str::random(9);
                Cache::put($cacheCodeKey, $newCode, now()->addMinutes(10));
                \Log::info('Generated new code', [
                    'new_code' => $newCode,
                    'cache_code_key' => $cacheCodeKey
                ]);

                $newWorkerId = \Str::uuid();
                $infoFake = getRandomFakeInfo();
                \Log::info('Generated new worker ID and fake info', [
                    'new_worker_id' => $newWorkerId,
                    'fake_info' => $infoFake
                ]);

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
                \Log::info('Created new worker', [
                    'new_worker_id' => $newWorkerId,
                    'commission_url_id' => $newUrlId
                ]);

                // Chuyển giá trị currentRepeat và matchCount sang worker_id mới
                Cache::put('worker_repeat_' . $newWorkerId, $checkNotMatch, now()->addMinutes(10));
                Cache::put('worker_limit_' . $newWorkerId, $repeatLimit, now()->addMinutes(10));
                Cache::put('worker_match_count_' . $newWorkerId, $matchCount, now()->addMinutes(10));
                // Khởi tạo limitTry cho worker mới
                Cache::put('limit_try_' . $newWorkerId, self::LIMIT_TRY, now()->addMinutes(10));
                \Log::info('Transferred cache to new worker and initialized limitTry', [
                    'new_worker_id' => $newWorkerId,
                    'current_repeat' => $checkNotMatch,
                    'repeat_limit' => $repeatLimit,
                    'match_count' => $matchCount,
                    'limit_try' => self::LIMIT_TRY
                ]);

                $newCacheUrlsKey = "worker_urls_" . $newWorkerId . $ip;
                Cache::put($newCacheUrlsKey, $cachedUrls, now()->addMinutes(10));
                \Log::info('Cached URLs for new worker', [
                    'new_cache_urls_key' => $newCacheUrlsKey,
                    'urls_count' => count($cachedUrls)
                ]);

                $newCacheCurrentUrlKey = "current_url_" . $newWorkerId . $ip;
                Cache::put($newCacheCurrentUrlKey, $newUrlId, now()->addMinutes(10));
                \Log::info('Cached current URL for new worker', [
                    'new_cache_current_url_key' => $newCacheCurrentUrlKey,
                    'new_url_id' => $newUrlId
                ]);

                $imagesUserInfo = generateTextImage(
                    ['SĐT: ' . $infoFake['phone'], 'Tên: ' . $infoFake['name']],
                    self::PATH_FAKE,
                    'minio'
                );
                \Log::info('Generated user info image', [
                    'images_user_info' => $imagesUserInfo
                ]);

                $imageData = [];
                foreach ($newUrl['images'] as $img) {
                    $imageData[] = \Storage::disk('minio')->url($img['image']);
                }
                \Log::info('Generated image data', [
                    'image_data' => $imageData
                ]);

                $newUrlData = [
                    'worker_id' => $newWorkerId,
                    'url' => $newUrl['url'],
                    'keyWordImage' => \Storage::disk('minio')->url($newUrl['key_word_image']),
                    'imageData' => $imageData,
                    'imagesUserInfo' => \Storage::disk('minio')->url($imagesUserInfo),
                ];
                \Log::info('Created new URL data', [
                    'new_url_data' => $newUrlData
                ]);
            }
        }

        // Kiểm tra kết thúc
        if ($checkNotMatch === $repeatLimit) {
            \Log::info('Reached repeat limit, checking deposit', [
                'current_repeat' => $checkNotMatch,
                'repeat_limit' => $repeatLimit,
                'match_count' => $matchCount
            ]);

            if ($matchCount === $repeatLimit) {
                $this->deposit->create([
                    'user_id' => $worker->user_id,
                    'id_transaction' => 'D_' . \Str::random(8),
                    'amount' => self::AMOUNT,
                    'from' => null,
                    'note' => 'done task'
                ]);
                \Log::info('Deposit created', [
                    'user_id' => $worker->user_id,
                    'amount' => self::AMOUNT
                ]);
            }

            // Xóa cache của worker_id hiện tại
            Cache::forget($cacheCodeKey);
            Cache::forget($cacheRepeatKey);
            Cache::forget($cacheLimitKey);
            Cache::forget($cacheMatchCountKey);
            Cache::forget($cacheTryKey);
            Cache::forget($cacheUrlsKey);
            Cache::forget($cacheUsedUrlsKey);
            \Log::info('Cleared all cache keys', [
                'cache_keys' => [
                    $cacheCodeKey,
                    $cacheRepeatKey,
                    $cacheLimitKey,
                    $cacheMatchCountKey,
                    $cacheTryKey,
                    $cacheUrlsKey,
                    $cacheUsedUrlsKey
                ]
            ]);
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

        \Log::info('Returning response', $response);

        return $response;
    }
}