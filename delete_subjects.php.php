<?php
include 'db.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

$conn->query("DELETE FROM subjects");
echo "All subjects deleted.<br><a href='index.html'>Go Back</a>";
?>
