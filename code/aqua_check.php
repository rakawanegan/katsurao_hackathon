<?php
// Conda仮想環境のPythonインタープリタのパス
$pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python';
// サーバー上のPythonスクリプトのパス
$scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/threshold_check_python.py'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ph = $_POST['ph'];
    $do = $_POST['do'];
    $temperature = $_POST['temperature'];
    $salinity = $_POST['salinity'];

    // Pythonスクリプトを実行
    $command = escapeshellcmd("$pythonEnv $scriptPath $ph $do $temperature $salinity");
    $output = shell_exec($command);

    // Pythonスクリプトの出力をそのまま出力
    echo $output;
}
?>