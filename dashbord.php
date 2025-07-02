<?php
session_start();
include "db.php";

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Search & Pagination Logic
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

// Escaped search for SQL
$search_safe = mysqli_real_escape_string($conn, $search);
$search_condition = $search ? "AND (title LIKE '%$search_safe%' OR content LIKE '%$search_safe%')" : "";

// Count total posts
$count_sql = "SELECT COUNT(*) as total FROM posts WHERE user_id = $user_id $search_condition";
$total_result = mysqli_query($conn, $count_sql);
$total_posts = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_posts / $limit);

// Fetch posts
$sql = "SELECT * FROM posts WHERE user_id = $user_id $search_condition ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard with Search & Pagination</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f4f6f8;
      font-family: "Segoe UI", Tahoma, sans-serif;
    }
    .container {
      max-width: 800px;
      margin: 60px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 16px rgba(0,0,0,0.05);
    }
    .post {
      padding: 15px;
      border-bottom: 1px solid #eee;
    }
    .pagination a, .pagination strong {
      margin: 0 5px;
      text-decoration: none;
    }
    .logout-btn {
      margin-top: 30px;
    }
  </style>
</head>
<body>
<div class="container">
  <h2 class="mb-4">Welcome, <?= htmlspecialchars($user_name) ?> 👋</h2>

  <!-- 🔍 Search Form -->
  <form method="GET" class="input-group mb-4">
    <input type="text" name="search" class="form-control" placeholder="Search posts..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-primary" type="submit">Search</button>
  </form>

  <!-- 📃 Posts Display -->
  <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="post">
        <h5><?= htmlspecialchars($row['title']) ?></h5>
        <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
        <small class="text-muted">Posted on <?= $row['created_at'] ?></small>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No posts found.</p>
  <?php endif; ?>
<!-- 🛠️ Action Buttons -->
<div class="mb-4 d-flex flex-wrap gap-2">
  <a href="insertpost.php" class="btn btn-success">➕ Insert Post</a>
  <a href="displaypost.php" class="btn btn-info text-white">📄 Display Posts</a>
  <a href="updatepost.php" class="btn btn-warning">✏️ Update Post</a>
  <a href="deletepost.php" class="btn btn-danger">🗑️ Delete Post</a>
</div>

 <!-- 📄 Pagination -->
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>

  <a href="logout.php" class="btn btn-danger logout-btn">Logout</a>
</div>
</body>
</html>

