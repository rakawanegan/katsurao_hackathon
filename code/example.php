<!DOCTYPE html>
<html>
<head>
    <title>Pythonスクリプトの実行</title>
</head>
<body>
    <h1>Pythonスクリプト呼び出しフォーム</h1>
    <form method="post">
        <input type="text" name="inputText" placeholder="入力してください">
        <button type="submit" name="submit">送信</button>
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $input = escapeshellarg($_POST['inputText']);
        // Conda仮想環境のPythonインタープリタのパス
        $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python';
        // サーバー上のPythonスクリプトのパス
        $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/example.py';
        
        // コマンドの構築
        $command = "$pythonEnv $scriptPath " . $input;
        $output = shell_exec($command);
        $return_var = null;
        exec($command, $output, $return_var);

        if ($return_var == 0) {
            echo "<h2>結果:</h2>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        } else if ($return_var == 1) {
            echo "<h2>エラーが発生しました。</h2>";
            echo "<p>Pythonスクリプトの実行中にエラーが発生しました。詳細なエラー情報は以下の通りです。</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        } else {
            echo "<h2>エラーが発生しました。</h2>";
            echo "<p>Pythonスクリプトが見つからないか、実行できません。パスを確認してください。</p>";
        }
    }
    ?>
</body>
</html>
