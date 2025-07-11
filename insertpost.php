<?php
session_start();
include 'db.php'; // Make sure this file correctly connects to your database

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']); // Ensure user_id is an integer

// Initialize variables for sticky form
$title = '';
$content = '';

// Generate CSRF token if not already set for the session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token. Please try again.";
        header("Location: dashboard.php"); // Redirect back to dashboard with error
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        // Use a session variable for sticky form content in case of error
        $_SESSION['post_form_title'] = $title;
        $_SESSION['post_form_content'] = $content;
        $_SESSION['error_message'] = "❗ Title and Content cannot be empty.";
        // No redirect here, stay on page to show error and sticky form
    } else {
        // Using prepared statements for security
        $stmt = mysqli_prepare($conn, "INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $title, $content);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "✅ Post added successfully!";
                // Clear sticky form data after successful submission
                unset($_SESSION['post_form_title']);
                unset($_SESSION['post_form_content']);
                header("Location: dashboard.php"); // Redirect to dashboard
                exit;
            } else {
                $_SESSION['error_message'] = "❌ Error adding post: " . mysqli_error($conn);
                // Keep sticky form data
                $_SESSION['post_form_title'] = $title;
                $_SESSION['post_form_content'] = $content;
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error_message'] = "❌ Database statement preparation failed: " . mysqli_error($conn);
            // Keep sticky form data
            $_SESSION['post_form_title'] = $title;
            $_SESSION['post_form_content'] = $content;
        }
    }
}

// Retrieve sticky form data if available
$title = $_SESSION['post_form_title'] ?? $title;
$content = $_SESSION['post_form_content'] ?? $content;

// Retrieve and display error message from session if any
$current_error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']); // Clear it after displaying
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Post</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Re-use the new, more consistent CSS styles from dashboard.php */
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
        display: flex;
        justify-content: center;
        align-items: center; /* Center vertically */
        padding: 20px; /* Add some padding */
    }

    .container {
        max-width: 800px;
        width: 100%;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 30px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .form-title {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(45deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 30px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
        display: block;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid rgba(102, 126, 234, 0.2);
        border-radius: 10px;
        font-size: 1rem;
        background: rgba(255, 255, 255, 0.8);
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        outline: none;
    }

    textarea.form-control {
        min-height: 150px;
        resize: vertical;
    }

    .action-btn {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        padding: 12px 25px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .back-btn {
        background: #6c757d;
        margin-left: 10px;
    }

    .back-btn:hover {
        background: #5a6268;
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
    }

    .alert {
        border-radius: 15px;
        margin-bottom: 20px;
        border: none;
        padding: 15px 20px; /* Adjust padding */
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: linear-gradient(45deg, #56ab2f, #a8e6cf);
        color: white;
    }

    .alert-danger {
        background: linear-gradient(45deg, #ff6b6b, #ee5a52);
        color: white;
    }
  </style>
</head>
<body>

<div class="container">
  <h1 class="form-title">Create a New Blog Post</h1>

  <?php if ($current_error_message): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i> <?= $current_error_message ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <div class="form-group">
      <label for="title" class="form-label">Post Title</label>
      <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($title) ?>" required>
    </div>

    <div class="form-group">
      <label for="content" class="form-label">Content</label>
      <textarea name="content" id="content" class="form-control" rows="10" required><?= htmlspecialchars($content) ?></textarea>
    </div>

    <div>
      <button type="submit" name="create_post" class="action-btn">
        <i class="fas fa-plus"></i> Publish Post
      </button>
      <a href="dashboard.php" class="action-btn back-btn">
        <i class="fas fa-arrow-left"></i> Cancel
      </a>
    </div>
  </form>
</div>

</body>
</html>
