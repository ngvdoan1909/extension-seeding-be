<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'commission_id',
        'key_word',
        'key_word_image',
        'url',
        'daily_limit',
        'daily_completed'
    ];

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function images()
    {
        return $this->hasMany(InstructionImage::class, 'commission_id', 'commission_id');
    }

    public function workers()
    {
        return $this->hasMany(Worker::class, 'commission_id', 'commission_id');
    }
}
