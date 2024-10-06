<?php
// セッション開始
session_start();

// ログイン状態の確認
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  header("Location: login.php");
  exit;
}

// 最新のCSVファイル名を取得する関数 (nitrification系)
function getLatestCSVForNitrification() {
    $currentYear = date('Y');
    $previousYear = $currentYear;
    $pattern = sprintf('/(%s)-(?:1st|2nd|3rd|4th|5th)-1-nitrification\.csv$/', $previousYear . '|' . $currentYear);
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
        fgetcsv($handle, 1000, ",");
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($data[1] == $userId) {
                fclose($handle);
                return $data[3];
            }
        }
        fclose($handle);
    }
    return "Unknown User";
}

// 異常検知を行う関数 (Nitrification用)
function detectNitrificationAnomalies($ph, $do, $temperature, $salinity, $nh4, $no2, $no3, $ca, $al, $mg) {
    $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python';
    $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/NitrificationReCheck.py';

    $command = escapeshellcmd("$pythonEnv $scriptPath $ph $do $temperature $salinity $nh4 $no2 $no3 $ca $al $mg");
    $output = shell_exec($command . " 2>&1");

    $debug_file = __DIR__ . '/php_debug_nitrification.txt';
    $debug_info = "Command: $command\n";
    $debug_info .= "Output: $output\n";
    $debug_info .= "Input values: ph=$ph, do=$do, temperature=$temperature, salinity=$salinity, nh4=$nh4, no2=$no2, no3=$no3, ca=$ca, al=$al, mg=$mg\n";
    file_put_contents($debug_file, $debug_info, FILE_APPEND);

    return array_map('trim', explode(",", trim($output)));
}

$csv_file = getLatestCSVForNitrification();
$today = date('Y/m/d');

$default_day = 1;
$default_pl = 15;
$default_ph = isset($_GET['ph']) ? $_GET['ph'] : '';
$default_do = isset($_GET['do']) ? $_GET['do'] : '';
$default_temperature = isset($_GET['temperature']) ? $_GET['temperature'] : '';
$default_salinity = isset($_GET['salinity']) ? $_GET['salinity'] : '';
$default_nh4 = isset($_GET['nh4']) ? $_GET['nh4'] : '';
$default_no2 = isset($_GET['no2']) ? $_GET['no2'] : '';
$default_no3 = isset($_GET['no3']) ? $_GET['no3'] : '';
$default_ca = isset($_GET['ca']) ? $_GET['ca'] : '';
$default_al = isset($_GET['al']) ? $_GET['al'] : '';
$default_mg = isset($_GET['mg']) ? $_GET['mg'] : '';

if (file_exists($csv_file)) {
    $fp = fopen($csv_file, 'r');
    fgetcsv($fp);
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

    $uploadedFile = $_FILES['image']['tmp_name'];
    $imageDirectory = '/home/xs29345872/globalsteptest.com/public_html/katsurao/image/';
    $newImageName = 'measurement.png';
    $imagePath = $imageDirectory . $newImageName;

    if (!is_dir($imageDirectory)) {
        mkdir($imageDirectory, 0777, true);
    }

    if (move_uploaded_file($uploadedFile, $imagePath)) {
        $command = escapeshellcmd("$pythonEnv $scriptPath") . ' ' . escapeshellarg($imagePath);
        $output = shell_exec($command);

        $results = explode(" ", $output);

        $ph_result = trim($results[0]);
        $temp1_result = trim($results[1]);
        $salt_result = trim($results[2]);
        $temp2_result = trim($results[3]);
        $do_result = trim($results[4]);
        $temp3_result = trim($results[5]);

        $redirectURL = "https://globalsteptest.com/katsurao/1-nitrification.php?ph=$ph_result&do=$do_result&temperature=$temp2_result&salinity=$salt_result";

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
    $nh4 = $_POST['nh4'];
    $no2 = $_POST['no2'];
    $no3 = $_POST['no3'];
    $ca = $_POST['ca'];
    $al = $_POST['al'];
    $mg = $_POST['mg'];

    $anomalies = detectNitrificationAnomalies($ph, $do, $temperature, $salinity, $nh4, $no2, $no3, $ca, $al, $mg);
    
    $debug_file = __DIR__ . '/php_process_debug_nitrification.txt';
    file_put_contents($debug_file, "Anomalies: " . print_r($anomalies, true) . "\n", FILE_APPEND);

    $hasAnomalies = in_array('1', $anomalies);

    file_put_contents($debug_file, "Has Anomalies: " . ($hasAnomalies ? 'true' : 'false') . "\n", FILE_APPEND);

    if ($hasAnomalies) {
        $showAnomalyWarning = true;
        $anomalyMessage = "以下の値に異常が検出されました:\\n";
        $anomalyParams = ['pH', 'DO', '温度', '塩分', 'NH4', 'NO2', 'NO3', 'Ca', 'Al', 'Mg'];
        foreach ($anomalies as $index => $value) {
            if ($value === '1') $anomalyMessage .= "- " . $anomalyParams[$index] . "\\n";
        }
        $anomalyMessage .= "\\n登録を続行する場合は、備考を入力してください。";
    } else {
        $fp = fopen($csv_file, 'a');
        fputcsv($fp, [$today, $day, $pl, $ph, $do, $temperature, $salinity, $nh4, $no2, $no3, $ca, $al, $mg, '']);
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
    $nh4 = $_POST['nh4'];
    $no2 = $_POST['no2'];
    $no3 = $_POST['no3'];
    $ca = $_POST['ca'];
    $al = $_POST['al'];
    $mg = $_POST['mg'];
    $notes = $_POST['notes'];

    $userId = $_SESSION['id'];
    $userName = getUserName($userId);
    $notes = trim($notes); // 前後の空白を削除
    if (!empty($notes)) {
        $notes .= " (" . $userName . ")";
    } else {
        $notes = "(" . $userName . ")";
    }

    $fp = fopen($csv_file, 'a');
    fputcsv($fp, [$today, $day, $pl, $ph, $do, $temperature, $salinity, $nh4, $no2, $no3, $ca, $al, $mg, $notes]);
    fclose($fp);

    $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python';
    $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/AquaMonitor_nitrification.py';
    
    // 日本語を含む文字列をBase64エンコードする
    $encodedNotes = base64_encode($notes);
    
    // コマンドライン引数を構築
    $args = [
        $ph, $do, $temperature, $salinity, $nh4, $no2, $no3, $ca, $al, $mg,
        escapeshellarg($encodedNotes)
    ];
    $argString = implode(' ', $args);
    
    $command = escapeshellcmd("$pythonEnv $scriptPath") . " $argString";
    
    $output = shell_exec($command);

    // デバッグ用：Pythonスクリプトの出力をログに記録
    $debug_file = __DIR__ . '/python_execution_debug_nitrification.txt';
    $debug_info = "Command: $command\n";
    $debug_info .= "Encoded Notes: $encodedNotes\n";
    $debug_info .= "Original Notes: $notes\n";
    $debug_info .= "Output: $output\n";
    file_put_contents($debug_file, $debug_info, FILE_APPEND);

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
    <title>数値入力 (硝化システム)</title>
    <link rel="stylesheet" href="input.css">
    <script>
    function showAnomalyWarning(message) {
        var notes = prompt(message);
        if (notes !== null && notes.trim() !== "") {
            document.getElementById('notes').value = notes;
            document.getElementById('confirmForm').submit();
        } else {
            history.back();
        }
    }
    </script>
</head>
<body>
    <h1>数値入力 (硝化システム)</h1>

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
        <div>
            <label for="nh4">NH4:</label>
            <input type="text" id="nh4" name="nh4" value="<?php echo $default_nh4; ?>">
        </div>
        <div>
            <label for="no2">NO2:</label>
            <input type="text" id="no2" name="no2" value="<?php echo $default_no2; ?>">
        </div>
        <div>
            <label for="no3">NO3:</label>
            <input type="text" id="no3" name="no3" value="<?php echo $default_no3; ?>">
        </div>
        <div>
            <label for="ca">Ca:</label>
            <input type="text" id="ca" name="ca" value="<?php echo $default_ca; ?>">
        </div>
        <div>
            <label for="al">Al:</label>
            <input type="text" id="al" name="al" value="<?php echo $default_al; ?>">
        </div>
        <div>
            <label for="mg">Mg:</label>
            <input type="text" id="mg" name="mg" value="<?php echo $default_mg; ?>">
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
        <input type="hidden" name="nh4" value="<?php echo isset($_POST['nh4']) ? $_POST['nh4'] : $default_nh4; ?>">
        <input type="hidden" name="no2" value="<?php echo isset($_POST['no2']) ? $_POST['no2'] : $default_no2; ?>">
        <input type="hidden" name="no3" value="<?php echo isset($_POST['no3']) ? $_POST['no3'] : $default_no3; ?>">
        <input type="hidden" name="ca" value="<?php echo isset($_POST['ca']) ? $_POST['ca'] : $default_ca; ?>">
        <input type="hidden" name="al" value="<?php echo isset($_POST['al']) ? $_POST['al'] : $default_al; ?>">
        <input type="hidden" name="mg" value="<?php echo isset($_POST['mg']) ? $_POST['mg'] : $default_mg; ?>">
        <input type="hidden" id="notes" name="notes" value="">
    </form>

    <?php if ($showAnomalyWarning): ?>
    <script>
    showAnomalyWarning("<?php echo $anomalyMessage; ?>");
    </script>
    <?php endif; ?>
</body>
</html>