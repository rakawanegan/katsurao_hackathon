<!DOCTYPE html>
<html>
<head>
  <title>水質モニタ</title>
</head>
<body>
  <h1>水質データ入力</h1>
  <form method="post" action="AquaMonitor.php"> 
    <label for="ph">pH:</label>
    <input type="number" step="0.01" name="ph" id="ph" required><br>

    <label for="do">DO:</label>
    <input type="number" step="0.01" name="do" id="do" required><br>

    <label for="temperature">水温:</label>
    <input type="number" step="0.01" name="temperature" id="temperature" required><br>

    <label for="salinity">塩分濃度:</label>
    <input type="number" step="0.01" name="salinity" id="salinity" required><br>

    <button type="submit" name="submit">送信</button>
  </form>

  <?php
  if (isset($_POST['submit'])) {
    // 入力値を取得
    $ph = $_POST['ph'];
    $do = $_POST['do'];
    $temperature = $_POST['temperature'];
    $salinity = $_POST['salinity'];

    // Conda仮想環境のPythonインタープリタのパス
    $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python'; 
    // サーバー上のPythonスクリプトのパス
    $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/AquaMonitor.py';  // AquaMonitor.pyのパスに修正

    // コマンドライン引数を構築
    $command = "$pythonEnv $scriptPath {$ph} {$do} {$temperature} {$salinity}";

    // Pythonスクリプトを実行
    $output = shell_exec($command);

    // 実行結果を表示
    echo "<pre>{$output}</pre>"; 
  }
  ?>
</body>
</html>