<?php
// Ensure you have proper validation and permission checks here
if (isset($_GET['id'])) {
    $taskId = $_GET['id'];

    // Your database connection
    $conn = new mysqli('localhost', 'username', 'password', 'database');

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Delete query
    $sql = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $taskId);

    if ($stmt->execute()) {
        // Redirect back to the tasks page after deletion
        header("Location: tasks_page.php"); // Replace with your tasks page
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
