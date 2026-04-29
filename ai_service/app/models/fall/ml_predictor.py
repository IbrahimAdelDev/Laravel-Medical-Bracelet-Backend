import joblib
import pandas as pd
import os

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

class FallPredictor:
    def __init__(self):
        self.scaler = joblib.load(os.path.join(BASE_DIR, 'scaler.pkl'))
        self.model = joblib.load(os.path.join(BASE_DIR, 'fall_detection_model.pkl'))

    def predict(self, features: pd.DataFrame) -> bool:
        # هنا الـ features جاية كـ DataFrame بأسماء الأعمدة
        scaled_features = self.scaler.transform(features)
        prediction = self.model.predict(scaled_features)
        return bool(prediction[0])