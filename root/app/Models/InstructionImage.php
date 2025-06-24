<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstructionImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'image'
    ];
}
