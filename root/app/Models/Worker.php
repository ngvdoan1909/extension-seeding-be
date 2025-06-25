<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'user_id',
        'user_name',
        'user_phone',
        'commission_id',
        'ip',
        'executed_at',
        'is_completed',
    ];
}
