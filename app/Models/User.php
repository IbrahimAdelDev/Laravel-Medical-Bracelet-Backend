<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'age',
        'height',
        'weight',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'age' => 'integer',
            'height' => 'float',
            'weight' => 'float',
        ];
    }

    public function locations() {
        return $this->hasMany(Location::class);
    }

    public function phones() {
        return $this->hasMany(Phone::class);
    }

    public function notifications() {
        return $this->belongsToMany(Notification::class, 'notification_users', 'user_id', 'notification_id')
                    ->withPivot('is_read', 'read_at')
                    ->withTimestamps();
    }

    public function patientProfile() {
        return $this->hasOne(PatientProfile::class);
    }

    public function doctors() {
        return $this->belongsToMany(User::class, 'doctor_patients', 'patient_id', 'doctor_id')
                    ->withPivot('specialty', 'status', 'started_at', 'ended_at')
                    ->withTimestamps();
    }

    public function familyMembers() {
        return $this->belongsToMany(User::class, 'family_patients', 'patient_id', 'family_member_id')
                    ->withPivot('relationship')
                    ->withTimestamps();
    }

    public function devices() {
        return $this->hasMany(Device::class, 'patient_id');
    }

    public function sensorReadings() {
        return $this->hasMany(SensorReading::class, 'patient_id');
    }

    public function alerts() {
        return $this->hasMany(Alert::class, 'patient_id');
    }

    public function medications() {
        return $this->hasMany(Medication::class, 'patient_id');
    }

    public function medicalHistories() {
        return $this->hasMany(MedicalHistory::class, 'patient_id');
    }

    public function patients() {
        return $this->belongsToMany(User::class, 'doctor_patients', 'doctor_id', 'patient_id')
                    ->withPivot('specialty', 'status', 'started_at', 'ended_at')
                    ->withTimestamps();
    }

    public function secretaries() {
        return $this->belongsToMany(User::class, 'doctor_secretaries', 'doctor_id', 'secretary_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function monitoredPatients() {
        return $this->belongsToMany(User::class, 'family_patients', 'family_member_id', 'patient_id')
                    ->withPivot('relationship')
                    ->withTimestamps();
    }

    public function employedByDoctors() {
        return $this->belongsToMany(User::class, 'doctor_secretaries', 'secretary_id', 'doctor_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }
}
