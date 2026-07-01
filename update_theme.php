<?php
session_start();
if (isset($_GET['theme'])) {
    $_SESSION['settings']['theme'] = $_GET['theme'];
}
?>