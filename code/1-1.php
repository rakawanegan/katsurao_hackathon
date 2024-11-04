<?php
// セッション開始
session_start();

// ログイン状態の確認
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  header("Location: login.php");
  exit;
}

//require_once('header.php'); 

// 最新のCSVファイル名を取得する関数 (システムは1系固定)
function getLatestCSVForSystem1() {
    $currentYear = date('Y');
    $previousYear = $currentYear;
    $pattern = sprintf('/(%s)-(?:1st|2nd|3rd|4th|5th)-1-1\.csv$/', $previousYear . '|' . $currentYear);
    $files = glob('*.csv');
    $matchedFiles = array_filter($files, function ($file) use ($pattern) {
        return preg_match($pattern, $file);
    });
    usort($matchedFiles, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    return reset($matchedFiles);
}
// ユーザー名を取得する関数
function getUserName($userId) {
    $memberCsvPath = __DIR__ . '/csv/member.csv'; 
    if (($handle = fopen($memberCsvPath, "r")) !== FALSE) {
        // ヘッダー行をスキップ
        fgetcsv($handle, 1000, ",");
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($data[1] == $userId) {
                fclose($handle);
                return $data[3]; // nameカラムの値を返す
            }
        }
        fclose($handle);
    }
    return "Unknown User"; // ユーザーが見つからない場合のデフォルト値
}

// 異常検知を行う関数
function detectAnomalies($ph, $do, $temperature, $salinity) {
    $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python';
    $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/AquaReCheck.py';

    $command = escapeshellcmd("$pythonEnv $scriptPath $ph $do $temperature $salinity");
    $output = shell_exec($command . " 2>&1");  // 標準エラー出力も取得

    // デバッグ情報をファイルに書き込み
    $debug_file = __DIR__ . '/php_debug.txt';
    file_put_contents($debug_file, "Command: $command\nOutput: $output\n", FILE_APPEND);

    return array_map('trim', explode(",", trim($output)));
}

$csv_file = getLatestCSVForSystem1();
$today = date('Y/m/d');

$default_day = 1;
$default_pl = 15;
$default_ph = isset($_GET['ph']) ? $_GET['ph'] : '';
$default_do = isset($_GET['do']) ? $_GET['do'] : '';
$default_temperature = isset($_GET['temperature']) ? $_GET['temperature'] : '';
$default_salinity = isset($_GET['salinity']) ? $_GET['salinity'] : '';

if (file_exists($csv_file)) {
    $fp = fopen($csv_file, 'r');
    fgetcsv($fp); // ヘッダー行をスキップ
    $last_row = null;
    while (($row = fgetcsv($fp)) !== false) {
        $last_row = $row;
    }
    fclose($fp);

    if ($last_row !== null) {
        $default_day = isset($last_row[1]) ? intval($last_row[1]) + 1 : 1;
        $default_pl = isset($last_row[2]) ? intval($last_row[2]) + 1 : 15;
    }
}

// 画像認識処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
    $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python';
    $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/recog_pram_values.py';
    $imageDirectory = '/home/xs29345872/globalsteptest.com/public_html/katsurao/image/';
    $imagePath = $imageDirectory . 'measurement.png'; 

    $uploadedFile = $_FILES['image']['tmp_name'];

    if (!is_dir($imageDirectory)) {
        mkdir($imageDirectory, 0777, true);
    }

    if (move_uploaded_file($uploadedFile, $imagePath)) {
        // Pythonスクリプトを実行 (引数は渡さない)
        $command = escapeshellcmd("$pythonEnv $scriptPath");
        $output = shell_exec($command);

    
        $results = explode(" ", $output);

        $ph_result = trim($results[0]);
        $temp1_result = trim($results[1]);
        $salt_result = trim($results[2]);
        $temp2_result = trim($results[3]);
        $do_result = trim($results[4]);
        $temp3_result = trim($results[5]);

        $redirectURL = "https://globalsteptest.com/katsurao/1-1.php?ph=$ph_result&do=$do_result&temperature=$temp2_result&salinity=$salt_result";

        header("Location: $redirectURL");
        exit;
    } else {
        echo "画像のアップロードに失敗しました。";
    }
}

$showAnomalyWarning = false;
$anomalyMessage = "";

// データ登録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_data'])) {
    $day = $_POST['day'];
    $pl = $_POST['pl'];
    $ph = $_POST['ph'];
    $do = $_POST['do'];
    $temperature = $_POST['temperature'];
    $salinity = $_POST['salinity'];

    $anomalies = detectAnomalies($ph, $do, $temperature, $salinity);
    
    // デバッグ情報をファイルに書き込み
    $debug_file = __DIR__ . '/php_process_debug.txt';
    file_put_contents($debug_file, "Anomalies: " . print_r($anomalies, true) . "\n", FILE_APPEND);

    $hasAnomalies = in_array('1', $anomalies);

    file_put_contents($debug_file, "Has Anomalies: " . ($hasAnomalies ? 'true' : 'false') . "\n", FILE_APPEND);

    if ($hasAnomalies) {
        $showAnomalyWarning = true;
        $anomalyMessage = "以下の値に異常が検出されました:\\n";
        if ($anomalies[0] === '1') $anomalyMessage .= "- pH\\n";
        if ($anomalies[1] === '1') $anomalyMessage .= "- DO\\n";
        if ($anomalies[2] === '1') $anomalyMessage .= "- 温度\\n";
        if ($anomalies[3] === '1') $anomalyMessage .= "- 塩分\\n";
        $anomalyMessage .= "\\n登録を続行する場合は、備考を入力してください。";
    } else {
        // 異常がない場合、直接登録
        $fp = fopen($csv_file, 'a');
        fputcsv($fp, [$today, $day, $pl, $ph, $do, $temperature, $salinity, '']);
        fclose($fp);

        header("Location: 1.php");
        exit;
    }
}

// 確認後の登録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_submit'])) {
    $day = $_POST['day'];
    $pl = $_POST['pl'];
    $ph = $_POST['ph'];
    $do = $_POST['do'];
    $temperature = $_POST['temperature'];
    $salinity = $_POST['salinity'];
    $notes = $_POST['notes'];

    // ユーザー名を取得して備考に追加
    $userId = $_SESSION['id'];
    $userName = getUserName($userId);
    $notes = trim($notes); // 前後の空白を削除
    if (!empty($notes)) {
        $notes .= " (" . $userName . ")";
    } else {
        $notes = "(" . $userName . ")";
    }

    $fp = fopen($csv_file, 'a');
    fputcsv($fp, [$today, $day, $pl, $ph, $do, $temperature, $salinity, $notes]);
    fclose($fp);

    // Pythonスクリプトの実行
    $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python';
    $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/AquaMonitor.py';
    
    // 日本語を含む文字列をBase64エンコードする
    $encodedNotes = base64_encode($notes);
    
    // コマンドライン引数を構築（Base64エンコードした備考欄のテキストを含む）
    $command = escapeshellcmd("$pythonEnv $scriptPath $ph $do $temperature $salinity") . ' ' . escapeshellarg($encodedNotes);
    
    // Pythonスクリプトを実行
    $output = shell_exec($command);

    // デバッグ用：Pythonスクリプトの出力をログに記録
    $debug_file = __DIR__ . '/python_execution_debug.txt';
    file_put_contents($debug_file, "Command: $command\nOutput: $output\n", FILE_APPEND);

    header("Location: 1.php");
    exit;
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
    <title>数値入力</title>
    <link rel="stylesheet" href="input.css">
    <script>
    function showAnomalyWarning(message) {
        var notes = prompt(message);
        if (notes !== null && notes.trim() !== "") {
            document.getElementById('notes').value = notes;
            document.getElementById('confirmForm').submit();
        } else {
            // ブラウザの戻るボタンと同じ挙動
            history.back();
        }
    }
    </script>
</head>
<body>
    <h1>数値入力</h1>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
        <div>
            <label for="image">画像選択:</label>
            <input type="file" id="image" name="image">
        </div>
        <input type="submit" value="画像を送信">
    </form>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="dataForm">
        <div>
            <label for="date">日付:</label>
            <input type="text" id="date" name="date" value="<?php echo $today; ?>" readonly>
        </div>
        <div>
            <label for="day">Day:</label>
            <select id="day" name="day">
                <?php
                for ($i = 1; $i <= 120; $i++) {
                    echo "<option value='$i'" . ($i == $default_day ? ' selected' : '') . ">$i</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label for="pl">PL:</label>
            <select id="pl" name="pl">
                <?php
                for ($i = 15; $i <= 120; $i++) {
                    echo "<option value='$i'" . ($i == $default_pl ? ' selected' : '') . ">$i</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label for="ph">pH:</label>
            <input type="text" id="ph" name="ph" value="<?php echo $default_ph; ?>">
        </div>
        <div>
            <label for="do">DO:</label>
            <input type="text" id="do" name="do" value="<?php echo $default_do; ?>">
        </div>
        <div>
            <label for="temperature">温度:</label>
            <input type="text" id="temperature" name="temperature" value="<?php echo $default_temperature; ?>">
        </div>
        <div>
            <label for="salinity">塩分:</label>
            <input type="text" id="salinity" name="salinity" value="<?php echo $default_salinity; ?>">
        </div>
        <input type="hidden" name="submit_data" value="1">
        <button type="submit">登録</button>
    </form>

    <form id="confirmForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" style="display: none;">
        <input type="hidden" name="confirm_submit" value="1">
        <input type="hidden" name="day" value="<?php echo isset($_POST['day']) ? $_POST['day'] : $default_day; ?>">
        <input type="hidden" name="pl" value="<?php echo isset($_POST['pl']) ? $_POST['pl'] : $default_pl; ?>">
        <input type="hidden" name="ph" value="<?php echo isset($_POST['ph']) ? $_POST['ph'] : $default_ph; ?>">
        <input type="hidden" name="do" value="<?php echo isset($_POST['do']) ? $_POST['do'] : $default_do; ?>">
        <input type="hidden" name="temperature" value="<?php echo isset($_POST['temperature']) ? $_POST['temperature'] : $default_temperature; ?>">
        <input type="hidden" name="salinity" value="<?php echo isset($_POST['salinity']) ? $_POST['salinity'] : $default_salinity; ?>">
        <input type="hidden" id="notes" name="notes" value="">
    </form>

    <?php if ($showAnomalyWarning): ?>
    <script>
    showAnomalyWarning("<?php echo $anomalyMessage; ?>");
    </script>
    <?php endif; ?>
</body>
</html>