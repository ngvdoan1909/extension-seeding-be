<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_url_id',
        'commission_id',
        'url',
        'key_word',
        'key_word_image',
    ];

    public function commission()
    {
        return $this->belongsTo(Commission::class, 'commission_id', 'commission_id');
    }

    public function images()
    {
        return $this->hasMany(InstructionImage::class, 'commission_url_id', 'commission_url_id');
    }
}
