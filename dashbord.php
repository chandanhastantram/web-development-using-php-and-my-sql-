<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$user_name = $_SESSION['user_name'];

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

$search_condition = '';
$params = [$user_id];
$types = 'i';

if ($search) {
    $search_condition = "AND (title LIKE ? OR content LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$count_sql = "SELECT COUNT(*) as total FROM posts WHERE user_id = ? $search_condition";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, $types, ...$params);
mysqli_stmt_execute($count_stmt);
$total_result = mysqli_stmt_get_result($count_stmt);
$total_posts = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_posts / $limit);

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$sql = "SELECT id, title, content, created_at FROM posts WHERE user_id = ? $search_condition ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NeonPulse Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      position: relative;
    }
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                  radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%);
      pointer-events: none;
      z-index: -1;
    }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .glass { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); }
    
    /* Creative Header with Floating Actions */
    .header-float {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      display: flex;
      gap: 10px;
    }
    .float-btn {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      position: relative;
      overflow: hidden;
    }
    .float-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: left 0.5s;
    }
    .float-btn:hover::before { left: 100%; }
    .float-btn:hover { transform: scale(1.1) rotate(10deg); }
    .btn-create { background: linear-gradient(45deg, #ff6b6b, #ff8e8e); }
    .btn-view { background: linear-gradient(45deg, #4ecdc4, #44a08d); }
    .btn-logout { background: linear-gradient(45deg, #ff9a9e, #fecfef); }
    
    /* Hero Section */
    .hero {
      text-align: center;
      padding: 80px 0 40px;
      position: relative;
    }
    .hero h1 {
      font-size: 3.5rem;
      font-weight: 800;
      background: linear-gradient(45deg, #fff, #f0f0f0);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 20px;
      animation: glow 2s ease-in-out infinite alternate;
    }
    @keyframes glow {
      from { text-shadow: 0 0 20px rgba(255, 255, 255, 0.5); }
      to { text-shadow: 0 0 30px rgba(255, 255, 255, 0.8); }
    }
    .stats {
      display: inline-block;
      background: linear-gradient(45deg, #667eea, #764ba2);
      padding: 15px 30px;
      border-radius: 50px;
      color: white;
      font-weight: 600;
      margin-top: 20px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    /* Search Bar */
    .search-bar {
      margin: 40px 0;
      position: relative;
    }
    .search-wrapper {
      position: relative;
      max-width: 600px;
      margin: 0 auto;
    }
    .search-input {
      width: 100%;
      padding: 20px 60px 20px 25px;
      border: none;
      border-radius: 50px;
      font-size: 1.1rem;
      background: rgba(255, 255, 255, 0.9);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    .search-input:focus {
      outline: none;
      transform: translateY(-2px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }
    .search-btn {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      width: 45px;
      height: 45px;
      border-radius: 50%;
      border: none;
      background: linear-gradient(45deg, #667eea, #764ba2);
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .search-btn:hover { transform: translateY(-50%) scale(1.1); }
    
    /* Posts Grid */
    .posts-grid { display: grid; gap: 25px; margin: 40px 0; }
    .post-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 20px;
      padding: 30px;
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }
    .post-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #667eea);
      transform: translateX(-100%);
      transition: transform 0.3s ease;
    }
    .post-card:hover::before { transform: translateX(0); }
    .post-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); }
    .post-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: white;
      margin-bottom: 15px;
      padding-right: 50px;
    }
    .post-content {
      color: rgba(255, 255, 255, 0.8);
      line-height: 1.6;
      margin-bottom: 20px;
    }
    .post-meta {
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    /* Dropdown */
    .post-actions {
      position: absolute;
      top: 20px;
      right: 20px;
    }
    .dots-menu {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .dots-menu:hover { background: rgba(255, 255, 255, 0.3); transform: scale(1.1); }
    .dropdown-menu {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: none;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    .dropdown-item {
      padding: 12px 20px;
      color: #333;
      transition: all 0.3s ease;
    }
    .dropdown-item:hover {
      background: linear-gradient(45deg, #667eea, #764ba2);
      color: white;
    }
    
    /* Modal */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(10px);
      z-index: 2000;
      display: none;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    .modal-content {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 25px;
      padding: 40px;
      max-width: 700px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
    }
    .modal-close {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: none;
      background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .modal-close:hover { transform: scale(1.1); }
    
    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin: 40px 0;
    }
    .page-link {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    .page-link:hover, .page-item.active .page-link {
      background: linear-gradient(45deg, #667eea, #764ba2);
      transform: scale(1.1);
      color: white;
    }
    
    /* No Posts */
    .no-posts {
      text-align: center;
      padding: 80px 20px;
      color: rgba(255, 255, 255, 0.8);
    }
    .no-posts i {
      font-size: 4rem;
      margin-bottom: 30px;
      opacity: 0.6;
    }
    .no-posts h4 {
      font-size: 2rem;
      margin-bottom: 20px;
      color: white;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .hero h1 { font-size: 2.5rem; }
      .header-float { top: 10px; right: 10px; }
      .float-btn { width: 50px; height: 50px; font-size: 1.2rem; }
      .container { padding: 10px; }
    }
  </style>
</head>
<body>
  <!-- Floating Action Buttons -->
  <div class="header-float">
    <button class="float-btn btn-create" onclick="window.location.href='insertpost.php'" title="Create Post">
      <i class="fas fa-plus"></i>
    </button>
    <button class="float-btn btn-view" onclick="window.location.href='displaypost.php'" title="View Posts">
      <i class="fas fa-eye"></i>
    </button>
    <form method="POST" action="logout.php" style="display: inline;">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <button type="submit" class="float-btn btn-logout" title="Logout" onclick="return confirm('Logout?')">
        <i class="fas fa-sign-out-alt"></i>
      </button>
    </form>
  </div>

  <div class="container">
    <!-- Hero Section -->
    <div class="hero">
      <h1>Welcome, <?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></h1>
      <div class="stats">
        <i class="fas fa-chart-line"></i> <?= $total_posts ?> posts
      </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
      <form method="GET">
        <div class="search-wrapper">
          <input type="text" name="search" class="search-input" placeholder="✨ Search your posts..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="search-btn">
            <i class="fas fa-search"></i>
          </button>
        </div>
        <?php if ($search): ?>
          <div style="text-align: center; margin-top: 20px;">
            <a href="?" style="color: rgba(255, 255, 255, 0.8); text-decoration: none;">
              <i class="fas fa-times"></i> Clear search
            </a>
            <span style="color: rgba(255, 255, 255, 0.6); margin-left: 20px;">
              Results for: "<strong><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></strong>"
            </span>
          </div>
        <?php endif; ?>
      </form>
    </div>

    <!-- Posts Display -->
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
      <div class="posts-grid">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <div class="post-card" onclick="openPost(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8') ?>', '<?= $row['created_at'] ?>')">
            <div class="post-actions">
              <div class="dropdown">
                <button class="dots-menu" type="button" data-bs-toggle="dropdown" onclick="event.stopPropagation()">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="updatepost.php?id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a></li>
                  <li><a class="dropdown-item" href="deletepost.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this post?')"><i class="fas fa-trash"></i> Delete</a></li>
                </ul>
              </div>
            </div>
            
            <h3 class="post-title"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="post-content"><?= nl2br(htmlspecialchars(substr($row['content'], 0, 200), ENT_QUOTES, 'UTF-8')) ?><?= strlen($row['content']) > 200 ? '...' : '' ?></div>
            <div class="post-meta">
              <i class="fas fa-calendar-alt"></i>
              <?= date('F j, Y \a\t g:i A', strtotime($row['created_at'])) ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="no-posts">
        <i class="fas fa-feather-alt"></i>
        <h4>No posts found</h4>
        <p><?= $search ? "Try different search terms or " : "" ?><a href="insertpost.php" style="color: #4facfe;">create your first post</a></p>
      </div>
    <?php endif; ?>

    <!-- Post Detail Modal -->
    <div id="postModal" class="modal-overlay">
      <div class="modal-content">
        <button class="modal-close" onclick="closePost()"><i class="fas fa-times"></i></button>
        <h2 id="postTitle" style="color: white; margin-bottom: 20px;"></h2>
        <div id="postContent" style="color: rgba(255, 255, 255, 0.9); line-height: 1.8; margin-bottom: 30px;"></div>
        <div id="postMeta" style="color: rgba(255, 255, 255, 0.7); padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.2);"></div>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav>
        <ul class="pagination">
          <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-left"></i></a>
            </li>
          <?php endif; ?>
          
          <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          
          <?php if ($page < $total_pages): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-right"></i></a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function openPost(id, title, content, date) {
      document.getElementById('postTitle').textContent = title;
      document.getElementById('postContent').innerHTML = content.replace(/\n/g, '<br>');
      document.getElementById('postMeta').innerHTML = `<i class="fas fa-calendar-alt"></i> ${new Date(date).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
      })}`;
      document.getElementById('postModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
    
    function closePost() {
      document.getElementById('postModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }
    
    document.getElementById('postModal').addEventListener('click', function(e) {
      if (e.target === this) closePost();
    });
    
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closePost();
    });
  </script>
</body>
</html> 

