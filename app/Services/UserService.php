<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    // register normal user
    public function registerNormalUser(array $data)
    {
        $data['role'] = 'user';
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    // register doctor
    public function registerDoctor(array $data)
    {
        $data['role'] = 'doctor';
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    // register secretary or assign existing secretary to doctor
    public function assignOrCreateSecretary(array $data, User $doctor)
    {
        return DB::transaction(function () use ($data, $doctor) {
            if (isset($data['secretary_id'])) {
                $secretary = User::findOrFail($data['secretary_id']);
            } else {
                $data['role'] = 'secretary';
                $data['password'] = Hash::make($data['password']);
                $secretary = User::create($data);
            }

            // connect secretary to doctor without detaching existing secretaries
            $doctor->secretaries()->syncWithoutDetaching([$secretary->id]);

            return $secretary;
        });
    }
}