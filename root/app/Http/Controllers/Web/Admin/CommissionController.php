<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionRequest;
use App\Models\Commission;
use App\Models\CommissionUrl; // Model mới
use App\Models\InstructionImage;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommissionController extends Controller
{
    protected $website;
    protected $commission;
    protected $commissionUrl;
    protected $instructionImage;

    const PATH_VIEW = 'admin.pages.commissions.';
    const PATH_UPLOAD_COMMISSION = 'commission';
    const PATH_UPLOAD_INSTRUCTION = 'instruction';

    public function __construct(
        Website $website,
        Commission $commission,
        CommissionUrl $commissionUrl,
        InstructionImage $instructionImage
    ) {
        $this->website = $website;
        $this->commission = $commission;
        $this->commissionUrl = $commissionUrl;
        $this->instructionImage = $instructionImage;
    }

    public function index()
    {
        try {
            $data = $this->commission->with([
                'website',
                'urls' => function ($query) {
                    $query->with('images');
                }
            ])
                ->latest('id')
                ->get();

            return view(self::PATH_VIEW . __FUNCTION__, compact('data'));
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function show(string $id)
    {
        try {
            $commission = $this->commission->with([
                'urls' => function ($query) {
                    $query->with('images')->orderBy('order');
                }
            ])
                ->where('commission_id', $id)
                ->firstOrFail();

            return view(self::PATH_VIEW . __FUNCTION__, compact('commission'));
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $websites = $this->website->select('website_id', 'name')->get();
        return view(self::PATH_VIEW . __FUNCTION__, compact('websites'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $commission = $this->commission->create([
                'website_id' => $data['website_id'],
                'commission_id' => Str::uuid(),
                'daily_limit' => $data['daily_limit'],
                'daily_completed' => 0
            ]);

            foreach ($data['urls'] as $index => $urlData) {
                $commissionUrl = $this->commissionUrl->create([
                    'commission_url_id' => Str::uuid(),
                    'commission_id' => $commission->commission_id,
                    'url' => $urlData['url'],
                    'key_word' => $urlData['key_word'],
                ]);

                if (!empty($urlData['images'])) {
                    foreach ($urlData['images'] as $image) {
                        $path = \Storage::disk('minio')->put(
                            self::PATH_UPLOAD_INSTRUCTION,
                            $image
                        );

                        $this->instructionImage->create([
                            'commission_url_id' => $commissionUrl->commission_url_id,
                            'image' => $path
                        ]);
                    }
                }

                $keyWordImagePath = generateTextImage(
                    [$urlData['key_word']],
                    self::PATH_UPLOAD_COMMISSION,
                    'minio'
                );

                $commissionUrl->update(['key_word_image' => $keyWordImagePath]);
            }

            DB::commit();

            return redirect()->route('admin.commissions.index')
                ->with('success', 'Thêm nhiệm vụ thành công');

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Commission store error: ' . $e->getMessage());
            dd($e->getMessage());
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function update(CommissionRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $commission = $this->commission->where('commission_id', $id)->firstOrFail();

            $commission->update([
                'name' => $data['name'],
                'daily_limit' => $data['daily_limit'],
                'website_id' => $data['website_id']
            ]);

            $commission->urls()->delete();

            foreach ($data['urls'] as $urlData) {
                $url = $this->commissionUrl->create([
                    'commission_id' => $commission->commission_id,
                    'url' => $urlData['url'],
                    'key_word' => $urlData['key_word'],
                    'order' => $this->commissionUrl->where('commission_id', $commission->commission_id)->count()
                ]);

                if (!empty($urlData['images'])) {
                    foreach ($urlData['images'] as $image) {
                        $path = \Storage::disk('minio')->put(
                            self::PATH_UPLOAD_INSTRUCTION,
                            $image
                        );

                        $this->instructionImage->create([
                            'url_id' => $url->id,
                            'image' => $path
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('admin.commissions.index')
                ->with('success', 'Cập nhật nhiệm vụ thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Commission update error: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $commission = $this->commission->where('commission_id', $id)->firstOrFail();

            foreach ($commission->urls as $url) {
                foreach ($url->images as $image) {
                    \Storage::disk('minio')->delete($image->image);
                    $image->delete();
                }
            }

            $commission->urls()->delete();

            $commission->delete();

            DB::commit();

            return redirect()->route('admin.commissions.index')
                ->with('success', 'Xóa nhiệm vụ thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}