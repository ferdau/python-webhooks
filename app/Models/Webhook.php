<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'name',
        'url',
        'script_path',
        'active',
        'parameters'
    ];

    protected $casts = [
        'active' => 'boolean',
        'parameters' => 'array'
    ];
}
