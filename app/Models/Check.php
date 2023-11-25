<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'response_status' => 'integer',
        'elapsed_time' => 'integer',
    ];

    public function successful()
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    function failed(): bool {
        return !$this->successful();
    }
}
