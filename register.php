<?php
session_start();
require 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'editor';

    if ($username === '' || $password === '') {
        $error = "Username and password are required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = "Username must be between 3 and 50 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can contain only letters, numbers, and underscores.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!in_array($role, ['admin', 'editor'], true)) {
        $error = "Invalid role selected.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);

        if ($stmt->fetch()) {
            $error = "Username already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare(
                "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)"
            );
            $insert->execute([
                ':username' => $username,
                ':password' => $hash,
                ':role'     => $role,
            ]);
            $success = "Account created successfully! You can log in now.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Task 4 Security</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 420px; margin: 40px auto; padding: 0 16px; }
        input, select, button { width: 100%; padding: 9px; margin: 6px 0 14px; box-sizing: border-box; }
        .err { color: #b91c1c; font-weight: bold; }
        .ok { color: #15803d; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Register (Task 4)</h2>
    <?php if ($error): ?>
        <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST" action="" onsubmit="return checkForm()">
        <label>Username (3-50 chars, A-Z, 0-9, _)</label>
        <input type="text" id="username" name="username" required minlength="3" maxlength="50">

        <label>Password (min 6 chars)</label>
        <input type="password" id="password" name="password" required minlength="6">

        <label>Role</label>
        <select name="role" id="role">
            <option value="editor">Editor (Create & View Tickets)</option>
            <option value="admin">Admin (Create, View, Resolve & Delete Tickets)</option>
        </select>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>

    <script>
        function checkForm() {
            const u = document.getElementById("username").value.trim();
            const p = document.getElementById("password").value;
            if (!/^[a-zA-Z0-9_]{3,50}$/.test(u)) {
                alert("Enter a valid username (3-50 letters, numbers, or underscores).");
                return false;
            }
            if (p.length < 6) {
                alert("Password must be at least 6 characters.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>