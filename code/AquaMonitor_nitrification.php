<!DOCTYPE html>
<html>
<head>
  <title>水質モニタ</title>
</head>
<body>
  <h1>水質データ入力</h1>
  <form method="post" action="AquaMonitor_nitrification.php">
    <label for="ph">pH:</label>
    <input type="number" step="0.01" name="ph" id="ph" required><br>

    <label for="do">DO:</label>
    <input type="number" step="0.01" name="do" id="do" required><br>

    <label for="temperature">水温:</label>
    <input type="number" step="0.01" name="temperature" id="temperature" required><br>

    <label for="salinity">塩分濃度:</label>
    <input type="number" step="0.01" name="salinity" id="salinity" required><br>

    <label for="nh4">NH4:</label>
    <input type="number" step="0.01" name="nh4" id="nh4" required><br>

    <label for="no2">NO2:</label>
    <input type="number" step="0.01" name="no2" id="no2" required><br>

    <label for="no3">NO3:</label>
    <input type="number" step="0.01" name="no3" id="no3" required><br>

    <label for="ca">Ca:</label>
    <input type="number" step="0.01" name="ca" id="ca" required><br>

    <label for="al">Al:</label>
    <input type="number" step="0.01" name="al" id="al" required><br>

    <label for="mg">Mg:</label>
    <input type="number" step="0.01" name="mg" id="mg" required><br>

    <button type="submit" name="submit">送信</button>
  </form>

  <?php
  if (isset($_POST['submit'])) {
    // 入力値を取得
    $ph = $_POST['ph'];
    $do = $_POST['do'];
    $temperature = $_POST['temperature'];
    $salinity = $_POST['salinity'];
    $nh4 = $_POST['nh4'];
    $no2 = $_POST['no2'];
    $no3 = $_POST['no3'];
    $ca = $_POST['ca'];
    $al = $_POST['al'];
    $mg = $_POST['mg'];

    // Conda仮想環境のPythonインタープリタのパス
    $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python'; 
    // サーバー上のPythonスクリプトのパス
    $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/AquaMonitor_nitrification.py';  // スクリプト名を修正

    // コマンドライン引数を構築
    $command = "$pythonEnv $scriptPath {$ph} {$do} {$temperature} {$salinity} {$nh4} {$no2} {$no3} {$ca} {$al} {$mg}";

    // Pythonスクリプトを実行
    $output = shell_exec($command);

    // 実行結果を表示
    echo "<pre>{$output}</pre>"; 
  }
  ?>
</body>
</html>