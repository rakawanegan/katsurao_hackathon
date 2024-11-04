# -*- coding: utf-8 -*-
"""水質検査（硝化槽）_異常検知.ipynb

Automatically generated by Colab.

Original file is located at
    https://colab.research.google.com/drive/10frNJIspwylpgzq2eb7jSrZdv-UcWh2D
"""

import sys
import requests
import base64
import logging

# ロギングの設定
logging.basicConfig(filename='aqua_monitor_nitrification.log', level=logging.DEBUG, 
                    format='%(asctime)s - %(levelname)s - %(message)s')

def send_line_notification(message):
    """LINEにメッセージを送信する関数"""
    # LINE NotifyのAPI URL
    LINE_NOTIFY_API_URL = "https://notify-api.line.me/api/notify"

    # 発行したLINE Notifyトークンをここに設定
    LINE_NOTIFY_TOKEN = "LINE-NotifyToken"

    headers = {
        "Authorization": f"Bearer {LINE_NOTIFY_TOKEN}"
    }
    data = {
        "message": message
    }
    try:
        response = requests.post(LINE_NOTIFY_API_URL, headers=headers, data=data)
        response.raise_for_status()
        logging.info("LINEにメッセージが送信されました。")
    except requests.exceptions.RequestException as e:
        logging.error(f"メッセージ送信失敗: {e}")
        logging.error(f"レスポンス: {response.status_code}, {response.text}")

def judge_value(value, thresholds):
    """値が閾値範囲内かどうかを判定する関数。"""
    lower, upper = thresholds
    if lower <= value <= upper:
        return "normal"  # 正常
    else:
        return "abnormal"  # 異常

# 硝化槽用の閾値を定義
THRESHOLDS = {
    'pH': (7.9, 8.2),
    'DO': (6, 10),  # 溶存酸素 (mg/L)
    'Temperature': (28, 29),  # 温度 (°C)
    'Salinity': (1.5, 3.5),  # 塩分濃度 (PSU)
    'NH4': (0, 5),  # アンモニウム (mg/L)
    'NO2': (1.5, 2.5),  # 亜硝酸塩 (mg/L)
    'NO3': (15, 20),  # 硝酸塩 (mg/L)
    'Ca': (280, 300),  # カルシウム (mg/L)
    'Al': (180, 200),  # アルミニウム (mg/L)
    'Mg': (700, 800),  # マグネシウム (mg/L)
}

if __name__ == "__main__":
    try:
        # コマンドライン引数から順番に取得
        ph = float(sys.argv[1]) 
        do = float(sys.argv[2]) 
        temperature = float(sys.argv[3]) 
        salinity = float(sys.argv[4])
        nh4 = float(sys.argv[5])
        no2 = float(sys.argv[6])
        no3 = float(sys.argv[7])
        ca = float(sys.argv[8])
        al = float(sys.argv[9])
        mg = float(sys.argv[10])
        encoded_notes = sys.argv[11] if len(sys.argv) > 11 else ""

        logging.info(f"Received encoded notes: {encoded_notes}")

        # Base64でエンコードされた文字列をデコードする
        if encoded_notes:
            # パディングを追加（必要な場合）
            encoded_notes += '=' * ((4 - len(encoded_notes) % 4) % 4)
            notes = base64.b64decode(encoded_notes).decode('utf-8', errors='ignore')
        else:
            notes = "備考なし"

        logging.info(f"入力値: pH={ph}, DO={do}, Temperature={temperature}, Salinity={salinity}, " +
                     f"NH4={nh4}, NO2={no2}, NO3={no3}, Ca={ca}, Al={al}, Mg={mg}, Notes={notes}")

        # それぞれの値を判定
        results = {}
        results['pH'] = {'value': ph, 'status': judge_value(ph, THRESHOLDS['pH']), 'thresholds': THRESHOLDS['pH']}
        results['DO'] = {'value': do, 'status': judge_value(do, THRESHOLDS['DO']), 'thresholds': THRESHOLDS['DO']}
        results['Temperature'] = {'value': temperature, 'status': judge_value(temperature, THRESHOLDS['Temperature']), 'thresholds': THRESHOLDS['Temperature']}
        results['Salinity'] = {'value': salinity, 'status': judge_value(salinity, THRESHOLDS['Salinity']), 'thresholds': THRESHOLDS['Salinity']}
        results['NH4'] = {'value': nh4, 'status': judge_value(nh4, THRESHOLDS['NH4']), 'thresholds': THRESHOLDS['NH4']}
        results['NO2'] = {'value': no2, 'status': judge_value(no2, THRESHOLDS['NO2']), 'thresholds': THRESHOLDS['NO2']}
        results['NO3'] = {'value': no3, 'status': judge_value(no3, THRESHOLDS['NO3']), 'thresholds': THRESHOLDS['NO3']}
        results['Ca'] = {'value': ca, 'status': judge_value(ca, THRESHOLDS['Ca']), 'thresholds': THRESHOLDS['Ca']}
        results['Al'] = {'value': al, 'status': judge_value(al, THRESHOLDS['Al']), 'thresholds': THRESHOLDS['Al']}
        results['Mg'] = {'value': mg, 'status': judge_value(mg, THRESHOLDS['Mg']), 'thresholds': THRESHOLDS['Mg']}

        # 異常な項目をまとめる
        abnormal_results = []
        for param, data in results.items():
            value = data['value']
            status = data['status']
            thresholds = data['thresholds']
            lower, upper = thresholds
            logging.info(f"{param}: {status} ({value})")
            if status == "abnormal":
                abnormal_results.append(f"{param}: {value}（正常範囲: {lower}-{upper}）")

        # 異常があればLINEに通知
        if abnormal_results:
            message = "\n⚠️ 異常検知:\n" + "\n".join(abnormal_results) + f"\n\n備考: {notes}"
            send_line_notification(message)
        else:
            logging.info("異常は検出されませんでした。")

    except Exception as e:
        logging.exception("予期しないエラーが発生しました:")
        send_line_notification(f"AquaMonitor_nitrification.pyでエラーが発生しました: {str(e)}")