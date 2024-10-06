<?php
// セッション開始
session_start();

// ログイン状態の確認
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSystem = $_POST['system'];

    // 最新のCSVファイル名を取得
    $latestFiles = getLatestCsvFiles($selectedSystem);

    // 新しいクールと年の計算
    $latestFileName = reset($latestFiles);
    preg_match('/(\d{4})-(\w+)-/', $latestFileName, $matches);
    $lastYear = $matches[1];
    $lastCool = $matches[2];

    $coolOrder = ['1st', '2nd', '3rd', '4th', '5th'];
    $coolIndex = array_search($lastCool, $coolOrder);
    $newCoolIndex = ($coolIndex + 1) % count($coolOrder);
    $newCool = $coolOrder[$newCoolIndex];
    $newYear = ($newCoolIndex === 0) ? (int)$lastYear + 1 : (int)$lastYear;

    // 新しいCSVファイル名を作成
    $newCsvFiles = [];
    foreach ($latestFiles as $fileType => $fileName) {
        $newFileName = str_replace("{$lastYear}-{$lastCool}", "{$newYear}-{$newCool}", $fileName);
        $newCsvFiles[$fileType] = $newFileName;
    }

    // 長さと重さのCSVファイル名を追加
    $lenWeiTypes = ['len', 'wei'];
    foreach ($lenWeiTypes as $type) {
        foreach (['1', '2'] as $number) {
            $newCsvFiles["{$type}-{$number}"] = "{$newYear}-{$newCool}-{$type}-{$selectedSystem}-{$number}.csv";
        }
    }

    // 新しいCSVファイルを作成 
    foreach ($newCsvFiles as $fileType => $newFileName) {
        // ヘッダー行を設定 (ファイルタイプによって異なる)
        if ($fileType === 'nitrification') {
            $header = ['日付', 'Day', 'PL', 'pH', 'DO', '温度', '塩分', 'NH4', 'NO2', 'NO3', 'Ca', 'Al', 'Mg', '備考'];
        } elseif (strpos($fileType, 'len') === 0) {
            $header = ['PL', 'len'];
        } elseif (strpos($fileType, 'wei') === 0) {
            $header = ['PL', 'wei'];
        } else {
            $header = ['日付', 'Day', 'PL', 'pH', 'DO', '温度', '塩分', '備考'];
        }

        $fp = fopen($newFileName, 'w');
        fputcsv($fp, $header);
        fclose($fp);
    }

    // list.php にリダイレクト
    header("Location: newlist.php?system=" . $selectedSystem);
    exit;
}

function getLatestCsvFiles($selectedSystem) {
    $allFiles = glob("*.csv");
    $systemFiles = array_filter($allFiles, function($file) use ($selectedSystem) {
        return preg_match("/^\d{4}-\w+-{$selectedSystem}-/", $file);
    });

    if (empty($systemFiles)) {
        return [];
    }

    // 年度でグループ化
    $filesByYear = [];
    foreach ($systemFiles as $file) {
        $year = substr($file, 0, 4);
        $filesByYear[$year][] = $file;
    }

    // 最新の年度を取得
    $latestYear = max(array_keys($filesByYear));

    // 最新の年度のファイルを期間でソート
    usort($filesByYear[$latestYear], function($a, $b) {
        preg_match("/^\d{4}-(\w+)-/", $a, $matchesA);
        preg_match("/^\d{4}-(\w+)-/", $b, $matchesB);
        return strcmp($matchesB[1], $matchesA[1]);
    });

    // 最新の期間のファイルを取得
    $latestPeriodFiles = array_filter($filesByYear[$latestYear], function($file) use ($filesByYear, $latestYear) {
        return strpos($file, substr($filesByYear[$latestYear][0], 0, 9)) === 0;
    });

    // ファイルタイプごとに最新のファイルを選択
    $result = [];
    $fileTypes = ['1', '2', 'nitrification'];
    foreach ($fileTypes as $type) {
        $typeFiles = array_filter($latestPeriodFiles, function($file) use ($selectedSystem, $type) {
            return strpos($file, "{$selectedSystem}-{$type}") !== false;
        });
        if (!empty($typeFiles)) {
            $result[$type] = reset($typeFiles);
        }
    }

    return $result;
}
?>