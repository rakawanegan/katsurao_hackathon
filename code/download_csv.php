<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

function readCsvWithoutRemarks($fileName) {
    $data = [];
    if (($handle = fopen($fileName, "r")) !== FALSE) {
        while (($row = fgetcsv($handle)) !== FALSE) {
            // 備考欄を除外（最後の列を削除）
            array_pop($row);
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

function readCsvWithRemarks($fileName) {
    return array_map('str_getcsv', file($fileName));
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

    $data = [];
    foreach ($filePatterns[$item][$system] as $fileName => $displayName) {
        if (file_exists($fileName)) {
            if ($item === 'water') {
                $data[$displayName] = readCsvWithoutRemarks($fileName);
            } else {
                $data[$displayName] = readCsvWithRemarks($fileName);
            }
        }
    }

    // ファイル名用に期間を修正
    $displayPeriod = str_replace(['1st', '2st', '3st'], ['1st', '2nd', '3rd'], $period);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $displayPeriod . '_' . $system . '系_' . $item . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // BOMを出力
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($item === 'water') {
        $headers = ['日付', 'Day', 'PL'];
        foreach ($filePatterns[$item][$system] as $displayName) {
            $headers[] = $displayName . ' pH';
            $headers[] = $displayName . ' DO';
            $headers[] = $displayName . ' 温度';
            $headers[] = $displayName . ' 塩分';
            if ($displayName === '硝化槽') {
                $headers[] = 'NH4';
                $headers[] = 'NO2';
                $headers[] = 'NO3';
                $headers[] = 'Ca';
                $headers[] = 'Al';
                $headers[] = 'Mg';
            }
        }
        fputcsv($output, $headers);

        $maxRows = max(array_map('count', $data));
        for ($i = 1; $i < $maxRows; $i++) {
            $rowData = [];
            $firstFile = reset($data);
            if (isset($firstFile[$i])) {
                $rowData = array_slice($firstFile[$i], 0, 3);
            } else {
                $rowData = ['', '', ''];
            }
            foreach ($filePatterns[$item][$system] as $displayName) {
                if (isset($data[$displayName][$i])) {
                    $rowData = array_merge($rowData, array_slice($data[$displayName][$i], 3));
                } else {
                    $rowData = array_merge($rowData, array_fill(0, $displayName === '硝化槽' ? 10 : 4, ''));
                }
            }
            fputcsv($output, $rowData);
        }
    } else {
        $headers = ['PL'];
        foreach ($filePatterns[$item][$system] as $displayName) {
            $headers[] = $displayName;
        }
        fputcsv($output, $headers);

        $plValues = [];
        foreach ($data as $tankData) {
            foreach ($tankData as $dataRow) {
                if (isset($dataRow[0]) && $dataRow[0] !== 'PL') {
                    $plValues[] = $dataRow[0];
                }
            }
        }
        $plValues = array_unique($plValues);
        sort($plValues);

        foreach ($plValues as $pl) {
            $rowData = [$pl];
            foreach ($filePatterns[$item][$system] as $displayName) {
                $value = '';
                foreach ($data[$displayName] as $dataRow) {
                    if (isset($dataRow[0]) && $dataRow[0] == $pl) {
                        $value = isset($dataRow[1]) ? $dataRow[1] : '';
                        break;
                    }
                }
                $rowData[] = $value;
            }
            fputcsv($output, $rowData);
        }
    }

    fclose($output);
    exit;
}
?>