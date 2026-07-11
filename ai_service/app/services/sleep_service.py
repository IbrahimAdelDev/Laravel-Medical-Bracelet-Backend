import joblib
import pandas as pd
import os

MODEL_PATH = os.path.join(os.path.dirname(__file__), '../models/sleep/sleep_disorder_model.pkl')
sleep_model = joblib.load(MODEL_PATH)

def analyze_sleep_data(vitals, sleep_duration: float, sleep_quality: int, static_patient_data: dict) -> dict:
    
    patient_features = {
        'Person ID': 999,  
        'Gender': static_patient_data.get('gender', 1), 
        'Age': static_patient_data.get('age', 30),
        'Occupation': 3,  
        'Sleep Duration': sleep_duration,
        'Quality of Sleep': sleep_quality,
        'Physical Activity Level': static_patient_data.get('activity_level', 30),
        'Stress Level': 5, 
        'BMI Category': static_patient_data.get('bmi_category', 1),
        'Blood Pressure': vitals.bp_category_code,  
        'Heart Rate': vitals.heart_rate,
        'Daily Steps': 5000  
    }

    features_df = pd.DataFrame([patient_features])

    ordered_columns = [
        'Person ID', 'Gender', 'Age', 'Occupation', 'Sleep Duration', 
        'Quality of Sleep', 'Physical Activity Level', 'Stress Level', 
        'BMI Category', 'Blood Pressure', 'Heart Rate', 'Daily Steps'
    ]
    
    features_df = features_df[ordered_columns]

    prediction = sleep_model.predict(features_df)[0]
    
    disorder_result = str(prediction)

    return {
        "duration": sleep_duration,
        "quality_score": sleep_quality,
        "disorder_prediction": disorder_result
    }