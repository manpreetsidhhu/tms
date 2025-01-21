<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    $heading = $_POST['heading'];
    $description = $_POST['description'];
    $assigned_to = $_POST['assigned_to']; // Can be NULL for tasks assigned to all users
    $assigned_by = $_SESSION['user_id'];

    $query = "INSERT INTO tasks (heading, description, assigned_by, assigned_to) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $heading, $description, $assigned_by, $assigned_to);
    $stmt->execute();

    header("Location: admin_dashboard.php");
}
?>
