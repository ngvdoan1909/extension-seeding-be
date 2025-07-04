<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstructionImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_url_id',
        'image'
    ];

    public function commissionUrl()
    {
        return $this->belongsTo(CommissionUrl::class, 'commission_url_id', 'commission_url_id');
    }
}
