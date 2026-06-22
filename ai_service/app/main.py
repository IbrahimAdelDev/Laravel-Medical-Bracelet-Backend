from fastapi import FastAPI
from app.schemas import SensorData
from app.services.health_calculators import calculate_bmi_category, process_movement_and_sleep
from app.services.fall_service import analyze_fall_data
from app.services.sleep_service import analyze_sleep_data

app = FastAPI(title="Medical AI Service")

@app.post("/analyze-patient-state")
def analyze_patient_state_endpoint(data: SensorData):
    
    # 1. الداتا الثابتة جاية جاهزة من لارافيل، هنحولها لقاموس (Dictionary)
    static_data = data.patient_info.dict()
    
    # 2. حساب الـ BMI مباشرة
    bmi_category_code = calculate_bmi_category(static_data['weight_kg'], static_data['height_cm'])
    static_data['bmi_category'] = bmi_category_code
    
    # 3. حسابات النوم والنشاط البدني والخطوات 
    health_metrics = process_movement_and_sleep(
        movement_data=data.movement,
        resting_heart_rate=72,
        current_heart_rate=data.vitals.heart_rate,
        spo2=data.vitals.spo2
    )
    
    static_data['steps_category'] = health_metrics['steps_category']
    static_data['activity_level'] = health_metrics['activity_level_minutes']

    # 4. تحليل السقوط (Fall Detection)
    is_falling = False
    for second in data.movement:
        if analyze_fall_data(second.x, second.y, second.z):
            is_falling = True
            break

    # 5. تشغيل موديل اضطرابات النوم (.pkl)
    sleep_results = analyze_sleep_data(
        vitals=data.vitals, 
        sleep_duration=health_metrics['sleep_duration'], 
        sleep_quality=health_metrics['sleep_quality'],
        static_patient_data=static_data
    )

    # 6. إرجاع النتيجة
    return {
        "status": "success",
        "fall_detected": is_falling,
        "sleep_analysis": sleep_results
    }