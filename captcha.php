<?php
session_start();
  require_once "assets\php\purecaptcha.php";

  $pureCaptcha = new PureCaptcha();

  $_SESSION['captcha'] = $pureCaptcha->show();

?>
