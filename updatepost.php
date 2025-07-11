<?php
session_start();
include 'db.php';

// Initialize variables
$errors = [];
$success = '';
$post = null;

// Step 1: Get post ID from URL and validate
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("❗ Invalid post ID.");
}

// Step 2: Fetch post data using prepared statement
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❗ Post not found.");
}

$post = $result->fetch_assoc();
$stmt->close();

// Step 3: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image = $post['image']; // default to current image

    // Validation
    if (empty($title)) {
        $errors[] = "Title is required.";
    } elseif (strlen($title) > 255) {
        $errors[] = "Title must be less than 255 characters.";
    }

    if (empty($description)) {
        $errors[] = "Description is required.";
    } elseif (strlen($description) > 5000) {
        $errors[] = "Description must be less than 5000 characters.";
    }

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = 'media/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];
        $fileTmpName = $_FILES['image']['tmp_name'];
        $fileError = $_FILES['image']['error'];

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error: " . $fileError;
        } else {
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            if ($fileSize > $maxFileSize) {
                $errors[] = "File size too large. Maximum allowed size is 5MB.";
            } else {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $mimeType = mime_content_type($fileTmpName);

                if (!in_array($ext, $allowedExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
                    $errors[] = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP files are allowed.";
                } else {
                    $newFileName = uniqid() . '_' . time() . '.' . $ext;
                    $targetFile = $uploadDir . $newFileName;

                    if (move_uploaded_file($fileTmpName, $targetFile)) {
                        if (!empty($post['image']) && $post['image'] !== $newFileName && file_exists($uploadDir . $post['image'])) {
                            unlink($uploadDir . $post['image']);
                        }
                        $image = $newFileName;
                    } else {
                        $errors[] = "Failed to upload image.";
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE posts SET title = ?, description = ?, image = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $title, $description, $image, $id);

        if ($stmt->execute()) {
            $success = "✅ Post updated successfully!";
            $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $post = $result->fetch_assoc();
        } else {
            $errors[] = "Failed to update post. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Update Post</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    /* [unchanged CSS from previous] */
    * { box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      margin: 0;
      padding: 20px;
      min-height: 100vh;
    }
    .container {
      max-width: 800px;
      margin: 0 auto;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      overflow: hidden;
    }
    .header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      text-align: center;
    }
    .form-container {
      padding: 40px;
    }
    .form-group { margin-bottom: 25px; }
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }
    input[type="text"], textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.3s ease;
    }
    input[type="text"]:focus, textarea:focus {
      outline: none;
      border-color: #667eea;
    }
    textarea {
      resize: vertical;
      min-height: 120px;
    }
    input[type="file"] {
      width: 100%;
      padding: 10px;
      border: 2px dashed #ccc;
      border-radius: 8px;
      background: #f9f9f9;
      cursor: pointer;
    }
    .current-image {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 15px;
    }
    .btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 15px 30px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
    }
    .success, .errors {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .errors {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .errors ul { margin: 0; padding-left: 20px; }
    .back-link {
      display: inline-block;
      margin-top: 20px;
      color: #667eea;
      font-weight: 600;
    }
    @media (max-width: 600px) {
      .container { margin: 10px; }
      .form-container { padding: 20px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>📝 Edit Post</h2>
    </div>
    <div class="form-container">
      <?php if (!empty($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="errors"><ul>
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="title">Title:</label>
          <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required maxlength="255">
        </div>

        <div class="form-group">
          <label for="description">Description:</label>
          <textarea id="description" name="description" required maxlength="5000"><?= htmlspecialchars($post['description'] ?? '') ?></textarea>
        </div>

        <?php if (!empty($post['image'])): ?>
          <div class="form-group">
            <label>Current Image:</label>
            <img src="media/<?= htmlspecialchars($post['image']) ?>" alt="Current Image" class="current-image">
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label for="image">Change Image:</label>
          <input type="file" id="image" name="image" accept="image/*">
          <small style="color: #666; display: block;">Max size: 5MB. Allowed: JPG, PNG, GIF, WebP</small>
        </div>

        <button type="submit" class="btn">Update Post</button>
      </form>

      <a href="dashboard.php" class="back-link">← Back to dashboard</a>
    </div>
  </div>
</body>
</html>
