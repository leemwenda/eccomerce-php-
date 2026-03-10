<?php
session_start();

// Destroy the session completely
session_unset();
session_destroy();

// Redirect to main login page
header('Location: ../pages/login.php');
exit;