<?php
include 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $message = '❗ Please fill in both fields.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM users WHERE name = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = '❌ Username already taken.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                $message = '✅ Registration successful. <a href="login.php">Login</a>';
            } else {
                $message = '❌ Something went wrong. Try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<style>
  :root {
    --primary: #4f46e5;
    --primary-dark: #4338ca;
    --bg: #f3f4f6;
    --card-bg: #ffffff;
    --error: #dc2626;
    --success: #16a34a;
  }
  * { box-sizing: border-box; }
  body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: var(--bg);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
  }
  .register-box {
    width: 350px;
    background: var(--card-bg);
    padding: 35px 45px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    text-align: center;
  }
  h2 {
    margin: 0 0 24px;
    color: #333;
  }
  input[type="text"],
  input[type="password"] {
    width: 100%;
    padding: 11px 14px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
  }
  button {
    width: 100%;
    padding: 11px 0;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 12px;
  }
  button:hover { background: var(--primary-dark); }
  .message {
    margin-top: 18px;
    font-size: 14px;
    color: var(--error);
  }
  .message.success { color: var(--success); }
  .message a { color: var(--primary-dark); text-decoration: none; font-weight: 600; }
</style>
</head>
<body>

<div class="register-box">
  <h2>Create Account</h2>

  <form method="POST">
    <input type="text" name="username" placeholder="Enter Username" required>
    <input type="password" name="password" placeholder="Enter Password" required>
    <button type="submit">Register</button>
  </form>

  <?php if ($message): ?>
    <div class="message <?= str_contains($message,'✅') ? 'success' : '' ?>">
      <?= $message ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>



