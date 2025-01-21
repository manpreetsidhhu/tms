<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            echo "Password is correct!"; // Debugging output
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];

            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
        } else {
            header("Location: index.html");
        }
    } else {
        echo "User not found.";
    }
} else {
    header("Location: index.html");
}


?>
