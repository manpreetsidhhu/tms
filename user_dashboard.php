<?php
session_start();
include 'db_connect.php';

// Ensure the user is logged in and has a 'user' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Get logged-in user's information
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

// Fetch announcements
$announcement_query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1";
$announcement_result = $conn->query($announcement_query);
$announcement = $announcement_result->fetch_assoc();

// Fetch all tasks (for everyone)
$task_query = "SELECT * FROM tasks ORDER BY created_at DESC";
$tasks_result = $conn->query($task_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="userdashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($first_name); ?></h1>
            <a href="logout.php">Logout</a>
        </header>

        <!-- Announcements Section -->
        <section class="display-announcements">
            <h3>Announcement:</h3>
            <?php
            // Fetch all announcements from the database
            $announcement_query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1";
            $all_announcements = $conn->query($announcement_query);

            if ($all_announcements->num_rows > 0) {
                while ($row = $all_announcements->fetch_assoc()) {
                    echo "<div class='announcement-card'>";
                    echo "<p class='announcement-message'>" . htmlspecialchars($row['message']) . "</p>";
                    echo "<p class='announcement-time'>Posted by: " . htmlspecialchars($row['posted_by']) . "<br>Posted on " . date("d-m-Y H:i:s", strtotime($row['created_at'])) . "</p>";
                    echo "</div>";
                }
            } else {
                echo "<p>No announcements available.</p>";
            }
            ?>
        </section>

        <!-- Tasks Section -->
        <div class="tasks">
            <h2>Your Tasks</h2>
            <?php if ($tasks_result->num_rows > 0): ?>
                <?php while ($task = $tasks_result->fetch_assoc()): ?>
                    <div class="task">
                        <div class="task-header">
                            <h3><?php echo htmlspecialchars($task['heading']); ?></h3>
                            <p><?php echo $task['task_id']; ?></p>
                        </div>
                        <p><?php echo htmlspecialchars($task['description']); ?></p>
                        <small>Posted on: <?php echo htmlspecialchars($task['created_at']); ?></small>
                        <p>Status: <strong><?php echo ucfirst($task['status']); ?></strong></p>
                        <?php if ($task['status'] === 'pending'): ?>
                            <form action="mark_done.php" method="POST">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to mark this task as done?')">Mark as Done</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No tasks available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>