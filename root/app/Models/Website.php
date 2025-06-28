<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'name',
        'domain',
    ];

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'website_id', 'website_id');
    }
}
