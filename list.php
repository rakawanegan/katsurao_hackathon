<?php
// セッション開始
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  // ログインしていない場合は、ログインページにリダイレクト
  $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  header("Location: login.php"); // ログインページのURL
  exit;
}

function getCsvFiles($pattern) {
  return glob($pattern);
}

function readCsvData($file) {
  $data = [];
  if (($handle = fopen($file, "r")) !== FALSE) {
    $headers = fgetcsv($handle); // ヘッダーを読み込む
    while (($row = fgetcsv($handle)) !== FALSE) {
      $data[] = $row;
    }
    fclose($handle);
  }
  return $data;
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
    <title>CSV Data Viewer</title>
    <link rel="stylesheet" href="list.css?v=<?php echo filemtime('list.css'); ?>">
</head>
<body>
    <div class="container">
        <h1>CSV Data Viewer</h1>

        <form method="post" action="list.php">
        <div>
            <label for="period">時期を選択:</label>
            <select name="period" id="period">
            <?php
            $periods = [];
            $csvFiles = glob('*.csv');
            foreach ($csvFiles as $csvFile) {
                $period = substr($csvFile, 5, 3); // "2st", "3st" などを抽出
                $period = str_replace(['2st', '3st'], ['2nd', '3rd'], $period); // 置換
                $periods[] = substr($csvFile, 0, 5) . $period; // 年と修正した期間を結合
            }
            $periods = array_unique($periods);
            rsort($periods); // 期間を降順にソート（最新が最初に来るように）

            $latestPeriod = reset($periods); // 最新の期間を取得

            foreach ($periods as $period) {
                $selected = '';
                if (isset($_POST['period'])) {
                    // POSTデータがある場合は、それを優先
                    $selected = ($_POST['period'] == $period) ? 'selected' : '';
                } else {
                    // POSTデータがない場合は、最新の期間を選択
                    $selected = ($period == $latestPeriod) ? 'selected' : '';
                }
                echo "<option value='$period' $selected>$period</option>";
            }
            ?>
        </select>
        </div>
            <div>
                <label for="system">システムを選択:</label>
                <select name="system" id="system">
                    <?php
                    $systems = ['1' => '1系', '2' => '2系'];
                    foreach ($systems as $value => $label) {
                      $selected = (isset($_POST['system']) && $_POST['system'] == $value) ? 'selected' : '';
                      echo "<option value='$value' $selected>$label</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="item">項目を選択:</label>
                <select name="item" id="item">
                    <?php
                    $items = ['water' => '水質', 'len' => '体長', 'wei' => '体重'];
                    foreach ($items as $value => $label) {
                      $selected = (isset($_POST['item']) && $_POST['item'] == $value) ? 'selected' : '';
                      echo "<option value='$value' $selected>$label</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit">表示</button>
        </form>

        <div id="csv-data">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $selectedPeriod = $_POST['period'];
    
            // 選択された期間を元のファイル名形式に変換
            $selectedPeriod = substr($selectedPeriod, 0, 5) . str_replace(['2nd', '3rd'], ['2st', '3st'], substr($selectedPeriod, 5));

            $selectedSystem = $_POST['system'];
            $selectedItem = $_POST['item'];

            $filePatterns = [
                'water' => [
                  '1' => [$selectedPeriod . '-1-1.csv' => '1-1', $selectedPeriod . '-1-2.csv' => '1-2', $selectedPeriod . '-1-nitrification.csv' => '硝化槽'],
                  '2' => [$selectedPeriod . '-2-1.csv' => '2-1', $selectedPeriod . '-2-2.csv' => '2-2', $selectedPeriod . '-2-nitrification.csv' => '硝化槽'],
                  '3' => [$selectedPeriod . '-3-1.csv' => '3-1', $selectedPeriod . '-3-2.csv' => '3-2', $selectedPeriod . '-3-nitrification.csv' => '硝化槽'],
                ],
                'len' => [
                  '1' => [$selectedPeriod . '-len-1-1.csv' => '1-1', $selectedPeriod . '-len-1-2.csv' => '1-2'],
                  '2' => [$selectedPeriod . '-len-2-1.csv' => '2-1', $selectedPeriod . '-len-2-2.csv' => '2-2'],
                  '3' => [$selectedPeriod . '-len-3-1.csv' => '3-1', $selectedPeriod . '-len-3-2.csv' => '3-2'],
                ],
                'wei' => [
                  '1' => [$selectedPeriod . '-wei-1-1.csv' => '1-1', $selectedPeriod . '-wei-1-2.csv' => '1-2'],
                  '2' => [$selectedPeriod . '-wei-2-1.csv' => '2-1', $selectedPeriod . '-wei-2-2.csv' => '2-2'],
                  '3' => [$selectedPeriod . '-wei-3-1.csv' => '3-1', $selectedPeriod . '-wei-3-2.csv' => '3-2'],
                ],
            ];

            $data = [];

            foreach ($filePatterns[$selectedItem][$selectedSystem] as $fileName => $displayName) {
                if (file_exists($fileName)) {
                  $data[$displayName] = readCsvData($fileName);
                }
            }

            if (!empty($data)) {
                echo '<form id="update-form" method="post" action="update_csv.php">'; 
                echo '<input type="hidden" name="period" value="' . $selectedPeriod . '">';
                echo '<input type="hidden" name="system" value="' . $selectedSystem . '">';
                echo '<input type="hidden" name="item" value="' . $selectedItem . '">';

                echo '<h2>' . $selectedSystem . '系 - ' . ($selectedItem == 'water' ? '水質' : ($selectedItem == 'len' ? '体長' : '体重')) . '</h2>';
                
                if ($selectedItem === 'water') {
                  echo '<table class="water-quality">';
                  echo '<thead>';
                  echo '<tr>';
                  echo '<th rowspan="2">日付</th>';
                  echo '<th rowspan="2">Day</th>';
                  echo '<th rowspan="2">PL</th>';
                  foreach ($filePatterns[$selectedItem][$selectedSystem] as $displayName) {
                      $colspan = ($displayName === '硝化槽') ? 10 : 4;
                      echo '<th colspan="' . $colspan . '">' . $displayName . '</th>';
                  }
                  echo '</tr>';
                  echo '<tr>';
                  foreach ($filePatterns[$selectedItem][$selectedSystem] as $displayName) {
                      echo '<th>pH</th><th>DO</th><th>温度</th><th>塩分</th>';
                      if ($displayName === '硝化槽') {
                          echo '<th>NH4</th><th>NO2</th><th>NO3</th><th>Ca</th><th>Al</th><th>Mg</th>';
                      }
                  }
                  echo '</tr>';
                  echo '</thead>';
                  echo '<tbody>';
              
                  // 基準値の行
                  echo '<tr class="standard-row">';
                  echo '<td colspan="3">基準</td>';
                  foreach ($filePatterns[$selectedItem][$selectedSystem] as $displayName) {
                      echo '<td>7.9-8.2</td><td>6~</td><td>28-29</td><td>2.0</td>';
                      if ($displayName === '硝化槽') {
                          echo '<td>0-5</td><td>1.5-2.5</td><td>15-20</td><td>280-300</td><td>180-200</td><td>700-800</td>';
                      }
                  }
                  echo '</tr>';
              
                  $maxRows = max(array_map('count', $data));
                  for ($i = 0; $i < $maxRows; $i++) {  // ヘッダー行をスキップ
                      echo '<tr>';
                      $firstFile = reset($data);
                      if (isset($firstFile[$i])) {
                          echo '<td>' . htmlspecialchars($firstFile[$i][0]) . '</td>';
                          echo '<td>' . htmlspecialchars($firstFile[$i][1]) . '</td>';
                          echo '<td>' . htmlspecialchars($firstFile[$i][2]) . '</td>';
                      } else {
                          echo '<td></td><td></td><td></td>';
                      }
                      foreach ($filePatterns[$selectedItem][$selectedSystem] as $fileName => $displayName) {
                          if (isset($data[$displayName][$i])) {
                              $row = $data[$displayName][$i];
                              echo '<td><input type="text" name="' . $displayName . '[' . $i . '][pH]" value="' . htmlspecialchars($row[3]) . '"></td>';
                              echo '<td><input type="text" name="' . $displayName . '[' . $i . '][DO]" value="' . htmlspecialchars($row[4]) . '"></td>';
                              echo '<td><input type="text" name="' . $displayName . '[' . $i . '][温度]" value="' . htmlspecialchars($row[5]) . '"></td>';
                              echo '<td><input type="text" name="' . $displayName . '[' . $i . '][塩分]" value="' . htmlspecialchars($row[6]) . '"></td>';
                              if ($displayName === '硝化槽' && count($row) > 7) {
                                  echo '<td><input type="text" name="' . $displayName . '[' . $i . '][NH4]" value="' . htmlspecialchars($row[7]) . '"></td>';
                                  echo '<td><input type="text" name="' . $displayName . '[' . $i . '][NO2]" value="' . htmlspecialchars($row[8]) . '"></td>';
                                  echo '<td><input type="text" name="' . $displayName . '[' . $i . '][NO3]" value="' . htmlspecialchars($row[9]) . '"></td>';
                                  echo '<td><input type="text" name="' . $displayName . '[' . $i . '][Ca]" value="' . htmlspecialchars($row[10]) . '"></td>';
                                  echo '<td><input type="text" name="' . $displayName . '[' . $i . '][Al]" value="' . htmlspecialchars($row[11]) . '"></td>';
                                  echo '<td><input type="text" name="' . $displayName . '[' . $i . '][Mg]" value="' . htmlspecialchars($row[12]) . '"></td>';
                              } elseif ($displayName === '硝化槽') {
                                  echo '<td></td><td></td><td></td><td></td><td></td><td></td>';
                              }
                          } else {
                              $colspan = ($displayName === '硝化槽') ? 10 : 4;
                              echo str_repeat('<td></td>', $colspan);
                          }
                      }
                      echo '</tr>';
                  }
              
                  echo '</tbody>';
                  echo '</table>';
              } else {
                    // 体長または体重のテーブル
                    echo '<table>';
                    echo '<thead>';
                    echo '<tr><th>PL</th>';
                    foreach ($filePatterns[$selectedItem][$selectedSystem] as $displayName) {
                        echo "<th>$displayName</th>";
                    }
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    $plValues = [];
                    foreach ($data as $tankData) {
                        foreach ($tankData as $row) {
                            if (isset($row[0]) && $row[0] !== 'PL') {  // ヘッダー行をスキップ
                                $plValues[] = $row[0];
                            }
                        }
                    }
                    $plValues = array_unique($plValues);
                    sort($plValues);

                    foreach ($plValues as $pl) {
                        echo '<tr>';
                        echo "<td>$pl</td>";
                        foreach ($filePatterns[$selectedItem][$selectedSystem] as $fileName => $displayName) {
                            $value = '';
                            foreach ($data[$displayName] as $row) {
                                if (isset($row[0]) && $row[0] == $pl) {
                                    $value = isset($row[1]) ? $row[1] : '';  // lenまたはweiの値
                                    break;
                                }
                            }
                            echo "<td><input type='text' name='{$displayName}[{$pl}]' value='" . htmlspecialchars($value) . "'></td>";
                        }
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                }
                
                echo '<button type="submit">更新する</button>'; 
                echo '</form>';
                if (!empty($data)) {
                  echo '<form method="post" action="download_csv.php">';
                  echo '<input type="hidden" name="period" value="' . $selectedPeriod . '">';
                  echo '<input type="hidden" name="system" value="' . $selectedSystem . '">';
                  echo '<input type="hidden" name="item" value="' . $selectedItem . '">';
                  echo '<button type="submit">CSVファイルをダウンロード</button>';
                  echo '</form>';
              }
            } else {
                echo '<p>データが見つかりませんでした。</p>';
            }
        }
        ?>
        </div>
    </div>
    <script>
    const standards = {
        'pH': { min: 7.9, max: 8.2 },
        'DO': { min: 6, max: Infinity },
        '温度': { min: 28, max: 29 },
        '塩分': { min: 2.0, max: 2.0 },
        'NH4': { min: 0, max: 5 },
        'NO2': { min: 1.5, max: 2.5 },
        'NO3': { min: 15, max: 20 },
        'Ca': { min: 280, max: 300 },
        'Al': { min: 180, max: 200 },
        'Mg': { min: 700, max: 800 }
    };

    function checkAndHighlight() {
        const inputs = document.querySelectorAll('table.water-quality input[type="text"]');
        inputs.forEach(input => {
            const value = parseFloat(input.value);
            if (!isNaN(value)) {
                const paramName = input.name.split('[').pop().split(']')[0];
                if (standards[paramName]) {
                    if (value < standards[paramName].min || value > standards[paramName].max) {
                        input.classList.add('out-of-range');
                    } else {
                        input.classList.remove('out-of-range');
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkAndHighlight();
        document.querySelectorAll('table.water-quality input[type="text"]').forEach(input => {
            input.addEventListener('input', checkAndHighlight);
        });
    });
    </script>
</body>
</html>