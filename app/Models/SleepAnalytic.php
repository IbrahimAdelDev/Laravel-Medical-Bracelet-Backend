<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SleepAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'date',
        'sleep_duration',
        'sleep_quality',
        'disorder_prediction',
    ];

    protected $casts = [
        'date' => 'date',
        'sleep_duration' => 'float',
        'sleep_quality' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
