<?php
session_start();
include 'db_connect.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all users for task assignment
$query_users = "SELECT id, first_name, last_name FROM users WHERE role = 'user'";
$result_users = $conn->query($query_users);
$users = $result_users->fetch_all(MYSQLI_ASSOC);
$query_admins = "SELECT id, first_name, last_name FROM users WHERE role = 'admin'";
$result_admins = $conn->query($query_admins);
$admins = $result_admins->fetch_all(MYSQLI_ASSOC);

// Fetch all tasks posted by admin
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all'; // 'all', 'done', 'pending'

$query_tasks = "SELECT t.id, t.task_id, t.heading, t.description, t.status, t.created_at, t.assigned_to, 
                u.first_name AS user_name 
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.created_by = ?";

if ($filter === 'done') {
    $query_tasks .= " AND t.status = 'done'";
} elseif ($filter === 'pending') {
    $query_tasks .= " AND t.status = 'pending'";
}

if (!empty($search)) {
    $query_tasks .= " AND (t.heading LIKE ? OR t.description LIKE ? OR u.first_name LIKE ?)";
}

$query_tasks .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query_tasks);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("isss", $_SESSION['user_id'], $search_param, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $_SESSION['user_id']);
}

$stmt->execute();
$result_tasks = $stmt->get_result();
$tasks = $result_tasks->fetch_all(MYSQLI_ASSOC);

// Fetch all registered users
$query_all_users = "SELECT id, first_name, last_name, email FROM users WHERE role = 'user'";
$result_all_users = $conn->query($query_all_users);
$registered_users = $result_all_users->fetch_all(MYSQLI_ASSOC);
$query_all_admins = "SELECT id, first_name, last_name, email FROM users WHERE role = 'admin'";
$result_all_admins = $conn->query($query_all_admins);
$registered_admins = $result_all_admins->fetch_all(MYSQLI_ASSOC);

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $heading = $_POST['heading'];
    $description = $_POST['description'];
    $assigned_to = $_POST['assigned_to'] === 'all' ? null : $_POST['assigned_to'];

    // Insert without task_id to get the last inserted ID
    $query_insert_task = "INSERT INTO tasks (heading, description, status, created_at, created_by, assigned_to) 
                          VALUES (?, ?, 'pending', NOW(), ?, ?)";
    $stmt = $conn->prepare($query_insert_task);
    $stmt->bind_param("ssii", $heading, $description, $_SESSION['user_id'], $assigned_to);
    $stmt->execute();

    // Get the last inserted task ID
    $last_insert_id = $stmt->insert_id;

    // Generate task_id using the last inserted ID
    $task_id = 'TATG' . $last_insert_id;

    // Update the task with the generated task_id
    $query_update_task_id = "UPDATE tasks SET task_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query_update_task_id);
    $stmt->bind_param("si", $task_id, $last_insert_id);
    $stmt->execute();

    header("Location: admin_dashboard.php");
    exit;
}

// Handle task deletion
if (isset($_GET['delete_task'])) {
    $task_id = $_GET['delete_task'];

    $query_delete_task = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($query_delete_task);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();

    header("Location: admin_dashboard.php");
    exit;
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $userId = $_GET['delete_user'];

    // Delete related tasks first to avoid foreign key constraint violations
    $stmt = $conn->prepare("DELETE FROM tasks WHERE assigned_to = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Now delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    echo "<script>alert('User deleted successfully');</script>";
    echo "<script>window.location.href = 'admin_dashboard.php';</script>";
}

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password
    $role = $_POST['role'];

    // Check if username is set
    if (isset($_POST['username'])) {
        $username = $_POST['username'];  // Collect the username
    } else {
        // Handle the case where the username is missing
        echo "<script>alert('Username is required!');</script>";
        exit; // Stop further execution if username is missing
    }

    // Check if the email already exists
    $query_check_email = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query_check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $query_check_username = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($query_check_username);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $re = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already exists!');</script>";
    } else if ($re->num_rows > 0) {
        echo "<script>alert('Username already exists!');</script>";
    } else {
        // Insert the new user into the database
        $query_insert_user = "INSERT INTO users (first_name, last_name, email, password, role, username) 
                              VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query_insert_user);
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $password, $role, $username);
        $stmt->execute();

        echo "<script>alert('User added successfully!');</script>";
        header("Location: admin_dashboard.php");
        exit;
    }

    $query_check_username = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($query_check_username);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Username already exists!');</script>";
    } else {
        // Insert the new user into the database
        $query_insert_user = "INSERT INTO users (first_name, last_name, email, password, role, username) 
                              VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query_insert_user);
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $password, $role, $username);
        $stmt->execute();

        echo "<script>alert('User added successfully!');</script>";
        header("Location: admin_dashboard.php");
        exit;
    }
}
// Handle the form submission
if (isset($_POST['create_announcement'])) {
    $new_announcement = trim($_POST['announcement']);
    $posted_by = $_SESSION['first_name']; // Assuming the admin/user name is stored in the session.

    if (!empty($new_announcement)) {
        $new_announcement = $conn->real_escape_string($new_announcement);
        $posted_by = $conn->real_escape_string($posted_by);

        // Insert the new announcement into the database
        $query = "INSERT INTO announcements (message, created_at, posted_by) VALUES ('$new_announcement', NOW(), '$posted_by')";
        if ($conn->query($query) === TRUE) {
            echo "<script>alert('Announcement created successfully!');</script>";
            echo "<script>window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('Error creating announcement: " . $conn->error . "');</script>";
        }
    } else {
        echo "<script>alert('Announcement cannot be empty.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_dashboard.css">
</head>

<body>
    <header>
        <h1>Welcome, <?php echo $_SESSION['first_name']; ?>!</h1>
        <a href="logout.php" class="logout">Logout</a>
    </header>

    <nav class="bottom-navbar">
        <button onclick="openTab('tasks')">Tasks</button>
        <button onclick="openTab('users')">Users</button>
        <button onclick="openTab('announcements')">Announcements</button>
    </nav>

    <div id="tasks" class="tab-content">
        <!-- Task Management Section -->
        <section class="tasks-section">
            <h2>Tasks Management</h2>
            <h3>Create Task</h3>
            <form method="POST" class="create-task-form">
                <input type="text" name="heading" placeholder="Task heading" required>
                <textarea name="description" placeholder="Task description" required></textarea>
                <select name="assigned_to">
                    <option value="all">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $user['first_name'] . ' ' . $user['last_name'] . " (User)"; ?></option>
                    <?php endforeach; ?>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?php echo $admin['id']; ?>"><?php echo $admin['first_name'] . ' ' . $admin['last_name'] . " (Admin)"; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="create_task">Post Task</button>
            </form>

            <h3>Tasks</h3>
            <form method="GET" class="filter-tasks-form">
                <input type="text" name="search" placeholder="Search tasks..." value="<?php echo $search; ?>">
                <select name="filter">
                    <option value="all" <?php if ($filter === 'all') echo 'selected'; ?>>All</option>
                    <option value="done" <?php if ($filter === 'done') echo 'selected'; ?>>Done</option>
                    <option value="pending" <?php if ($filter === 'pending') echo 'selected'; ?>>Pending</option>
                </select>
                <button type="submit">Filter</button>
            </form>

            <div class="tasks-container">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <h3><?php echo $task['heading']; ?></h3>
                            <p><?php echo $task['task_id']; ?></p>
                        </div>
                        <div class="task-body">
                            <p><?php echo $task['description']; ?></p>
                        </div>
                        <div class="task-footer">
                            <span>Assigned to: <?php echo $task['user_name'] ?? 'All Users'; ?></span>
                            <span>Status: <?php echo ucfirst($task['status']); ?></span>
                            <span><?php echo $task['created_at']; ?></span>
                            <!-- Add a delete button -->
                            <button class="delete-btn" onclick="confirmDelete(<?php echo $task['id']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div id="users" class="tab-content" style="display:none;">
        <!-- User Management Section -->
        <section class="add-user-section">
            <h2>Add User/Admin</h2>
            <form method="POST" class="add-user-form">
                <input type="text" name="username" placeholder="username" required>
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="password" placeholder="Password" required>
                <select name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="add_user">Add User</button>
            </form>
        </section>

        <section class="users-section">
            <h2>Users</h2>
            <div class="users-container">
                <?php foreach ($registered_users as $user): ?>
                    <div class="user-card">
                        <div class="user-info">
                            <h4><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h4>
                            <p><?php echo "(User)"; ?></p>
                            <p><?php echo $user['email']; ?></p>
                        </div>
                        <div class="user-actions">
                            <a href="admin_dashboard.php?delete_user=<?php echo $user['id']; ?>" class="delete-user" onclick="return confirm('Are you sure?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($registered_admins as $admin): ?>
                    <div class="user-card">
                        <div class="user-info">
                            <h4><?php echo $admin['first_name'] . ' ' . $admin['last_name']; ?></h4>
                            <p><?php echo "(Admin)"; ?></p>
                            <p><?php echo $admin['email']; ?></p>
                        </div>
                        <div class="user-actions">
                            <a href="admin_dashboard.php?delete_user=<?php echo $admin['id']; ?>" class="delete-user" onclick="return confirm('Are you sure?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div id="announcements" class="tab-content" style="display:none;">
        <!-- Announcement Section -->
        <section class="announcement-section">
            <h2>Announcement</h2>
            <form method="POST" class="announcement-form">
                <textarea name="announcement" rows="3" placeholder="Write an announcement..."></textarea>
                <button type="submit" name="create_announcement">Post Announcement</button>
            </form>
        </section>

        <!-- Display Announcements Section -->
        <section class="display-announcements">
            <h3>Announcement:</h3><br>
            <hr>
            <?php
            // Fetch all announcements from the database
            $all_announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
            if ($all_announcements->num_rows > 0) {
                while ($row = $all_announcements->fetch_assoc()) {
                    echo "<br><div class='announcement-card'>";
                    echo "<p class='announcement-message'>" . htmlspecialchars($row['message']) . "</p><br>";
                    echo "<p class='announcement-time'>Posted by: " . htmlspecialchars($row['posted_by']) . "<br>Posted on " . date("d-m-Y H:i:s", strtotime($row['created_at'])) . "</p><br><hr>";
                    echo "</div>";
                }
            } else {
                echo "<p>No announcements available.</p>";
            }
            ?>
        </section>

    </div>

    <script>
        function openTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.getElementById(tabId).style.display = 'block';
        }

        function confirmDelete(taskId) {
            if (confirm("Are you sure you want to delete this task?")) {
                window.location.href = "admin_dashboard.php?delete_task=" + taskId;
            }
        }
    </script>
</body>

</html>