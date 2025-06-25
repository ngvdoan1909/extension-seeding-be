<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

if (!function_exists('getRandomFakeInfo')) {
    function getRandomFakeInfo()
    {
        $data = Cache::rememberForever('data_fake', function () {
            $json = Storage::get('data/data_fake.json');
            return json_decode($json, true);
        });

        return $data[array_rand($data)];
    }
}

if (!function_exists('generateTextImage')) {
    function generateTextImage(array $lines, $path, $disk = 'minio'): string
    {
        $fontPath = public_path('fonts/NotoSans-Regular.ttf');
        $fontSize = 20;
        $padding = 30;
        $lineSpacing = 20;

        $lineBoxes = [];
        $maxWidth = 0;
        $totalHeight = 0;

        foreach ($lines as $line) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $line);
            $width = $box[2] - $box[0];
            $height = $box[1] - $box[7];

            $lineBoxes[] = [
                'text' => $line,
                'width' => $width,
                'height' => $height,
            ];

            $maxWidth = max($maxWidth, $width);
            $totalHeight += $height + $lineSpacing;
        }

        $imageWidth = $maxWidth + 2 * $padding;
        $imageHeight = $totalHeight - $lineSpacing + 2 * $padding;

        $image = imagecreatetruecolor($imageWidth, $imageHeight);
        $background = imagecolorallocate($image, 0, 0, 0);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $imageWidth, $imageHeight, $background);

        $y = $padding;
        foreach ($lineBoxes as $box) {
            $x = ($imageWidth - $box['width']) / 2;
            $y += $box['height'];
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $box['text']);
            $y += $lineSpacing;
        }

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        $fileName = \Str::random(40) . '.png';
        $fullPath = $path . '/' . $fileName;
        \Storage::disk($disk)->put($fullPath, $imageData);

        return $fullPath;
    }
}
