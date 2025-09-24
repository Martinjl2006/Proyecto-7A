<?php
session_start();
session_destroy();
header("Location: inicio.html");
exit();
?>
