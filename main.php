<?php
// セッション開始
session_start();

// ログイン状態の確認
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
  // ログインしていない場合は、ログインページにリダイレクト
  //$_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // 現在のページのURLをセッションに保存
  //header("Location: login.php");
  //exit;
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
    <title>メイン画面</title>
    <link rel="stylesheet" href="mainstyle.css">
</head>
<body>
    <div class="container">
        <button class="btn" onclick="location.href='num_input.php'">水質入力</button>
        <button class="btn" onclick="location.href='len_input.php'">体長入力</button>
        <button class="btn" onclick="location.href='wei_input.php'">体重入力</button>
        <button class="btn" onclick="location.href='graphhub.php'">グラフの作成</button>
        <button class="btn" onclick="location.href='newlist.php'">新しいクールにする</button>
        <button class="btn" onclick="location.href='newuser.php'">ユーザー追加</button>
    </div>
</body>
</html>