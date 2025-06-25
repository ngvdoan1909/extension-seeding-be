<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'keyword',
        'is_matched',
        'repeat_count',
        'current_repeat',
    ];
}
