<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookCall extends Model
{
    use HasFactory;

    public $guarded = [];

    public $casts = [
        'data' => 'array',
    ];

    public function check()
    {
        return $this->belongsTo(Check::class);
    }
}
