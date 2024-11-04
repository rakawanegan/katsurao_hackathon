<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $year = escapeshellarg($_POST['year']);
    $period = escapeshellarg($_POST['period']);
    $target_type = escapeshellarg($_POST['target_type']);
    $graph_type = $_POST['graph_type'];

    $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python';
    
    // グラフの種類に応じてPythonスクリプトを選択
    if ($graph_type === 'histogram') {
        $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/plot_hist_suisitu.py';
    } else {
        $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/plot_line_suisitu.py';
    }

    $command = "$pythonEnv $scriptPath $year $period $target_type 2>&1";
    
    $output = array();
    $return_var = 0;
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        echo "<div class='error'>エラーが発生しました。</div>";
    }
}
?>

<!DOCTYPE html>
<?php
  session_start();
  require_once('header.php'); 
?>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>水質データ可視化</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        body::before {
            content: "";
            display: block;
            height: 60px;
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        label {
            display: inline-block;
            width: 120px;
            margin-bottom: 10px;
        }

        select {
            width: 200px;
            padding: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        input[type="submit"] {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background-color: #2980b9;
        }

        .image-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 20px;
        }

        .image-item {
    flex-basis: calc(100% - 20px);  /* 元の33.333%から50%に増やすことで項目を大きく表示 */
    min-width: 400px;              /* 最小幅を300pxから400pxに増やす */
    background-color: #fff;
    padding: 20px;                 /* パディングを15pxから20pxに増やす */
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}


        h2 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.2em;
        }

        img {
            width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-bottom: 10px;
        }

        a {
            display: inline-block;
            margin-top: 10px;
            color: #3498db;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        pre {
            background-color: #fff;
            padding: 15px;
            border-radius: 3px;
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .image-item {
                flex-basis: 100%;
            }
        }
        .radio-group {
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }
    .radio-group label {
        margin-right: 20px;
        display: flex;
        align-items: center;
        white-space: nowrap;
    }
    .radio-group input[type="radio"] {
        margin-right: 5px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: inline-block;
        width: 120px;
        margin-bottom: 5px;
    }
    .form-group select {
        width: 200px;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    </style>
</head>
<body>
    <h1>水質データ可視化</h1>
    <form method="post">
        <div class="radio-group">
            <label>グラフの種類:</label>
            <label><input type="radio" name="graph_type" value="histogram" checked> ヒストグラム</label>
            <label><input type="radio" name="graph_type" value="line"> 折れ線グラフ</label>
        </div>

        <label for="year">年:</label>
        <select name="year" id="year">
            <?php
            for ($i = 2014; $i <= 2024; $i++) {
                echo "<option value=\"$i\">$i</option>";
            }
            ?>
        </select><br><br>

        <label for="period">期間:</label>
        <select name="period" id="period">
            <option value="1st">1st</option>
            <option value="2st">2nd</option>
            <option value="3st">3rd</option>
        </select><br><br>

        <label for="target_type">対象タイプ:</label>
        <select name="target_type" id="target_type">
            <option value="pH">pH</option>
            <option value="DO">DO</option>
            <option value="temperature">温度</option>
            <option value="salinity">塩分</option>
        </select><br><br>

        <input type="submit" value="グラフ生成">
    </form>

    <?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $image_dir = '/home/xs29345872/globalsteptest.com/public_html/katsurao/image';
    $images_found = false;

    for ($i = 1; $i <= 3; $i++) {
        if ($_POST['graph_type'] === 'histogram') {
            $image_path = "$image_dir/histogram_kei_$i.png";
            $image_name = "histogram_kei_$i.png";
            $title = "系列 $i のヒストグラム";
        } else {
            $image_path = "$image_dir/line_plot_kei_$i.png";
            $image_name = "line_plot_kei_$i.png";
            $title = "系列 $i の折れ線グラフ";
        }

        if (file_exists($image_path)) {
            $images_found = true;
            $timestamp = filemtime($image_path);
            $image_url = "/katsurao/image/$image_name?t=$timestamp";
            
            echo "<h2>$title</h2>";
            echo "<img src='$image_url' alt='Graph for Kei $i'>";
            echo "<br><a href='$image_url' download='$image_name'>画像をダウンロード</a><br><br>";
        }
    }

    if (!$images_found) {
        echo "<p>指定されたパラメータに対応するデータが見つかりませんでした。</p>";
    }
}
?>
</body>
</html>