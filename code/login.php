<?php

// エラーメッセージの初期化
$errorMessage = "";

// ログインボタンが押された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 入力値の取得
  $id = $_POST['id'];
  $password = $_POST['password'];

  // CSVファイルの読み込み
  $file = fopen('csv/member.csv', 'r');
  fgets($file); // ヘッダー行を読み飛ばす

  // IDとパスワードの照合
  $loginFlg = false;
  while (($row = fgetcsv($file)) !== false) {
    if ($row[1] === $id && $row[2] === $password) {
      $loginFlg = true;
      // セッションを開始
      session_start();
      $_SESSION['login'] = true;
      $_SESSION['id'] = $id;
      break;
    }
  }
  fclose($file);

  // ログイン成功時の処理
  if ($loginFlg) {
    // 遷移元のページにリダイレクト
    if (isset($_SESSION['redirect_url'])) {
        $redirectUrl = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        header("Location: $redirectUrl");
        exit;
    } else {
        header("Location: main.php"); // 例: トップページにリダイレクト
        exit;
    }
  } else {
    $errorMessage = 'IDまたはパスワードが間違っています。';
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>ログイン</title>
  <link rel="stylesheet" href="login.css"> 
</head>
<body>
  <div class="container">
    <h1>ログイン</h1>
    <?php if ($errorMessage !== ''): ?>
      <p class="error-message"><?php echo $errorMessage; ?></p>
    <?php endif; ?>
    <form action="" method="post">
      <label for="id">ID:</label><br>
      <input type="text" id="id" name="id" required><br>
      <label for="password">パスワード:</label><br>
      <input type="password" id="password" name="password" required><br><br>
      <input type="submit" value="ログイン">
    </form>
  </div>
</body>
</html>