import sys
import requests
import logging
import base64

# ロギングの設定
logging.basicConfig(filename='aqua_monitor.log', level=logging.DEBUG, 
                    format='%(asctime)s - %(levelname)s - %(message)s')

def send_line_notification(message):
    """LINEにメッセージを送信する関数"""
    LINE_NOTIFY_API_URL = "https://notify-api.line.me/api/notify"
    LINE_NOTIFY_TOKEN = "LINE-NotifyTokenHere"

    headers = {"Authorization": f"Bearer {LINE_NOTIFY_TOKEN}"}
    data = {"message": message}
    
    try:
        response = requests.post(LINE_NOTIFY_API_URL, headers=headers, data=data)
        response.raise_for_status()  # エラーがあれば例外を発生させる
        logging.info("LINEにメッセージが送信されました。")
    except requests.exceptions.RequestException as e:
        logging.error(f"メッセージ送信失敗: {e}")
        logging.error(f"レスポンス: {response.status_code}, {response.text}")

def judge_value(value, thresholds):
    """値が閾値範囲内かどうかを判定する関数。"""
    lower, upper = thresholds
    return "normal" if lower <= value <= upper else "abnormal"

THRESHOLDS = {
    'pH': (7.9, 8.2),
    'DO': (6, 10),
    'Temperature': (28, 29),
    'Salinity': (1.5, 3.5),
}

if __name__ == "__main__":
    try:
        ph = float(sys.argv[1])
        do = float(sys.argv[2])
        temperature = float(sys.argv[3])
        salinity = float(sys.argv[4])
        encoded_notes = sys.argv[5] if len(sys.argv) > 5 else ""

        # Base64でエンコードされた文字列をデコードする
        notes = base64.b64decode(encoded_notes).decode('utf-8') if encoded_notes else "備考なし"

        logging.info(f"入力値: pH={ph}, DO={do}, Temperature={temperature}, Salinity={salinity}, Notes={notes}")

        results = {
            'pH': {'value': ph, 'status': judge_value(ph, THRESHOLDS['pH']), 'thresholds': THRESHOLDS['pH']},
            'DO': {'value': do, 'status': judge_value(do, THRESHOLDS['DO']), 'thresholds': THRESHOLDS['DO']},
            'Temperature': {'value': temperature, 'status': judge_value(temperature, THRESHOLDS['Temperature']), 'thresholds': THRESHOLDS['Temperature']},
            'Salinity': {'value': salinity, 'status': judge_value(salinity, THRESHOLDS['Salinity']), 'thresholds': THRESHOLDS['Salinity']}
        }

        abnormal_results = []
        for param, data in results.items():
            value = data['value']
            status = data['status']
            thresholds = data['thresholds']
            lower, upper = thresholds
            logging.info(f"{param}: {status} ({value})")
            if status == "abnormal":
                abnormal_results.append(f"{param}: {value}（正常範囲: {lower}-{upper}）")

        if abnormal_results:
            message = "\n⚠️ 異常検知:\n" + "\n".join(abnormal_results) + f"\n\n備考: {notes}"
            send_line_notification(message)
        else:
            logging.info("異常は検出されませんでした。")

    except Exception as e:
        logging.exception("予期しないエラーが発生しました:")
        send_line_notification(f"AquaMonitor.pyでエラーが発生しました: {str(e)}")
