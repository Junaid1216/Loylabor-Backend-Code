<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianAvailability extends Model
{
    protected $table = 'technician_availability';

    protected $fillable = [
        'technician_id',
        'day',
        'start_time',
        'end_time',
        'is_available',
        'specific_date',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'specific_date' => 'date',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
