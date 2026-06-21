<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DoctorProfileService
{
    /**
     * تحديث بيانات الحساب وأرقام الهواتف الـ HasMany
     */
    public function updateProfile(User $user, array $data): User
    {
        try {
            DB::beginTransaction();

            // 1. تحديث البيانات الأساسية في جدول users (الاسم والإيميل فقط)
            // عملنا تصفية للداتا عشان نضمن إن الحقول اللي مبعتتش متمسحش
            $userData = array_intersect_key($data, array_flip(['name', 'email']));
            if (!empty($userData)) {
                $user->update($userData);
            }

            // 2. تحديث أرقام الهواتف في جدول phones إذا وُجدت في الريكويست
            if (isset($data['phones'])) {
                // مسح الأرقام القديمة
                $user->phones()->delete();
                
                // إدخال الأرقام الجديدة دفعة واحدة
                $user->phones()->createMany($data['phones']);
            }

            DB::commit();

            // 3. إرجاع كائن المستخدم محدث ومحمل بعلاقة التليفونات فوراً
            return $user->fresh()->load('phones');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // رمي الإيرور عشان الكنترولر يمسكه ويرد بيه
        }
    }
}