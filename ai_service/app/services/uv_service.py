import joblib
import pandas as pd
import os
import warnings

warnings.filterwarnings("ignore", category=UserWarning)

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MODEL_PATH = os.path.join(BASE_DIR, 'models', 'uv', 'uv_model.pkl')

try:
    uv_model = joblib.load(MODEL_PATH)
except Exception as e:
    print(f"Error loading UV model: {e}")
    uv_model = None

def analyze_uv_risk(body_temperature: float, uv_index: float) -> dict:
    """
    تحليل خطر التعرض للشمس والحرارة بناءً على قراءات المريض
    """
    if uv_model is None:
        return {"status": "Unknown", "error": "Model not loaded properly"}
    
    df = pd.DataFrame([[body_temperature, uv_index]], columns=['body_temperature', 'uv_index'])
    
    prediction = uv_model.predict(df)[0]
    
    return {
        "status": prediction,
        "body_temperature": body_temperature,
        "uv_index": uv_index
    }