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

        $keyWordImagePath = $this->generateAndStoreKeyWordImage($data['key_word']);
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

            $keyWordImagePath = $this->generateAndStoreKeyWordImage($data['key_word']);
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

    protected function generateAndStoreKeyWordImage(string $text): string
    {
        $fontPath = public_path('fonts/NotoSans-Regular.ttf');
        $fontSize = 20;
        $padding = 20;

        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];

        $width = $textWidth + 2 * $padding;
        $height = $textHeight + 2 * $padding;

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $width, $height, $black);

        $x = $padding;
        $y = $padding + $textHeight - 6;

        imagettftext($image, $fontSize, 0, $x, $y, $white, $fontPath, $text);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        $fileName = \Str::random(40) . '.png';
        $fullPath = self::PATH_UPLOAD_COMMISSION . '/' . $fileName;
        \Storage::disk('minio')->put($fullPath, $imageData);

        return $fullPath;
    }
}