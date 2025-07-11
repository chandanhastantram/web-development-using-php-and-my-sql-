<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db.php';                 // ① DB connection ($conn)

/* ---------- CONFIG ---------- */
$limit = 5;                       // posts per page

/* ---------- INPUT ---------- */
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ---------- HELPER: bind by ref ---------- */
function bindParams(mysqli_stmt $stmt, string $types, array $params): void {
    /* mysqli requires params passed by reference */
    $refs = [];
    foreach ($params as $k => $v) $refs[$k] = &$params[$k];
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

/* ---------- BUILD SEARCH CLAUSE ---------- */
$searchClause = '';
$params       = [];
$types        = '';

if ($search !== '') {
    $searchClause = 'WHERE title LIKE ? OR content LIKE ?';
    $like = "%{$search}%";
    $params = [$like, $like];
    $types  = 'ss';
}

/* ---------- COUNT POSTS ---------- */
$count_sql = "SELECT COUNT(*) AS total FROM posts $searchClause";
if (!$count_stmt = $conn->prepare($count_sql)) die("Count prep error: {$conn->error}");

if ($search !== '') bindParams($count_stmt, $types, $params);
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, (int)ceil($total_posts / $limit));

/* ---------- GET PAGINATED POSTS ---------- */
$list_sql = "
  SELECT id, title, content, created_at
  FROM posts
  $searchClause
  ORDER BY created_at DESC
  LIMIT ? OFFSET ?
";
$list_stmt = $conn->prepare($list_sql) or die("List prep error: {$conn->error}");

/* add limit & offset params */
$params_list = $params;
$types_list  = $types . 'ii';
$params_list[] = $limit;
$params_list[] = $offset;

bindParams($list_stmt, $types_list, $params_list);
$list_stmt->execute();
$result = $list_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pulse - Stories that matter</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root {
  --primary-bg: #0f0f23;
  --secondary-bg: #1a1a2e;
  --card-bg: #16213e;
  --accent-purple: #8b5cf6;
  --accent-blue: #3b82f6;
  --accent-cyan: #06b6d4;
  --text-primary: #f8fafc;
  --text-secondary: #cbd5e1;
  --text-muted: #94a3b8;
  --border-color: #374151;
  --shadow-light: rgba(139, 92, 246, 0.1);
  --shadow-dark: rgba(0, 0, 0, 0.3);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--primary-bg);
  color: var(--text-primary);
  line-height: 1.6;
  overflow-x: hidden;
}

/* Animated background */
body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(circle at 20% 30%, var(--accent-purple) 0%, transparent 50%),
    radial-gradient(circle at 80% 70%, var(--accent-blue) 0%, transparent 50%),
    radial-gradient(circle at 40% 80%, var(--accent-cyan) 0%, transparent 50%);
  opacity: 0.1;
  z-index: -1;
  animation: float 20s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
}

/* Header Styles */
.header {
  text-align: center;
  margin-bottom: 3rem;
  position: relative;
}

.logo {
  font-size: 3.5rem;
  font-weight: 900;
  background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue), var(--accent-cyan));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.5rem;
  text-shadow: 0 4px 8px var(--shadow-dark);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

.tagline {
  color: var(--text-secondary);
  font-size: 1.1rem;
  font-weight: 300;
  margin-bottom: 1.5rem;
}

.nav {
  display: flex;
  justify-content: center;
  gap: 2rem;
}

.nav a {
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.nav a::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  transition: left 0.6s;
}

.nav a:hover {
  color: var(--text-primary);
  background: rgba(139, 92, 246, 0.1);
  transform: translateY(-2px);
}

.nav a:hover::before {
  left: 100%;
}

/* Search Styles */
.search-container {
  position: relative;
  margin-bottom: 2rem;
}

.search-form {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input {
  width: 100%;
  padding: 1rem 3rem 1rem 1.5rem;
  border: 2px solid var(--border-color);
  border-radius: 50px;
  background: var(--card-bg);
  color: var(--text-primary);
  font-size: 1rem;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.search-input:focus {
  outline: none;
  border-color: var(--accent-purple);
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.search-input::placeholder {
  color: var(--text-muted);
}

.search-btn {
  position: absolute;
  right: 0.5rem;
  background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
  border: none;
  border-radius: 50%;
  width: 2.5rem;
  height: 2.5rem;
  color: white;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

/* Post Styles */
.posts-grid {
  display: grid;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.post-card {
  background: var(--card-bg);
  border-radius: 20px;
  padding: 1.5rem;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.post-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, var(--accent-purple), var(--accent-blue), var(--accent-cyan));
}

.post-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px var(--shadow-light);
  border-color: var(--accent-purple);
}

.post-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
  line-height: 1.4;
}

.post-meta {
  font-size: 0.875rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.post-meta::before {
  content: '📅';
  font-size: 0.75rem;
}

.post-excerpt {
  color: var(--text-secondary);
  line-height: 1.6;
  margin-bottom: 1rem;
}

.read-more {
  color: var(--accent-purple);
  text-decoration: none;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.read-more:hover {
  color: var(--accent-blue);
  transform: translateX(5px);
}

.read-more::after {
  content: '→';
  transition: transform 0.3s ease;
}

.read-more:hover::after {
  transform: translateX(3px);
}

/* Pagination Styles */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
}

.pagination a,
.pagination span {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--card-bg);
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 600;
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.pagination a:hover {
  background: var(--accent-purple);
  color: white;
  transform: scale(1.1);
}

.pagination .current {
  background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue));
  color: white;
  border-color: var(--accent-purple);
}

/* No Posts Message */
.no-posts {
  text-align: center;
  color: var(--text-muted);
  font-size: 1.1rem;
  margin: 3rem 0;
  padding: 2rem;
  background: var(--card-bg);
  border-radius: 15px;
  border: 1px solid var(--border-color);
}

/* Responsive Design */
@media (max-width: 768px) {
  .container {
    padding: 1rem;
  }
  
  .logo {
    font-size: 2.5rem;
  }
  
  .nav {
    gap: 1rem;
  }
  
  .post-card {
    padding: 1rem;
  }
  
  .search-input {
    padding: 0.875rem 2.5rem 0.875rem 1rem;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
</head>
<body>

<div class="container">
  <header class="header">
    <h1 class="logo">Pulse</h1>
    <p class="tagline">Stories that matter, voices that inspire</p>
    <nav class="nav">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Sign Out</a>
      <?php else: ?>
        <a href="login.php">Join Us</a>
      <?php endif; ?>
    </nav>
  </header>

  <div class="search-container">
    <form class="search-form" method="GET">
      <input 
        type="text" 
        name="search" 
        class="search-input"
        placeholder="Search for stories, ideas, inspiration..." 
        value="<?= htmlspecialchars($search) ?>"
      >
      <button type="submit" class="search-btn">🔍</button>
    </form>
  </div>

  <main class="posts-grid">
    <?php if ($result->num_rows): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <article class="post-card">
          <h2 class="post-title"><?= htmlspecialchars($row['title']) ?></h2>
          <div class="post-meta">
            <?= date("F j, Y", strtotime($row['created_at'])) ?>
          </div>
          <p class="post-excerpt"><?= htmlspecialchars(substr($row['content'], 0, 150)) ?>...</p>
          <a class="read-more" href="displaypost.php?id=<?= $row['id'] ?>">Continue reading</a>
        </article>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-posts">
        <p>No stories found matching your search.</p>
        <p>Try different keywords or explore our latest posts.</p>
      </div>
    <?php endif; ?>
  </main>

  <?php if ($total_pages > 1): ?>
    <nav class="pagination" aria-label="Pagination">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current" aria-current="page"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" aria-label="Go to page <?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>

</div>

</body>
</html>


