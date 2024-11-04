import matplotlib.pyplot as plt
import pandas as pd
import os
import sys

def main():
    year = sys.argv[1]
    period = sys.argv[2]  # 1st, 2nd, 3rd
    target_type = sys.argv[3]  # len, wei

    if target_type == 'len':
        target_type_label = 'Length'
    elif target_type == 'wei':
        target_type_label = 'Weight'
    else:
        print(f"Invalid target_type: {target_type}")
        sys.exit(1)

    base_dir = '/home/xs29345872/globalsteptest.com/public_html/katsurao'
    csv_dir = base_dir  # CSVファイルは base_dir と同じ場所にあります
    image_dir = os.path.join(base_dir, 'image')
    os.makedirs(image_dir, exist_ok=True)

    csv_base_name = f"{year}-{period}-{target_type}-"

    suisou_name_lists = [
        ['1-1', '1-2'],
        ['2-1', '2-2'],
        ['3-1A', '3-1B', '3-1C', '3-2A', '3-2B', '3-2C']
    ]

    seichou_df_lists = []

    # データ読み込みループ
    for suisou_name_list in suisou_name_lists:
        for suisou_name in suisou_name_list:
            csv_file_name = csv_base_name + suisou_name + '.csv'
            csv_file_path = os.path.join(csv_dir, csv_file_name)
            if os.path.exists(csv_file_path):
                seichou_df = pd.read_csv(csv_file_path)
                seichou_df_lists.append((suisou_name, seichou_df))
            else:
                print(f"File not found: {csv_file_path}")

    if not seichou_df_lists:
        print("No data files found.")
        sys.exit(1)

    # 全ての水槽のデータを一つのグラフにプロット
    plt.figure(figsize=(12, 8))
    plt.title(f"Comparison of {target_type_label} across all tanks")

    # 各水槽のデータをプロット
    for suisou_name, seichou_df in seichou_df_lists:
        if target_type in seichou_df.columns:
            plt.plot(seichou_df['PL'], seichou_df[target_type], label=suisou_name)
        else:
            print(f"Column {target_type} not found in data for {suisou_name}")

    # グラフの設定
    plt.xlabel("PL")
    plt.ylabel(target_type_label)
    plt.legend(loc="best")
    plt.grid(True)

    # グラフを保存
    image_path = os.path.join(image_dir, f'growth_line_graph_{year}_{period}_{target_type}.png')
    plt.savefig(image_path)
    print(f"Graph saved as: {image_path}")

if __name__ == "__main__":
    main()