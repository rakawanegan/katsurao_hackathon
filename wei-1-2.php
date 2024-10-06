<?php
// len-1-1.php

// セッション開始
session_start();

// ログイン状態の確認
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    // ログインしていない場合は、ログインページにリダイレクト
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// 最新のCSVファイル名を取得する関数 (システム1用)
function getLatestCSVForSystem1() {
    $currentYear = date('Y');
    $previousYear = $currentYear;
    $pattern = sprintf('/(%s)-(?:1st|2nd|3rd|4th|5th)-wei-1-2\.csv$/', $previousYear . '|' . $currentYear);
    $files = glob('*.csv');
    $matchedFiles = array_filter($files, function ($file) use ($pattern) {
        return preg_match($pattern, $file);
    });
    usort($matchedFiles, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    return reset($matchedFiles);
}

// CSVファイルから最後のPLを取得し、+10した値をデフォルトPLとする
function getDefaultPL($csv_file) {
    $lastPL = 0; // 初期値
    if (file_exists($csv_file)) {
        $lines = file($csv_file, FILE_IGNORE_NEW_LINES);
        if (count($lines) > 1) { // ヘッダー行を除く
            $lastLine = explode(',', $lines[count($lines) - 1]);
            if (isset($lastLine[0]) && is_numeric($lastLine[0])) {
                $lastPL = intval($lastLine[0]);
            }
        }
    }
    return max(10, $lastPL + 10); // 最小値10を保証しつつ、最後のPLに10を加える
}

$csv_file = getLatestCSVForSystem1();
$defaultPL = getDefaultPL($csv_file);

// エラーメッセージの初期化
$errorMessage = "";

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pl = $_POST['pl'];
    $weight = $_POST['weight'];

    // バリデーション
    if (!is_numeric($weight) || $weight <= 0) {
        $errorMessage = "体重には正の数字を入力してください。";
    } else {
        // ヘッダー行がまだ存在しない場合は書き込む
        if (!file_exists($csv_file)) {
            $fp = fopen($csv_file, 'w');
            fputcsv($fp, ['PL', 'wei']);
            fclose($fp);
        }

        // データをCSVファイルに追記
        $fp = fopen($csv_file, 'a');
        fputcsv($fp, [$pl, $weight]);
        fclose($fp);

        // wei_input.php にリダイレクト
        header("Location: wei_input.php");
        exit;
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
    <title>体重入力</title>
    <link rel="stylesheet" href="input.css">
</head>
<body>
    <h1>体重入力</h1>

    <?php if (!empty($errorMessage)): ?>
        <div class="error">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <div>
            <label for="pl">PL:</label>
            <select id="pl" name="pl">
                <?php
                for ($i = $defaultPL; $i <= 120; $i += 10) {
                    echo "<option value='$i'" . ($i === $defaultPL ? " selected" : "") . ">$i</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label for="weight">体重 (g):</label>
            <input type="text" id="weight" name="weight" required>
        </div>
        <button type="submit">登録</button>
    </form>
</body>
</html>