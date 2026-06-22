import joblib
import pandas as pd
import os

# تحميل الموديل
MODEL_PATH = os.path.join(os.path.dirname(__file__), '../models/sleep/sleep_disorder_model.pkl')
sleep_model = joblib.load(MODEL_PATH)

def analyze_sleep_data(vitals, sleep_duration: float, sleep_quality: int, static_patient_data: dict) -> dict:
    
    # 1. بناء القاموس بنفس أسماء العواميد اللي الموديل متدرب عليها بالمللي
    patient_features = {
        'Person ID': 999,  # قيمة وهمية لأن الموديل بيطلبها بس مش بيعتمد عليها
        'Gender': static_patient_data.get('gender', 1), 
        'Age': static_patient_data.get('age', 30),
        'Occupation': 3,   # قيمة وهمية (مثلاً 3 تعني ممرض/دكتور) بناءً على التشفير القديم
        'Sleep Duration': sleep_duration,
        'Quality of Sleep': sleep_quality,
        'Physical Activity Level': static_patient_data.get('activity_level', 30),
        'Stress Level': 5, # قيمة افتراضية للتوتر
        'BMI Category': static_patient_data.get('bmi_category', 1),
        'Blood Pressure': vitals.bp_category_code, # إنت كنت مشفر الضغط في كود الـ ESP
        'Heart Rate': vitals.heart_rate,
        'Daily Steps': 5000 # قيمة الخطوات 
    }

    # 2. تحويلها لـ DataFrame
    features_df = pd.DataFrame([patient_features])

    # 3. ترتيب العواميد (أهم خطوة عشان الـ Scaler في الموديل ميضربش إيرور)
    ordered_columns = [
        'Person ID', 'Gender', 'Age', 'Occupation', 'Sleep Duration', 
        'Quality of Sleep', 'Physical Activity Level', 'Stress Level', 
        'BMI Category', 'Blood Pressure', 'Heart Rate', 'Daily Steps'
    ]
    
    # إجبار الـ DataFrame على نفس الترتيب
    features_df = features_df[ordered_columns]

    # 4. التنبؤ
    prediction = sleep_model.predict(features_df)[0]
    
    # 5. الموديل بيرجع النص صريح زي ما اتدرب عليه، فهنرجعه زي ما هو
    disorder_result = str(prediction)

    return {
        "duration": sleep_duration,
        "quality_score": sleep_quality,
        "disorder_prediction": disorder_result
    }