<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $period = $_POST['period'];
    $system = $_POST['system'];
    $item = $_POST['item'];

    $filePatterns = [
        'water' => [
            '1' => [$period . '-1-1.csv' => '1-1', $period . '-1-2.csv' => '1-2', $period . '-1-nitrification.csv' => '硝化槽'],
'2' => [$period . '-2-1.csv' => '2-1', $period . '-2-2.csv' => '2-2', $period . '-2-nitrification.csv' => '硝化槽'],
            '3' => [$period . '-3-1.csv' => '3-1', $period . '-3-2.csv' => '3-2', $period . '-3-nitrification.csv' => '硝化槽'],
        ],
        'len' => [
            '1' => [$period . '-len-1-1.csv' => '1-1', $period . '-len-1-2.csv' => '1-2'],
            '2' => [$period . '-len-2-1.csv' => '2-1', $period . '-len-2-2.csv' => '2-2'],
            '3' => [$period . '-len-3-1.csv' => '3-1', $period . '-len-3-2.csv' => '3-2'],
        ],
        'wei' => [
            '1' => [$period . '-wei-1-1.csv' => '1-1', $period . '-wei-1-2.csv' => '1-2'],
            '2' => [$period . '-wei-2-1.csv' => '2-1', $period . '-wei-2-2.csv' => '2-2'],
            '3' => [$period . '-wei-3-1.csv' => '3-1', $period . '-wei-3-2.csv' => '3-2'],
        ],
    ];

    foreach ($filePatterns[$item][$system] as $fileName => $displayName) {
      if (file_exists($fileName)) {
          $data = [];
          if (($handle = fopen($fileName, "r")) !== FALSE) {
              $headers = fgetcsv($handle);
              $data[] = $headers;  // ヘッダーを保存
              while (($row = fgetcsv($handle)) !== FALSE) {
                  $data[] = $row;
              }
              fclose($handle);
          }
  
          if ($item === 'water') {
              foreach ($_POST[$displayName] as $index => $values) {
                  $index = intval($index) + 1;  // インデックスを1つずらす
                  if (isset($data[$index])) {
                      $data[$index][3] = $values['pH'];
                      $data[$index][4] = $values['DO'];
                      $data[$index][5] = $values['温度'];
                      $data[$index][6] = $values['塩分'];
                      if ($displayName === '硝化槽' && count($data[$index]) > 7) {
                          $data[$index][7] = $values['NH4'];
                          $data[$index][8] = $values['NO2'];
                          $data[$index][9] = $values['NO3'];
                          $data[$index][10] = $values['Ca'];
                          $data[$index][11] = $values['Al'];
                          $data[$index][12] = $values['Mg'];
                      }
                  }
              }
          } else {
              foreach ($_POST[$displayName] as $pl => $value) {
                  $found = false;
                  foreach ($data as $index => $row) {
                      if ($index === 0) continue;  // ヘッダー行をスキップ
                      if (isset($row[0]) && $row[0] == $pl) {
                          $data[$index][1] = $value;  // lenまたはweiの値を更新
                          $found = true;
                          break;
                      }
                  }
                  // もし既存のPLが見つからなければ、新しい行を追加
                  if (!$found) {
                      $data[] = [$pl, $value];
                  }
              }
          }
  
          // ファイルに書き込み
          if (($handle = fopen($fileName, "w")) !== FALSE) {
              foreach ($data as $row) {
                  fputcsv($handle, $row);
              }
              fclose($handle);
          }
      }
  }

    // リダイレクト先のURLを作成
    $redirect_url = "list.php?period=" . urlencode($period) . "&system=" . urlencode($system) . "&item=" . urlencode($item);
    header("Location: " . $redirect_url);
    exit;
}
?>