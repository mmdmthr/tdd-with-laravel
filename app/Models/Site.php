<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_online' => 'boolean',
        'is_resolving' => 'boolean',
    ];

    function user() {
        return $this->belongsTo(User::class);
    }

    function checks() {
        return $this->hasMany(Check::class);
    }
}
