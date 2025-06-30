<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRequest;
use App\Models\Commission;
use App\Models\InstructionImage;
use App\Models\Website;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    protected $website;
    protected $commission;
    protected $instructionImage;

    const PATH_VIEW = 'admin.pages.commissions.';
    const PATH_UPLOAD_COMMISSION = 'commission';
    const PATH_UPLOAD_INSTRCUTION = 'instruction';

    public function __construct(
        Website $website,
        Commission $commission,
        InstructionImage $instructionImage
    ) {
        $this->website = $website;
        $this->commission = $commission;
        $this->instructionImage = $instructionImage;
    }

    public function index()
    {
        try {
            $data = $this->commission->with(['images', 'website'])
                ->select('id', 'website_id', 'commission_id', 'key_word', 'key_word_image', 'url', 'daily_limit', 'daily_completed')
                ->latest('id')
                ->get();

            return view(self::PATH_VIEW . __FUNCTION__, compact('data'));
        } catch (\Exception $e) {
            // dd($e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function show(string $id)
    {
        try {
            $commission = $this->commission->where('commission_id', $id)->first();
            // dd($commission);

            return view(self::PATH_VIEW . __FUNCTION__, compact('commission'));

        } catch (\Exception $e) {
            dd($e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $websites = $this->website->select('website_id', 'name')->get();
        return view(self::PATH_VIEW . __FUNCTION__, compact('websites'));
    }

    public function store(CommissionRequest $request)
    {
        DB::beginTransaction();
        try {
            $website_id = $request->input('website_id');
            $commissionsData = $request->input('commissions');
            $createdCommissions = [];

            foreach ($commissionsData as $data) {
                $commission = $this->commission->create([
                    'website_id' => $website_id,
                    'commission_id' => \Str::uuid(),
                    'key_word' => $data['key_word'],
                    'url' => $data['url'],
                    'daily_limit' => $data['daily_limit'],
                    'daily_completed' => 0
                ]);

                $images = $data['image'] ?? [];
                if (!empty($images) && !is_array($images)) {
                    $images = [$images];
                }

                foreach ($images as $image) {
                    $path = \Storage::disk('minio')->put(
                        self::PATH_UPLOAD_INSTRCUTION,
                        $image
                    );

                    $this->instructionImage->create([
                        'commission_id' => $commission->commission_id,
                        'image' => $path
                    ]);
                }

                $keyWordImagePath = generateTextImage(
                    [$data['key_word']],
                    self::PATH_UPLOAD_COMMISSION,
                    'minio'
                );

                $commission->update(['key_word_image' => $keyWordImagePath]);

                $createdCommissions[] = $commission;
            }

            DB::commit();

            return redirect()->route('admin.commissions.index')
                ->with('success', 'Đã thêm ' . count($createdCommissions) . ' nhiệm vụ thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Commission create error: ' . $e->getMessage());

            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function update(CommissionRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $commission = $this->commission->where('commission_id', $id)->first();

            if (!$commission) {
                throw new \Exception("Không tìm thấy nhiệm vụ");
            }

            if (isset($data['image'])) {
                $instructionImages = $commission->images;
                foreach ($instructionImages as $image) {
                    try {
                        \Storage::disk('minio')->delete($image->image);
                        $image->delete();
                    } catch (\Exception $e) {
                        // dd($e->getMessage());
                    }
                }

                $images = is_array($data['image']) ? $data['image'] : [$data['image']];
                foreach ($images as $image) {
                    try {
                        $path = \Storage::disk('minio')->put(self::PATH_UPLOAD_INSTRCUTION, $image);
                        $this->instructionImage->create([
                            'commission_id' => $commission->commission_id,
                            'image' => $path
                        ]);
                    } catch (\Exception $e) {
                        // dd($e->getMessage());
                        continue;
                    }
                }
            }

            if (isset($data['key_word']) && $data['key_word'] !== $commission->key_word) {
                try {
                    if ($commission->key_word_image) {
                        \Storage::disk('minio')->delete($commission->key_word_image);
                    }

                    $keyWordImagePath = generateTextImage(
                        [$data['key_word']],
                        self::PATH_UPLOAD_COMMISSION,
                        'minio'
                    );
                    $data['key_word_image'] = $keyWordImagePath;
                } catch (\Exception $e) {
                    logger()->error("Lỗi khi xử lý ảnh từ khóa: " . $e->getMessage());
                    $data['key_word_image'] = $commission->key_word_image;
                }
            } else {
                $data['key_word_image'] = $commission->key_word_image;
            }

            $updateData = [
                'key_word' => $data['key_word'] ?? $commission->key_word,
                'url' => $data['url'] ?? $commission->url,
                'daily_limit' => $data['daily_limit'] ?? $commission->daily_limit,
                'key_word_image' => $data['key_word_image'],
            ];

            $commission->update($updateData);

            DB::commit();
            return redirect()->route('admin.commissions.index')
                ->with('success', 'Cập nhật nhiệm vụ thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());

            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $commission = $this->commission->where('commission_id', $id)->first();

            \Storage::disk('minio')->delete($commission->key_word_image);

            $instructionImages = $commission->images;

            foreach ($instructionImages as $image) {
                \Storage::disk('minio')->delete($image->image);
                $image->delete();
            }

            $commission->delete();

            DB::commit();

            return redirect()->route('admin.commissions.index')
                ->with('success', 'Xóa nhiệm vụ thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
