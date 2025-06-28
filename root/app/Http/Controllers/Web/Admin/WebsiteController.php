<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteRequest;
use App\Models\Commission;
use App\Models\InstructionImage;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteController extends Controller
{
    protected $website;
    protected $commission;
    protected $instructionImage;

    const PATH_VIEW = 'admin.pages.websites.';
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
            $data = $this->website->select('id', 'website_id', 'name', 'domain')->latest('id')->get();

            return view(self::PATH_VIEW . __FUNCTION__, compact('data'));
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function create()
    {
        return view(self::PATH_VIEW . __FUNCTION__);
    }

    public function store(WebsiteRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $website = $this->website->create([
                'website_id' => \Str::uuid(),
                'name' => $data['name'],
                'domain' => $data['domain'],
            ]);

            if ($website) {
                foreach ($data['commissions'] as $commissionData) {
                    $commission = $this->commission->create([
                        'website_id' => $website->website_id,
                        'commission_id' => \Str::uuid(),
                        'key_word' => $commissionData['key_word'],
                        'url' => $commissionData['url'],
                        'daily_limit' => $commissionData['daily_limit'],
                        'daily_completed' => 0
                    ]);

                    $keyWordImagePath = generateTextImage(
                        [$commissionData['key_word']],
                        self::PATH_UPLOAD_COMMISSION,
                        'minio'
                    );
                    $commission->update(['key_word_image' => $keyWordImagePath]);

                    if (!empty($commissionData['images'])) {
                        foreach ($commissionData['images'] as $image) {
                            $path = \Storage::disk('minio')->put(
                                self::PATH_UPLOAD_INSTRCUTION,
                                $image
                            );

                            $this->instructionImage->create([
                                'commission_id' => $commission->commission_id,
                                'image' => $path
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('admin.websites.index')->with("success", "Thêm mới website thành công");
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function show(string $id)
    {
        try {
            $data = $this->website->where('website_id', $id)->select('website_id', 'name', 'domain')->first();

            if (!$data) {
                return back()->with('error', 'Không tìm thấy website');
            }

            return view(self::PATH_VIEW . __FUNCTION__, compact('data'));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function update(WebsiteRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'domain']);

            $this->website->where('website_id', $id)->update($data);

            DB::commit();
            return redirect()->route('admin.websites.index')
                ->with("success", "Cập nhật website thành công");
        } catch (\Exception $e) {
            DB::rollBack();
            // dd($e->getMessage());

            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $website = $this->website->with('commissions')->where('website_id', $id)->first();

            if (!$website) {
                return back()->with('error', 'Không tìm thấy website');
            }

            foreach ($website->commissions as $commission) {
                \Storage::disk('minio')->delete($commission->key_word_image);

                $this->instructionImage->where('commission_id', $commission->commission_id)
                    ->each(function ($image) {
                        \Storage::disk('minio')->delete($image->image);
                    });

                $this->instructionImage->where('commission_id', $commission->commission_id)->delete();
            }

            $website->commissions()->delete();

            $website->delete();

            DB::commit();

            return redirect()->route('admin.websites.index')
                ->with('success', 'Xóa website và các nhiệm vụ liên quan thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra khi xóa: ' . $e->getMessage());
        }
    }
}
