<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationDose extends Model
{
    use HasFactory;

    protected $fillable = [
        'medication_id',
        'scheduled_at',
        'taken_at',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'taken_at' => 'datetime',
    ];

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    protected $appends = ['actual_status'];

    public function getActualStatusAttribute(): string
    {
        if ($this->status === 'pending' && $this->scheduled_at < now()) {
            return 'missed';
        }
        
        return $this->status;
    }

}