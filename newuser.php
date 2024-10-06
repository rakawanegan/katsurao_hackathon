<?php
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $password = $_POST['password'];
    $name = $_POST['name'];

    if (empty($id) || empty($password) || empty($name)) {
        $error = "全てのフィールドを入力してください。";
    } else {
        $file = 'csv/member.csv';
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $last_line = end($lines);
        $last_num = explode(",", $last_line)[0];
        $new_num = $last_num + 1;

        $new_line = "$new_num,$id,$password,$name";
        file_put_contents($file, $new_line . PHP_EOL, FILE_APPEND);

        $success = "新規ユーザーが登録されました。";
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
    <title>新規ユーザー登録</title>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            width: 100%;
            max-width: 500px;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #3498db;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>新規ユーザー登録</h1>
        <?php
        if ($error) {
            echo "<div class='message error'>$error</div>";
        }
        if ($success) {
            echo "<div class='message success'>$success</div>";
        }
        ?>
        <form method="post">
            <label for="id">ID:</label>
            <input type="text" id="id" name="id" required>
            
            <label for="password">パスワード:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="name">名前:</label>
            <input type="text" id="name" name="name" required>
            
            <input type="submit" value="登録">
        </form>
    </div>
</body>
</html>