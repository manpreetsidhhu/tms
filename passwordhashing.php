<?php
$unhashed_password = "tatvaorg999";
$hashed_password = password_hash($unhashed_password, PASSWORD_DEFAULT);
echo $hashed_password;
?>