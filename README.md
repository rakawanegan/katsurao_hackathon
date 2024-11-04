# HANERU葛尾様のための水質検査システムの提案

## プロジェクト概要
このプロジェクトは水質データの自動入力、異常値検出、通知機能、データ可視化を含む水質検査システムを提供します。  
スマートフォンから計器の写真を撮るだけでデータ入力が可能で、異常値は自動的に検出され、LINEに通知されます。

## メンバー
| 名前                | 担当箇所          |
|--------------------|-----------------|
| @rakawanegan       | データ可視化、発表 |
| @Tamasaki217       | 異常検知         |
| @naoki-sakamoto820 | デジタル文字抽出  |
| @NinomiyaOsuke     | フロントエンド    |


## 機能
- 画像認識: 計測器の画面をスマートフォンカメラで認識し、値を自動入力。
- 異常値検出と通知: 異常なデータは再検査を促し、LINEで通知。
- データ可視化: 任意の変数・期間を選択して自動的にグラフを生成。

## 技術スタック
- 言語: Python、PHP

## 使用方法
- データ入力ページにアクセスし、計測器の画像をアップロードすると、自動で値が入力されます。
- 異常値が検出された場合、再検査を促す通知がLINEに送信されます。
- 管理画面からデータを可視化することができます。



## 使用方法
`code`ディレクトリ下のファイルをXサーバを用いてビルドすることで使用することができます。  
デモ動画は[Youtbe](https://www.youtube.com/watch?v=hQKnW6Xyz0A)で確認できます。