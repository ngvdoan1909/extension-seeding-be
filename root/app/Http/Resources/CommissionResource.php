<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = [];
        $bucket = env('MINIO_BUCKET', 'extension-seeding');

        $imageKetWord = \Storage::disk('minio')->url($bucket . '/' . $this->key_word_image);

        foreach ($this->images as $image) {
            $images[] = [
                'url' => \Storage::disk('minio')->url($bucket . '/' . $image->image),
            ];
        }

        return [
            'id' => $this->id,
            'commission_id' => $this->commission_id,
            'key_word' => $this->key_word,
            'key_word_image' => $imageKetWord,
            'url' => $this->url,
            'daily_limit' => $this->daily_limit,
            'daily_completed' => $this->daily_completed,
            'images' => $images
        ];
    }
}