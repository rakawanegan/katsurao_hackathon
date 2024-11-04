import matplotlib.pyplot as plt
import pandas as pd
import numpy as np
import os
import sys
import traceback

def main():
    try:
        year = sys.argv[1]
        period = sys.argv[2]
        target_type = sys.argv[3]

        # target_typeに応じた横軸の範囲を設定
        x_range_mapping = {
            'pH': (7.0, 8.5),
            'DO': (5.0, 8.0),
            'temperature': (26.0, 31.0),
            'salinity': (1.7, 2.2)
        }

        base_dir = '/home/xs29345872/globalsteptest.com/public_html/katsurao'
        image_dir = os.path.join(base_dir, 'image')
        os.makedirs(image_dir, exist_ok=True)

        csv_base_name = f"{year}-{period}-"

        suisou_name_lists = [
            ['1-1', '1-2', '1-nitrification'],
            ['2-1', '2-2', '2-nitrification'],
            ['3-1A', '3-1B', '3-1C', '3-2A', '3-2B', '3-2C', '3-nitrification']
        ]

        suisitu_df_lists = [[], [], []]

        # カラム名のマッピング
        column_mapping = {
            'ph': 'pH',
            'DO': 'DO',
            'temp.': 'temperature',
            'salinity': 'salinity'
        }

        for i, suisou_name_list in enumerate(suisou_name_lists):
            for suisou_name in suisou_name_list:
                csv_file_name = csv_base_name + suisou_name + '.csv'
                csv_file_path = os.path.join(base_dir, csv_file_name)

                if os.path.exists(csv_file_path):
                    # CSVファイルを読み込む（ヘッダーあり）
                    suisitu_df = pd.read_csv(csv_file_path, parse_dates=[0])
                    suisitu_df.set_index(suisitu_df.columns[0], inplace=True)
                    
                    # カラム名を統一
                    suisitu_df.rename(columns=column_mapping, inplace=True)
                    
                    # 必要な列のみを選択
                    suisitu_df = suisitu_df[['pH', 'DO', 'temperature', 'salinity']]
                    
                    # 数値型に変換
                    for col in suisitu_df.columns:
                        suisitu_df[col] = pd.to_numeric(suisitu_df[col], errors='coerce')
                    
                    suisitu_df_lists[i].append(suisitu_df)
                else:
                    suisitu_df_lists[i].append(pd.DataFrame())

        if all(all(df.empty for df in df_list) for df_list in suisitu_df_lists):
            return

        # ヒストグラムの作成ループ
        for i, suisitu_df_list in enumerate(suisitu_df_lists):
            if any(not df.empty for df in suisitu_df_list):
                fig, axes = plt.subplots(1, len(suisitu_df_list), figsize=(5 * len(suisitu_df_list), 5))
                fig.suptitle(f"Histogram for {target_type} (Kei: {i+1})")

                if len(suisitu_df_list) == 1:
                    axes = [axes]

                for j, suisitu_df in enumerate(suisitu_df_list):
                    if not suisitu_df.empty and target_type in suisitu_df.columns:
                        tempdf = suisitu_df[target_type].dropna()
                        if not tempdf.empty:
                            n, bins, patches = axes[j].hist(tempdf, bins=50, edgecolor='black')
                            axes[j].set_title(f"{suisou_name_lists[i][j]}")
                            axes[j].set_xlabel(target_type)
                            axes[j].set_ylabel('Frequency')
                            
                            # target_typeに応じたx軸の範囲を設定
                            if target_type in x_range_mapping:
                                x_min, x_max = x_range_mapping[target_type]
                                axes[j].set_xlim(x_min, x_max)
                        else:
                            axes[j].set_visible(False)
                    else:
                        axes[j].set_visible(False)

                plt.tight_layout(rect=[0, 0, 1, 0.95])

                image_path = os.path.join(image_dir, f'histogram_kei_{i+1}.png')
                plt.savefig(image_path)
                plt.close(fig)
                print("Script executed successfully")

    except Exception as e:
        print(f"An error occurred: {str(e)}")
        print("Traceback:")
        print(traceback.format_exc())
        sys.exit(1)

if __name__ == "__main__":
    main()