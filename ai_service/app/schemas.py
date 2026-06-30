from pydantic import BaseModel
from typing import List, Optional

class Vitals(BaseModel):
    heart_rate: float
    spo2: float
    body_temperature: float
    ecg_signal: float
    hrv_rmssd: float
    systolic_bp: float
    diastolic_bp: float
    bp_category_code: int

# 2. فاليديشن مصفوفة الحركة (كل ثانية فيها x, y, z)
class SecondData(BaseModel):
    x: List[float]
    y: List[float]
    z: List[float]

class PatientInfo(BaseModel):
    gender: int
    age: int
    weight_kg: float
    height_cm: float

# 3. الفاليديشن الأساسي للريكويست بالكامل (بيطابق لارافيل بالمللي)
class SensorData(BaseModel):
    device_uid: str
    vitals: Vitals
    uv_index: Optional[float] = None  # Optional تعني أنه يقبل قيمة float أو null
    movement: List[SecondData]
    patient_info: PatientInfo

class DiabetesForm(BaseModel):
    Age: int
    Gender: int  # 1 for Male, 0 for Female
    Polyuria: int # 1 for Yes, 0 for No (كثرة التبول)
    Polydipsia: int # (العطش الشديد)
    sudden_weight_loss: int
    weakness: int
    Polyphagia: int # (الجوع الشديد)
    Genital_thrush: int
    visual_blurring: int
    Itching: int
    Irritability: int
    delayed_healing: int
    partial_paresis: int
    muscle_stiffness: int
    Alopecia: int
    Obesity: int