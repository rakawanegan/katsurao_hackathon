import matplotlib.pyplot as plt
import cv2
import numpy as np
import pandas as pd
from PIL import Image, ImageOps
import sys
import pyocr
import pyocr.builders
import base64
import os

# 画像ファイルのパスを直接指定
image_path = '/home/xs29345872/globalsteptest.com/public_html/katsurao/image/measurement.png'

# 画像ファイルを読み込む
img = cv2.imread(image_path)
# 元画像を各パラメータの表示領域ごとにクロップする関数
def crop_image(img):

    top_ph = 1273
    bottom_ph = 1564
    left_ph = 1073
    right_ph = 1783

    top_temp1 = 1576
    bottom_temp1 = 1727
    left_temp1 = 1472
    right_temp1 = 1770

    top_salt = 1811
    bottom_salt = 2003
    left_salt = 868
    right_salt = 1251

    top_temp2 = 2037
    bottom_temp2 = 2184
    left_temp2 = 702
    right_temp2 = 1014

    top_do = 1818
    bottom_do = 2002
    left_do = 1683
    right_do = 1951

    top_temp3 = 2034
    bottom_temp3 = 2184
    left_temp3 = 1474
    right_temp3 = 1749

    # リサイズによりサイズを固定
    img_ph = cv2.resize(img[top_ph : bottom_ph, left_ph : right_ph], (275, 150))
    img_temp1 = cv2.resize(img[top_temp1 : bottom_temp1, left_temp1 : right_temp1], (275, 150))
    img_salt = cv2.resize(img[top_salt : bottom_salt, left_salt : right_salt], (275, 150))
    img_temp2 = cv2.resize(img[top_temp2 : bottom_temp2, left_temp2 : right_temp2], (275, 150))
    img_do = cv2.resize(img[top_do : bottom_do, left_do : right_do], (275, 150))
    img_temp3 = cv2.resize(img[top_temp3 : bottom_temp3, left_temp3 : right_temp3], (275, 150))

    return img_ph, img_temp1, img_salt, img_temp2, img_do, img_temp3

# x,xxの形式のパラメータの表示領域画像を各桁に分割
def split_image_xpointxx(img_cropped):

    segment_width = 85
    segments = []
    for i in range(4):
        if i < 1:
          left = i * segment_width
          right = (i + 1) * segment_width - 1
          segment = img_cropped[:, left:right]
          segments.append(segment)
        elif i == 1:
          continue
        elif i == 2:
          left = (i - 1) * segment_width + 20
          right = i * segment_width + 20 - 1
          segment = img_cropped[:, left:right]
          segment = np.concatenate([img_cropped[:, left+10:right], img_cropped[:, left:left+10-1]], axis=1) 
          segments.append(segment)
        else:
          left = (i - 1) * segment_width + 20
          right = i * segment_width + 20 - 1
          segment = img_cropped[:, left:right]
          segments.append(segment)

    return segments 

# xx.xの形式のパラメータの表示領域画像を各桁に分割
def split_image_xxpointx(img_cropped):

    segment_width = 85
    segments = []
    for i in range(4):
        if i < 2:
          left = i * segment_width
          right = (i + 1) * segment_width - 1
          segment = img_cropped[:, left:right]
          segments.append(segment)
        elif i == 2:
          continue
        else:
          left = (i - 1) * segment_width + 20
          right = i * segment_width + 20 - 1
          segment = img_cropped[:, left:right]
          segments.append(segment)

    return segments

# x.xの形式のパラメータの表示領域画像を各桁に分割
def split_image_xpointx(img_cropped):

    segment_width = 125
    segments = []
    for i in range(3):
        if i < 1:
          left = i * segment_width
          right = (i + 1) * segment_width - 1
          segment = img_cropped[:, left:right]
          segments.append(segment)
        elif i == 1:
          continue
        else:
          left = (i - 1) * segment_width + 20
          right = i * segment_width + 20 - 1
          segment = img_cropped[:, left:right]
          segments.append(segment)

    return segments

def preprocess_image(img):
    """
    画像をグレースケール化，二値化し，モルフォロジー変換によるノイズ処理を行う

    Args:
        img (numpy.ndarray): 入力画像（BGR形式）

    Returns:
        numpy.ndarray: ノイズ処理された二値化画像
    """
    # グレースケール化
    img_gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    img_blur = cv2.GaussianBlur(img_gray, (5, 5), 0)

    # 二値化
    thresh_value, thresh_img = cv2.threshold(img_blur, 0, 255, cv2.THRESH_OTSU)

    # ノイズ処理（モルフォロジー変換）
    kernel = np.ones((5, 5), np.uint8)
    img_opening = cv2.morphologyEx(thresh_img, cv2.MORPH_OPEN, kernel)
    

    return img_opening

def preprocess_segments(segments):

    segments_preprocessed = []
    for segment in segments:
        segments_preprocessed.append(preprocess_image(segment))

    return segments_preprocessed


# 画像を各パラメータの表示領域ごとにクロップ
img_ph, img_temp1, img_salt, img_temp2, img_do, img_temp3 = crop_image(img)

# 各パラメータの表示領域画像を各桁に分割
segments_ph = split_image_xpointxx(img_ph)
segments_temp1 = split_image_xxpointx(img_temp1)
segments_salt = split_image_xpointxx(img_salt)
segments_temp2 = split_image_xxpointx(img_temp2)
segments_do = split_image_xpointx(img_do)
segments_temp3 = split_image_xxpointx(img_temp3)

# 前処理
segments_preprocessed_ph = preprocess_segments(segments_ph)
segments_preprocessed_temp1 = preprocess_segments(segments_temp1)
segments_preprocessed_salt = preprocess_segments(segments_salt)
segments_preprocessed_temp2 = preprocess_segments(segments_temp2)
segments_preprocessed_do = preprocess_segments(segments_do)
segments_preprocessed_temp3 = preprocess_segments(segments_temp3)


cv2.imwrite('/home/xs29345872/globalsteptest.com/public_html/katsurao/image/image1.png', segments_preprocessed_ph[0])
cv2.imwrite('/home/xs29345872/globalsteptest.com/public_html/katsurao/image/image2.png', segments_preprocessed_ph[1])
cv2.imwrite('/home/xs29345872/globalsteptest.com/public_html/katsurao/image/image3.png', segments_preprocessed_ph[2])
cv2.imwrite('/home/xs29345872/globalsteptest.com/public_html/katsurao/image/image4.png', segments_preprocessed_do[0])
cv2.imwrite('/home/xs29345872/globalsteptest.com/public_html/katsurao/image/image5.png', segments_preprocessed_do[1])


#OCRの準備
tools = pyocr.get_available_tools()
if len(tools) == 0:
    #print("No OCR tool found")
    sys.exit(1)
# The tools are returned in the recommended order of usage
tool = tools[0]
#print("Will use tool '%s'" % (tool.get_name()))
# Ex: Will use tool 'libtesseract'

langs = tool.get_available_languages()
#print("Available languages: %s" % ", ".join(langs))
lang = langs[0] # if len(langs) != 0 else None
#print("Will use lang '%s'" % (lang))

#OpenCV型からPIL型に変換する関数
def cv2pil(image):
    ''' OpenCV型 -> PIL型 '''
    new_image = image.copy()
    if new_image.ndim == 2:  # モノクロ
        pass
    elif new_image.shape[2] == 3:  # カラー
        new_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    elif new_image.shape[2] == 4:  # 透過
        new_image = cv2.cvtColor(image, cv2.COLOR_BGRA2RGBA)
    new_image = Image.fromarray(new_image)
    return new_image

def invert_image(pil_img):
    """
    PIL画像を白黒反転する。

    Args:
        pil_img (PIL.Image.Image): 入力PIL画像

    Returns:
        PIL.Image.Image: 白黒反転されたPIL画像
    """
    # 画像がRGBまたはLモードかを確認し、そうでない場合は変換する
    if pil_img.mode == 'RGB':
        inverted_img = ImageOps.invert(pil_img)
    elif pil_img.mode == 'L':
        inverted_img = ImageOps.invert(pil_img)
    else:
        # 画像がRGBまたはLモードでない場合は、まずLモードに変換する
        pil_img = pil_img.convert('L')
        inverted_img = ImageOps.invert(pil_img)

    return inverted_img





def ocr_image(processed_img, lang="eng", layout=8):
    """
    画像に対してOCRを実行してテキストを抽出する

    Args:
        processed_img (numpy.ndarray): OpenCVの前処理済画像データ
        lang (str): OCRの言語設定．デフォルトは "eng"
        layout (int): Tesseractのレイアウト設定．デフォルトは8

    Returns:
        str: 抽出されたテキスト
    """
    # 画像をPIL形式に変換
    temp_pil_im = cv2pil(processed_img)

    # 白黒反転
    temp_pil_im = invert_image(temp_pil_im)

    # 現在のDPIを取得
    current_dpi = temp_pil_im.info.get('dpi', (72, 72))  # デフォルトは72 DPIと仮定
    # 300 DPIに変更する
    new_dpi = (300, 300)
    # 画像サイズを変更するための計算
    width, height = temp_pil_im.size
    new_width = int(width * (new_dpi[0] / current_dpi[0]))
    new_height = int(height * (new_dpi[1] / current_dpi[1]))
    # 画像のリサイズ
    temp_pil_im = temp_pil_im.resize((new_width, new_height), Image.Resampling.LANCZOS)

    # OCRツールの初期化
    tool = pyocr.get_available_tools()[0]

    # OCRを実行してテキストを抽出
    txt = tool.image_to_string(
        temp_pil_im,
        lang=lang,
        builder=pyocr.builders.DigitBuilder(tesseract_layout=layout)
    )

    return txt

# 各桁のOCR結果を適切な位置に小数点を追加して統合
def ocr_segments(segments, param_name):

    ocr_results = []
    for segment in segments:
        ocr_result = ocr_image(segment)
        ocr_results.append(ocr_result)

    if param_name == 'ph' or param_name == 'salt':
        param_value = ocr_results[0] + '.' + ocr_results[1] + ocr_results[2]
    elif param_name == 'temp':
        param_value = ocr_results[0] + ocr_results[1] + '.' + ocr_results[2]
    elif param_name == 'do':
        param_value = ocr_results[0] + '.' + ocr_results[1]

    return param_value


# OCR実行
ph_result = ocr_segments(segments_preprocessed_ph, 'ph')
temp1_result = ocr_segments(segments_preprocessed_temp1, 'temp')
salt_result = ocr_segments(segments_preprocessed_salt, 'salt')
temp2_result = ocr_segments(segments_preprocessed_temp2, 'temp')
do_result = ocr_segments(segments_preprocessed_do, 'do')
temp3_result = ocr_segments(segments_preprocessed_temp3, 'temp')

# 結果をスペースで区切って出力
print(ph_result, temp1_result, salt_result, temp2_result, do_result, temp3_result)
