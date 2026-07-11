<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DoctorProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        try {
            DB::beginTransaction();

            $userData = array_intersect_key($data, array_flip(['name', 'email']));
            if (!empty($userData)) {
                $user->update($userData);
            }

            if (isset($data['phones'])) {
                $user->phones()->delete();
                
                $user->phones()->createMany($data['phones']);
            }

            DB::commit();

            return $user->fresh()->load('phones');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}