<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'user') {
    $task_id = $_POST['task_id'];

    $query = "UPDATE tasks SET status = 'done' WHERE id = ? AND assigned_to = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
    $stmt->execute();

    header("Location: user_dashboard.php");
}
?>