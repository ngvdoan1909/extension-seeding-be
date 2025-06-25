<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'key_word',
        'key_word_image',
        'url',
        'daily_limit',
        'daily_completed'
    ];

    public function images()
    {
        return $this->hasMany(InstructionImage::class, 'commission_id', 'commission_id');
    }

    public function workers(){
        return $this->hasMany(Worker::class, 'commission_id', 'commission_id');
    }
}
