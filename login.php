<?php
session_start();
include "db.php"; 

$message = '';

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE name = '$name'";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        $message = "❗ Error: {$conn->error}";
    } else {
        if ($result->num_rows > 0) {
            $row = mysqli_fetch_assoc($result);

            // If using hashed password during registration, use password_verify
            // if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                header("Location: dashboard.php");
                exit;
            // } else {
            //     $message = "❌ Incorrect password.";
            // }
        } else {
            $message = "❌ User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    body {
      background-color: #f4f4f4;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .login-box {
      background: #fff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
      width: 300px;
    }
    h2 {
      margin-bottom: 20px;
      text-align: center;
      color: #333;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }
    input[type="submit"] {
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      border: none;
      color: white;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
    }
    input[type="submit"]:hover {
      background-color: #0056b3;
    }
    .message {
      color: red;
      text-align: center;
      font-size: 14px;
    }
    .register-link {
      text-align: center;
      display: block;
      margin-top: 10px;
      font-size: 13px;
    }
  </style>
</head>
<body>

<div class="login-box">
  <h2>Login</h2>
  <form action="login.php" method="POST">
    <input type="text" name="name" placeholder="Username" required />
    <input type="password" name="password" placeholder="Password" required />
    <input type="submit" name="submit" value="Login" />
  </form>

  <?php if ($message): ?>
    <div class="message"><?= $message ?></div>
  <?php endif; ?>

  <a class="register-link" href="register.php">Don't have an account? Register</a>
</div>

</body>
</html>



