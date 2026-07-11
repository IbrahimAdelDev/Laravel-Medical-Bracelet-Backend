import math
import numpy as np

def calculate_bmi_category(weight_kg: float, height_cm: float) -> int:
    height_m = height_cm / 100.0
    bmi = weight_kg / (height_m ** 2)
    if bmi < 21.5: return 0  # Normal
    elif 21.5 <= bmi < 25.0: return 1  # Normal Weight
    elif 25.0 <= bmi < 30.0: return 2  # Overweight
    else: return 3  # Obese

def process_movement_and_sleep(movement_data, resting_heart_rate, current_heart_rate, spo2):
    """
    هذه الدالة تدمج حسابات النوم والنشاط البدني والخطوات
    لأننا نمر على مصفوفة الحركة (20 قراءة في الثانية) مرة واحدة للحفاظ على الأداء (High Performance)
    """
    total_sleep_minutes = 0
    stable_sleep_minutes = 0
    active_seconds = 0
    total_steps = 0
    
    step_threshold = 12.0
    
    for second in movement_data:
        second_motion_diff = 0
        is_active_second = False
        
        for i in range(1, len(second.x)):
            diff_x = abs(second.x[i] - second.x[i-1])
            diff_y = abs(second.y[i] - second.y[i-1])
            diff_z = abs(second.z[i] - second.z[i-1])
            second_motion_diff += (diff_x + diff_y + diff_z)
            
            magnitude = math.sqrt(second.x[i]**2 + second.y[i]**2 + second.z[i]**2)
            dynamic_acc = abs(magnitude - 9.8)
            
            if dynamic_acc > 1.5:
                is_active_second = True
                
            if magnitude > step_threshold:
                total_steps += 1 

        is_still = second_motion_diff < 0.2
        is_hr_low = current_heart_rate < (resting_heart_rate * 0.85)
        is_spo2_normal = 95 <= spo2 <= 100
        
        if is_still and (is_hr_low or is_spo2_normal):
            total_sleep_minutes += (1/60) 
            if second_motion_diff < 0.05:
                stable_sleep_minutes += (1/60)

        if is_active_second:
            active_seconds += 1

    calculated_sleep_hours = total_sleep_minutes / 60.0
    final_sleep_duration = max(5.8, min(8.5, calculated_sleep_hours))
    
    stability_ratio = (stable_sleep_minutes / total_sleep_minutes * 100) if total_sleep_minutes > 0 else 0
    if stability_ratio >= 85: sleep_quality = 9
    elif stability_ratio >= 70: sleep_quality = 8
    elif stability_ratio >= 55: sleep_quality = 7
    elif stability_ratio >= 40: sleep_quality = 6
    else: sleep_quality = 4
    
    if total_steps < 5000: steps_code = 0
    elif 5000 <= total_steps < 7500: steps_code = 1
    elif 7500 <= total_steps < 10000: steps_code = 2
    else: steps_code = 3

    return {
        "sleep_duration": final_sleep_duration,
        "sleep_quality": sleep_quality,
        "activity_level_minutes": max(30, min(90, round(active_seconds / 60.0))),
        "steps_category": steps_code
    }