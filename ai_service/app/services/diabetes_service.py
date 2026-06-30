import joblib
import pandas as pd
import os
import warnings

warnings.filterwarnings("ignore", category=UserWarning)

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MODEL_PATH = os.path.join(BASE_DIR, 'models', 'diabetes', 'gradient_boosting_diabetes_model.pkl')
SCALER_PATH = os.path.join(BASE_DIR, 'models', 'diabetes', 'minmax_scaler.pkl')

try:
    diabetes_model = joblib.load(MODEL_PATH)
    diabetes_scaler = joblib.load(SCALER_PATH)
except Exception as e:
    print(f"Error loading Diabetes model/scaler: {e}")
    diabetes_model, diabetes_scaler = None, None

def predict_diabetes_risk(data: dict) -> dict:
    if diabetes_model is None or diabetes_scaler is None:
        return {"status": "error", "message": "Model not loaded"}

    # 1. تحويل الداتا لـ DataFrame
    # (تأكد إن ترتيب الأعمدة هنا مطابق لترتيبهم في الـ Colab بالظبط)
    features = list(data.keys())
    df = pd.DataFrame([data.values()], columns=features)

    # 2. عمل Scaling للبيانات (لأنك رافع ملف Scaler)
    df_scaled = diabetes_scaler.transform(df)

    # 3. التنبؤ (Gradient Boosting)
    prediction = diabetes_model.predict(df_scaled)[0]
    
    # 4. حساب نسبة الخطر (Probability) لو الموديل بيدعمها
    probabilities = diabetes_model.predict_proba(df_scaled)[0]
    risk_percentage = round(probabilities[1] * 100, 2) # نسبة التأكد من الإصابة

    result_text = "Positive (High Risk)" if prediction == 1 else "Negative (Low Risk)"

    return {
        "prediction": result_text,
        "risk_percentage": risk_percentage,
        "is_diabetic": bool(prediction == 1)
    }