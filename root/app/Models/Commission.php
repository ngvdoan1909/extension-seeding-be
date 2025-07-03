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
        'daily_limit',
        'daily_completed'
    ];

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function images()
    {
        return $this->hasMany(InstructionImage::class, 'commission_url_id', 'commission_url_id');
    }

    public function workers()
    {
        return $this->hasMany(Worker::class, 'commission_id', 'commission_id');
    }

    public function urls()
    {
        return $this->hasMany(CommissionUrl::class, 'commission_id', 'commission_id');
    }
}
