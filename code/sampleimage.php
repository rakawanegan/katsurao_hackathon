<!DOCTYPE html>
<html>
<head>
    <title>気温グラフ生成</title>
</head>
<body>
    <h1>気温グラフ生成フォーム</h1>
    <form method="post" action="sampleimage.php">
        <label for="temp1">1日目:</label>
        <input type="number" name="temp1" id="temp1" required><br>

        <label for="temp2">2日目:</label>
        <input type="number" name="temp2" id="temp2" required><br>

        <label for="temp3">3日目:</label>
        <input type="number" name="temp3" id="temp3" required><br>

        <label for="temp4">4日目:</label>
        <input type="number" name="temp4" id="temp4" required><br>

        <label for="temp5">5日目:</label>
        <input type="number" name="temp5" id="temp5" required><br>

        <button type="submit" name="submit">グラフ生成</button>
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $temps = array($_POST['temp1'], $_POST['temp2'], $_POST['temp3'], $_POST['temp4'], $_POST['temp5']);
        $pythonEnv = '/home/xs29345872/miniconda3/envs/katsupy/bin/python'; 
        $scriptPath = '/home/xs29345872/globalsteptest.com/public_html/katsurao/create.py'; 

        $command = "$pythonEnv $scriptPath " . implode(" ", $temps);
        shell_exec($command); 

        $timestamp = time();
        echo "<img src='image/sampleimage.png?t=$timestamp' alt='気温グラフ'>";
    }
    ?>
</body>
</html>