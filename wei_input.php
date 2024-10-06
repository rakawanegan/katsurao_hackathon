
<?php
// セッション開始
session_start();

// ログイン状態の確認
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  // ログインしていない場合は、ログインページにリダイレクト
  $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // 現在のページのURLをセッションに保存
  header("Location: login.php");
  exit;
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
    <title>数値入力</title>
    <link rel="stylesheet" href="mainstyle.css">
</head>
<body>
    <div class="container">
        <button class="btn" onclick="location.href='wei-1.php'">1系</button>
        <button class="btn" onclick="location.href='wei-2.php'">2系</button>
        <button class="btn" onclick="location.href='wei-3.php'">3系</button>
        <button class="btn" onclick="location.href='wei-4.php'">4系</button>
    </div>
</body>
</html>