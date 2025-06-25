<?php
namespace App\Services;

use App\Models\Commission;
use App\Models\InstructionImage;

class CommissionService
{
    protected $commission;
    protected $instructionImage;

    const PATH_UPLOAD_COMMISSION = 'commission';
    const PATH_UPLOAD_INSTRCUTION = 'instruction';

    public function __construct(
        Commission $commission,
        InstructionImage $instructionImage
    ) {
        $this->commission = $commission;
        $this->instructionImage = $instructionImage;
    }

    public function fetchCommissions()
    {
        $commissions = $this->commission->with('images')->select('id', 'commission_id', 'key_word', 'key_word_image', 'url', 'daily_limit', 'daily_completed')->get();

        if ($commissions->isEmpty()) {
            throw new \Exception('Không có dữ liệu');
        }

        return $commissions;
    }

    public function getDetailCommission(string $id)
    {
        $commission = $this->commission->where('commission_id', $id)->first();

        if (!$commission) {
            throw new \Exception('Không tìm thấy nhiệm vụ');
        }

        return $commission;
    }

    public function createNewCommission(array $data = [])
    {
        $commission = $this->commission->create([
            'commission_id' => \Str::uuid(),
            'key_word' => $data['key_word'],
            'url' => $data['url'],
            'daily_limit' => $data['daily_limit']
        ]);

        $images = $data['image'] ?? [];
        if (!is_array($images)) {
            $images = [$images];
        }

        foreach ($images as $image) {
            $path = \Storage::disk('minio')->put(self::PATH_UPLOAD_INSTRCUTION, $image);

            $this->instructionImage->create([
                'commission_id' => $commission->commission_id,
                'image' => $path
            ]);
        }

        $keyWordImagePath = generateTextImage([$data['key_word']], self::PATH_UPLOAD_COMMISSION, 'minio');
        $commission->update(['key_word_image' => $keyWordImagePath]);

        return $commission;
    }

    public function updateCommission(string $id, array $data = [])
    {
        $commission = $this->getDetailCommission($id);

        if (!empty($data['image'])) {
            $instructionImages = $commission->images;

            foreach ($instructionImages as $image) {
                \Storage::disk('minio')->delete($image->image);
                $image->delete();
            }

            $images = is_array($data['image']) ? $data['image'] : [$data['image']];
            foreach ($images as $image) {
                $path = \Storage::disk('minio')->put(self::PATH_UPLOAD_INSTRCUTION, $image);
                $this->instructionImage->create([
                    'commission_id' => $commission->commission_id,
                    'image' => $path
                ]);
            }
        }

        if (!empty($data['key_word'])) {
            if ($commission->key_word_image) {
                \Storage::disk('minio')->delete($commission->key_word_image);
            }

            $keyWordImagePath = generateTextImage($data['key_word'], self::PATH_UPLOAD_COMMISSION, 'minio');
            $data['key_word_image'] = $keyWordImagePath;
        }

        $commission->update([
            'key_word' => $data['key_word'],
            'url' => $data['url'],
            'daily_limit' => $data['daily_limit'],
            'key_word_image' => $data['key_word_image'],
        ]);

        return $commission;
    }

    public function deleteCommission(string $id)
    {
        $commission = $this->getDetailCommission($id);

        \Storage::disk('minio')->delete($commission->key_word_image);

        $instructionImages = $commission->images;

        foreach ($instructionImages as $image) {
            \Storage::disk('minio')->delete($image->image);
            $image->delete();
        }

        $commission->delete();

        return $commission;
    }
}