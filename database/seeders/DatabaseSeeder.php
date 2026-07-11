<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Device;
use App\Models\Condition;
use App\Models\Medication;
use App\Models\MedicationDose; 
use App\Models\SensorReading;
use App\Models\SleepAnalytic;
use App\Models\Alert;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::table('phones')->truncate();
        Device::truncate();
        Condition::truncate();
        Medication::truncate();
        MedicationDose::truncate();
        SensorReading::truncate();
        SleepAnalytic::truncate();
        Alert::truncate();
        DB::table('doctor_patients')->truncate();
        DB::table('family_patients')->truncate();
        DB::table('notifications')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Creating 10 Users (4 Doctors, 6 Family, 10 Patients) with Phones...');

        $doctors = [];
        for ($i = 1; $i <= 4; $i++) {
            $email = "doctor{$i}@test.com";
            $doctor = User::create([
                'name' => "Dr. " . $faker->firstName . ' ' . $faker->lastName,
                'email' => $email,
                'password' => Hash::make($email),
                'role' => 'doctor',
            ]);
            $doctors[] = $doctor;

            DB::table('phones')->insert([
                'user_id' => $doctor->id,
                'phone_number' => $faker->phoneNumber,
                'type' => 'personal',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $families = [];
        for ($i = 1; $i <= 6; $i++) {
            $email = "family{$i}@test.com";
            $family = User::create([
                'name' => "Family " . $faker->firstName,
                'email' => $email,
                'password' => Hash::make($email),
                'role' => 'user',
                'age' => $faker->numberBetween(20, 80),
                'weight' => $faker->randomFloat(1, 50, 120),
                'height' => $faker->randomFloat(1, 150, 190),
                'can_self_manage' => $faker->boolean(70), 
            ]);
            $families[] = $family;

            DB::table('phones')->insert([
                'user_id' => $family->id,
                'phone_number' => $faker->phoneNumber,
                'type' => 'personal',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $patients = [];
        for ($i = 1; $i <= 10; $i++) {
            $email = "patient{$i}@test.com";
            $patient = User::create([
                'name' => "Patient " . $faker->firstName,
                'email' => $email,
                'password' => Hash::make($email),
                'role' => 'user',
                'age' => $faker->numberBetween(20, 80),
                'weight' => $faker->randomFloat(1, 50, 120),
                'height' => $faker->randomFloat(1, 150, 190),
                'can_self_manage' => $faker->boolean(70), 
            ]);
            $patients[] = $patient;

            DB::table('phones')->insert([
                'user_id' => $patient->id,
                'phone_number' => $faker->phoneNumber,
                'type' => 'personal',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Linking Relations (Doctors & Families to Patients)...');
        
        $doctors[0]->patients()->attach([$patients[0]->id, $patients[1]->id, $patients[2]->id]);
        $doctors[1]->patients()->attach([$patients[3]->id]);

        $families[0]->patients()->attach([$patients[0]->id, $patients[1]->id]);
        $families[1]->patients()->attach([$patients[2]->id]);

        $this->command->info('Generating Devices, Sensors, History, Medications, and Alerts...');

        $conditionsList = ['Diabetes Type 2', 'Hypertension', 'Asthma', 'Heart Failure', 'Arthritis'];
        
        foreach ($patients as $index => $patient) {
            $hasDevice = $index < 3; 

            $device = null;
            if ($hasDevice) {
                $device = Device::create([
                    'patient_id' => $patient->id,
                    'device_uid' => 'DEV-' . strtoupper($faker->unique()->bothify('?????-#####')),
                    'status' => 'active',
                ]);

                for ($s = 0; $s < 10; $s++) {
                    SensorReading::create([
                        'device_id' => $device->id,
                        'patient_id' => $patient->id,
                        'payload' => [
                            'heart_rate' => $faker->numberBetween(60, 110),
                            'spo2' => $faker->numberBetween(92, 100),
                            'body_temperature' => $faker->randomFloat(1, 36.5, 38.5),
                        ],
                        'created_at' => $now->copy()->subMinutes($s * 15),
                    ]);
                }

                for ($sl = 1; $sl <= 5; $sl++) {
                    SleepAnalytic::create([
                        'patient_id' => $patient->id,
                        'date' => $now->copy()->subDays($sl)->toDateString(),
                        'sleep_duration' => $faker->numberBetween(24000, 32000), 
                        'sleep_quality' => $faker->numberBetween(40, 95),
                        'disorder_prediction' => $faker->randomElement(['None', 'Sleep Apnea', 'Insomnia']),
                    ]);
                }

                for ($a = 0; $a < 3; $a++) {
                    Alert::create([
                        'patient_id' => $patient->id,
                        'device_id' => $device->id,
                        'type' => $faker->randomElement(['vitals_emergency', 'fall_detected', 'sos_pressed']),
                        'is_resolved' => $a === 0 ? false : true, 
                        'notes' => $faker->sentence,
                        'created_at' => $now->copy()->subHours($a),
                    ]);
                }
            }

            $conditionCount = $faker->numberBetween(1, 3);
            for ($c = 0; $c < $conditionCount; $c++) {
                $condition = Condition::create([
                    'patient_id' => $patient->id,
                    'disease_name' => $faker->randomElement($conditionsList),
                    'status' => $c === 0 ? 'active' : 'resolved', 
                    'diagnosed_at' => $now->copy()->subMonths($faker->numberBetween(5, 50)),
                    'notes' => $faker->paragraph,
                ]);

                $medsCount = $faker->numberBetween(1, 2);
                for ($m = 0; $m < $medsCount; $m++) {
                    $isPast = $faker->boolean(40); 
                    
                    $medication = Medication::create([
                        'patient_id' => $patient->id,
                        'condition_id' => $condition->id,
                        'name' => $faker->randomElement(['Aspirin', 'Metformin', 'Amoxicillin', 'Panadol', 'Lisinopril']),
                        'dosage' => $faker->numberBetween(1, 3) . ' ' . $faker->randomElement(['mg', 'ml', 'pills']),
                        'start_date' => $condition->diagnosed_at->copy()->addDays(2),
                        'end_date' => $isPast ? $now->copy()->subDays($faker->numberBetween(5, 30)) : null,
                        'stop_reason' => $isPast ? $faker->sentence : null,
                    ]);

                    if (!$isPast) {
                        for ($d = -2; $d <= 2; $d++) { 
                            MedicationDose::create([
                                'medication_id' => $medication->id,
                                'scheduled_at' => $now->copy()->addHours($d * 8),
                                'status' => $d < 0 ? $faker->randomElement(['taken', 'missed']) : 'pending',
                                'taken_at' => $d < 0 ? $now->copy()->addHours($d * 8)->addMinutes(10) : null,
                            ]);
                        }
                    }
                }
            }

            DB::table('notifications')->insert([
                'title' => 'Welcome',
                'message' => 'Your profile is ready.',
                'type' => 'general', 
                'related_id' => $patient->id,
                'related_model' => 'App\Models\User',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $notification = DB::table('notifications')->latest()->first(); 

            DB::table('notification_users')->insert([
                'notification_id' => $notification->id,
                'user_id' => $patient->id,
                'is_read' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Database Seeded Successfully! 🔥');
        $this->command->info('Emails format: doctor1@test.com, family1@test.com, patient1@test.com (Passwords are the same as emails)');
    }
}