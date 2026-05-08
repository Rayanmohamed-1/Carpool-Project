<?php
// start the session so we can access it
session_start();

// clear all the session data
session_unset();

// destroy the session completely
session_destroy();

// send user back to home page
header("Location: home.html");
exit();
?>