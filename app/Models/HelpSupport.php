<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpSupport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'issue_category',
        'description',
        'screenshot',
        'priority'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
