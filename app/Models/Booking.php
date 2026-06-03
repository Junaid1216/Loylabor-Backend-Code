<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'service_date' => 'date',
        'time_slot' => 'datetime:H:i',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}