import sys
import os

def judge_value(value, thresholds):
    lower, upper = thresholds
    return '0' if lower <= value <= upper else '1'

def check_abnormalities(ph, do, temperature, salinity):
    THRESHOLDS = {
        'pH': (7.9, 8.2),
        'DO': (6, 10),
        'Temperature': (28, 29),
        'Salinity': (1.5, 3.5),
    }
    
    results = [
        judge_value(ph, THRESHOLDS['pH']),
        judge_value(do, THRESHOLDS['DO']),
        judge_value(temperature, THRESHOLDS['Temperature']),
        judge_value(salinity, THRESHOLDS['Salinity'])
    ]
    return ','.join(results)

if __name__ == "__main__":
    try:
        ph = float(sys.argv[1])
        do = float(sys.argv[2])
        temperature = float(sys.argv[3])
        salinity = float(sys.argv[4])
        
        # デバッグ情報をファイルに書き込み
        current_dir = os.path.dirname(os.path.abspath(__file__))
        debug_file_path = os.path.join(current_dir, 'suisitsu.txt')
        
        with open(debug_file_path, 'w') as f:
            f.write(f"pH: {ph}\n")
            f.write(f"DO: {do}\n")
            f.write(f"Temperature: {temperature}\n")
            f.write(f"Salinity: {salinity}\n")
        
        results = check_abnormalities(ph, do, temperature, salinity)
        print(results)
    except Exception as e:
        # エラーが発生した場合、エラー情報をファイルに書き込み
        with open(debug_file_path, 'w') as f:
            f.write(f"Error occurred: {str(e)}\n")
            f.write(f"Arguments: {sys.argv}\n")
        print("Error occurred. Check suisitsu.txt for details.")