<?php
$password = "Admin123";   // <-- change to your real password
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>
