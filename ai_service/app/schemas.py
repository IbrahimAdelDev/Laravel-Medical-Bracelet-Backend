from pydantic import BaseModel
from typing import List

class SecondData(BaseModel):
    x: List[float]
    y: List[float]
    z: List[float]

class SensorData(BaseModel):
    movement: List[SecondData]