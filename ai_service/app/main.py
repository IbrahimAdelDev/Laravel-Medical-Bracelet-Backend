from fastapi import FastAPI
from app.schemas import SensorData
from app.services.fall_service import analyze_fall_data

app = FastAPI(title="Fall Detection AI Service")

@app.post("/predict-fall")
def predict_fall_endpoint(data: SensorData):
    # اللوب على الـ 10 ثواني (كل ثانية بتتبعت للموديل كـ 20 قراءة)
    for second in data.movement:
        is_falling = analyze_fall_data(second.x, second.y, second.z)
        
        # لو لقط وقوع في أي ثانية من الـ 10، يرجع True فوراً ويوقف اللوب
        if is_falling:
            return {"fall_detected": True}
            
    # لو خلص الـ 10 ثواني ومفيش وقعة
    return {"fall_detected": False}