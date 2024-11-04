<!DOCTYPE html>
<html>
<head>
  <title>会員サイト</title>
  <link rel="stylesheet" href="header.css?v=<?php echo filemtime('header.css'); ?>"> 
</head>
<body>
  <header>
    <nav>
      <ul>
        <li><a href="main.php">メインメニュー</a></li>
        <li><a href="list.php">データ一覧</a></li>
        <?php if (isset($_SESSION['login']) && $_SESSION['login'] === true): ?> 
        <li><a href="logout.php">ログアウト</a></li>
        <?php else: ?>
        <li><a href="login.php">ログイン</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>
  </body>
</html>