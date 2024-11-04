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
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>体長測定場所選択</title>
    <link rel="stylesheet" href="map.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.rwdImageMaps/1.6/jquery.rwdImageMaps.min.js"></script> 
</head>
<body>

    <img src="field.png" alt="field" usemap="#fieldMap">
    <map name="fieldMap">
        <area shape="rect" coords="170,399,412,448" href="button1.php"  alt="Button 1">
        <area shape="rect" coords="580,331,822,379" href="len-2-nitrification.php"  alt="Button 2">
        <area shape="rect" coords="170,331,412,379" href="len-1-nitrification.php"  alt="Button 3">
        <area shape="rect" coords="740,13,822,311" href="len-2-2.php"  alt="Button 4">
        <area shape="rect" coords="501,13,583,311" href="len-2-1.php"  alt="Button 5">
        <area shape="rect" coords="321,13,403,311" href="len1-2.php"  alt="Button 6">
        <area shape="rect" coords="15,13,97,311"   href="len-1-1.php"  alt="Button 7">
        <area shape="circle" coords="342,799,75" href="len-3-2-c.php"  alt="Button 8">
        <area shape="circle" coords="84,799,75"   href="len-3-1-c.php"  alt="Button 9">
        <area shape="circle" coords="682,786,89" href="button10.php" alt="Button 10">
        <area shape="circle" coords="342,631,69" href="len-3-2-b.php" alt="Button 11">
        <area shape="circle" coords="84,631,69"   href="len-3-1-b.php" alt="Button 12">
        <area shape="circle" coords="682,641,41" href="button13.php" alt="Button 13">
        <area shape="circle" coords="682,542,41" href="button14.php" alt="Button 14">
        <area shape="circle" coords="370,502,41" href="len-3-2-a.php" alt="Button 15">
        <area shape="circle" coords="55,498,41"   href="len-3-1-a.php" alt="Button 16">
        <area shape="circle" coords="682,440,41" href="button17.php" alt="Button 17">
    </map>

    <script>
        $(document).ready(function(e) {
            $('img[usemap]').rwdImageMaps(); 
        });
    </script>
</body>
</html>