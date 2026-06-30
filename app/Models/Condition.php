<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Condition extends Model
{
    use HasFactory;

    protected $table = 'conditions';

    protected $fillable = [
        'patient_id',
        'disease_name',
        'status',
        'diagnosed_at',
        'notes'
    ];

    // تحديد نوع البيانات (Casting) لضمان التعامل مع التواريخ ككائنات Carbon
    protected $casts = [
        'diagnosed_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * علاقة المرض بالمريض
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * علاقة المرض بالأدوية المرتبطة به
     */
    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class, 'condition_id');
    }
}