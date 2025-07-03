<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_session_id',
        'worker_id',
        'code',
        'is_matched'
    ];
}
