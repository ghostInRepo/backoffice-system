<?php
// OTP email template
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OTP Code</title>
  <style>
    .card { font-family: Arial, sans-serif; border:1px solid #eee; padding:20px; border-radius:8px; }
    .otp { font-size:28px; font-weight:bold; color:#2b6cb0; }
  </style>
</head>
<body>
  <div class="card">
    <p>Hello,</p>
    <p>Your OTP code is:</p>
    <p class="otp"><?=htmlspecialchars($otp)?></p>
    <p>This code is valid for 5 minutes.</p>
  </div>
</body>
</html>
