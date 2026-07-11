import math
import pandas as pd
from app.models.fall.ml_predictor import FallPredictor

predictor = FallPredictor()

def calculate_magnitude(x, y, z):
    return math.sqrt(x*x + y*y + z*z)

def calculate_std(values):
    if len(values) == 0:
        return 0
    mean = sum(values) / len(values)
    variance = sum((x - mean)**2 for x in values) / len(values)
    return math.sqrt(variance)

def analyze_fall_data(accel_x: list, accel_y: list, accel_z: list) -> bool:
    ax_std = calculate_std(accel_x)
    ay_std = calculate_std(accel_y)
    az_std = calculate_std(accel_z)

    ax = accel_x[-1]
    ay = accel_y[-1]
    az = accel_z[-1]
    smv = calculate_magnitude(ax, ay, az) - 9.7 

    features = pd.DataFrame(
        [[smv, ax_std, ay_std, az_std]],
        columns=['smv', 'ax_std', 'ay_std', 'az_std']
    )

    # التنبؤ
    return predictor.predict(features)