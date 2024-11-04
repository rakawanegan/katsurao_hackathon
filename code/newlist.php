<?php
// セッション開始
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  // ログインしていない場合は、ログインページにリダイレクト
  $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  header("Location: login.php"); // ログインページのURL
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

<!DOCTYPE html>
<?php
  session_start();
  require_once('header.php'); 
?>
<html>
<head>
  <title>CSV Data Viewer</title>
  <link rel="stylesheet" href="list.css?v=<?php echo filemtime('list.css'); ?>">
</head>
<body>

<h1>CSV Data Viewer</h1>

<form method="post">
  <label for="system">システムを選択:</label>
  <select name="system" id="system">
    <option value="1">1系</option>
    <option value="2">2系</option>
  </select>

  <button type="submit">表示</button>
</form>

<div id="csv-data">
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $selectedSystem = $_POST['system'];

  $filePatterns = [
    '1' => '1',
    '2' => '2',
    'nitrification' => '硝化槽',
  ];

  $data = [];
  $usedFiles = getLatestCsvFiles($selectedSystem);

  foreach ($filePatterns as $fileType => $displayName) {
    $fileName = isset($usedFiles[$fileType]) ? $usedFiles[$fileType] : null;
    if ($fileName && file_exists($fileName)) {
      $handle = fopen($fileName, 'r');
      if ($handle) {
        fgetcsv($handle);  // ヘッダーをスキップ

        while (($row = fgetcsv($handle)) !== false) {
          $date = $row[0];
          $day = $row[1];
          $data[$day]['日付'] = $date;
          $data[$day]['PL'] = $row[2];

          $data[$day][$displayName] = [
            'pH' => $row[3],
            'DO' => $row[4],
            '温度' => $row[5],
            '塩分' => $row[6],
          ];
          if ($displayName === '硝化槽') {
            $data[$day][$displayName]['NH4'] = $row[7];
            $data[$day][$displayName]['NO2'] = $row[8];
            $data[$day][$displayName]['NO3'] = $row[9];
            $data[$day][$displayName]['Ca'] = $row[10];
            $data[$day][$displayName]['Al'] = $row[11];
            $data[$day][$displayName]['Mg'] = $row[12];
          }
        }
        fclose($handle);
      }
    }
  }

  // デバッグ情報の表示
  echo "<h3>使用されたCSVファイル:</h3>";
  echo "<ul>";
  foreach ($usedFiles as $file) {
    echo "<li>$file</li>";
  }
  echo "</ul>";

  if (!empty($data)) {
    echo '<h2>' . $selectedSystem . '系</h2>';
    echo '<table>';
    echo '<thead>';
    echo '<tr><th>日付</th><th>Day</th><th>PL</th>';

    foreach ($filePatterns as $displayName) {
      $colspan = ($displayName === '硝化槽') ? 10 : 4;
      echo "<th colspan='$colspan'>$displayName</th>";
    }
    echo '</tr>';

    echo '<tr><th></th><th></th><th></th>';
    foreach ($filePatterns as $displayName) {
      echo '<th>pH</th><th>DO</th><th>温度</th><th>塩分</th>';
      if ($displayName === '硝化槽') {
        echo '<th>NH4</th><th>NO2</th><th>NO3</th><th>Ca</th><th>Al</th><th>Mg</th>';
      }
    }
    echo '</tr>';
    echo '</thead>';

    echo '<tbody>';

    echo '<tr>';
    echo '<td>基準</td><td></td><td></td>';
    foreach ($filePatterns as $displayName) {
        echo '<td>7.9-8.2</td><td>6~</td><td>28-29</td><td>2.0</td>';
        if ($displayName === '硝化槽') {
            echo '<td>0-5</td><td>1.5-2.5</td><td>15-20</td><td>280-300</td><td>180-200</td><td>700-800</td>';
        }
    }
    echo '</tr>';

    ksort($data);
    foreach ($data as $day => $dayData) {
      echo '<tr>';
      echo "<td>{$dayData['日付']}</td>";
      echo "<td>{$day}</td>";
      echo "<td>{$dayData['PL']}</td>";

      foreach ($filePatterns as $displayName) {
        if (isset($dayData[$displayName])) {
          echo "<td>{$dayData[$displayName]['pH']}</td>";
          echo "<td>{$dayData[$displayName]['DO']}</td>";
          echo "<td>{$dayData[$displayName]['温度']}</td>";
          echo "<td>{$dayData[$displayName]['塩分']}</td>";

          if ($displayName === '硝化槽') {
            echo "<td>{$dayData[$displayName]['NH4']}</td>";
            echo "<td>{$dayData[$displayName]['NO2']}</td>";
            echo "<td>{$dayData[$displayName]['NO3']}</td>";
            echo "<td>{$dayData[$displayName]['Ca']}</td>";
            echo "<td>{$dayData[$displayName]['Al']}</td>";
            echo "<td>{$dayData[$displayName]['Mg']}</td>";
          }
        } else {
          $cellCount = ($displayName === '硝化槽') ? 10 : 4;
          echo str_repeat("<td></td>", $cellCount);
        }
      }
      echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    
    // 新しいクールを作成するボタンを追加
    echo '<form action="create_new_csv.php" method="post">';
    echo '<input type="hidden" name="system" value="' . $selectedSystem . '">';
    echo '<button type="submit">新しいクールを作成</button>';
    echo '</form>';
  } else {
    echo '<p>データが見つかりませんでした。</p>';
  }
}
?>
</div>

</body>
</html>