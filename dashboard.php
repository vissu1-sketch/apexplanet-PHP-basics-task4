<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'];

$error = "";
$success = "";

// Create new ticket (Prepared Statement + Server Validation)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_ticket'])) {
    $subject     = trim($_POST['subject'] ?? '');
    $category    = $_POST['category'] ?? 'Bug';
    $priority    = $_POST['priority'] ?? 'Low';
    $description = trim($_POST['description'] ?? '');

    if (strlen($subject) < 3 || strlen($subject) > 150) {
        $error = "Subject must be between 3 and 150 characters.";
    } elseif (!in_array($category, ['Bug', 'Feature', 'Account'], true)) {
        $error = "Invalid category.";
    } elseif (!in_array($priority, ['Low', 'Medium', 'High'], true)) {
        $error = "Invalid priority.";
    } elseif (strlen($description) < 5) {
        $error = "Description must be at least 5 characters.";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tickets (user_id, subject, category, priority, description)
             VALUES (:user_id, :subject, :category, :priority, :description)"
        );
        $stmt->execute([
            ':user_id'     => $userId,
            ':subject'     => $subject,
            ':category'    => $category,
            ':priority'    => $priority,
            ':description' => $description,
        ]);
        $success = "Ticket submitted securely!";
    }
}

// Role-Based Action: Toggle Status (Admin only)
if (isset($_GET['toggle_id'])) {
    if ($role !== 'admin') {
        $error = "Access Denied: Only Admin can change ticket status.";
    } else {
        $ticketId = (int) $_GET['toggle_id'];
        $stmt = $pdo->prepare(
            "UPDATE tickets SET status = IF(status = 'Open', 'Resolved', 'Open') WHERE id = :id"
        );
        $stmt->execute([':id' => $ticketId]);
        $success = "Ticket status updated.";
    }
}

// Role-Based Action: Delete Ticket (Admin only)
if (isset($_GET['delete_id'])) {
    if ($role !== 'admin') {
        $error = "Access Denied: Only Admin can delete tickets.";
    } else {
        $ticketId = (int) $_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = :id");
        $stmt->execute([':id' => $ticketId]);
        $success = "Ticket deleted.";
    }
}

// Fetch tickets with prepared query
$stmt = $pdo->prepare(
    "SELECT t.id, t.subject, t.category, t.priority, t.description, t.status, t.created_at, u.username
     FROM tickets t
     JOIN users u ON t.user_id = u.id
     ORDER BY t.created_at DESC"
);
$stmt->execute();
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ticket Dashboard - Task 4</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 860px; margin: 30px auto; padding: 0 16px; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        input, select, textarea, button { width: 100%; padding: 8px; margin: 6px 0 12px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .err { color: #b91c1c; font-weight: bold; }
        .ok { color: #15803d; font-weight: bold; }
    </style>
</head>
<body>
    <div class="top">
        <div>
            <h2>Support Ticket Dashboard</h2>
            <p>User: <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong> | Role: <strong><?= htmlspecialchars(strtoupper($role), ENT_QUOTES, 'UTF-8') ?></strong></p>
        </div>
        <div><a href="logout.php">Logout</a></div>
    </div>

    <?php if ($error): ?>
        <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST" action="" onsubmit="return validateTicket()">
        <h3>Create New Ticket</h3>
        <label>Subject (3-150 chars)</label>
        <input type="text" id="subject" name="subject" required minlength="3" maxlength="150">

        <label>Category</label>
        <select name="category">
            <option value="Bug">Bug</option>
            <option value="Feature">Feature</option>
            <option value="Account">Account</option>
        </select>

        <label>Priority</label>
        <select name="priority">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select>

        <label>Description (min 5 chars)</label>
        <textarea id="description" name="description" rows="3" required minlength="5"></textarea>

        <button type="submit" name="create_ticket">Submit Ticket</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Subject</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>By</th>
                <?php if ($role === 'admin'): ?>
                    <th>Admin Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?= (int) $t['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($t['subject'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <small><?= htmlspecialchars($t['description'], ENT_QUOTES, 'UTF-8') ?></small>
                    </td>
                    <td><?= htmlspecialchars($t['category'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($t['priority'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($t['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($t['username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <?php if ($role === 'admin'): ?>
                        <td>
                            <a href="dashboard.php?toggle_id=<?= (int) $t['id'] ?>">Toggle Status</a> |
                            <a href="dashboard.php?delete_id=<?= (int) $t['id'] ?>" onclick="return confirm('Delete this ticket?')">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        function validateTicket() {
            const sub = document.getElementById("subject").value.trim();
            const desc = document.getElementById("description").value.trim();
            if (sub.length < 3) {
                alert("Subject must be at least 3 characters.");
                return false;
            }
            if (desc.length < 5) {
                alert("Description must be at least 5 characters.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>