<?php
// 定义文件系统的根目录
define('BASE_DIR', __DIR__ . '/files/');

// --- 变量初始化 ---
$relative_path = '';
$search_query = '';
$is_search = false;

// --- 处理搜索 ---
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = trim($_GET['q']);
    $is_search = true;
} elseif (isset($_GET['path'])) {
    $relative_path = trim(str_replace('..', '', $_GET['path']), '/');
}

// --- 路径和安全检查 ---
$current_dir = realpath(BASE_DIR . $relative_path);
if ($current_dir === false || strpos($current_dir, realpath(BASE_DIR)) !== 0) {
    $relative_path = '';
    $current_dir = BASE_DIR;
}

// --- 数据获取 ---
$directories = [];
$files_list = [];
$readme_content = '';

if ($is_search) {
    // --- 执行递归搜索 ---
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(BASE_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        if (stripos($item->getFilename(), $search_query) !== false) {
            $itemRelativePath = str_replace(BASE_DIR, '', $item->getRealPath());
            $itemRelativePath = str_replace('\\', '/', $itemRelativePath); // 兼容Windows
            if ($item->isDir()) {
                $directories[] = ['name' => $item->getFilename(), 'path' => $itemRelativePath];
            } else {
                $files_list[] = [
                    'name' => $item->getFilename(),
                    'path' => $itemRelativePath,
                    'size' => round($item->getSize() / (1024 * 1024), 2) . ' MB'
                ];
            }
        }
    }
} else {
    // --- 浏览模式 ---
    $items = is_dir($current_dir) ? array_diff(scandir($current_dir), ['.', '..']) : [];
    $readme_path = $current_dir . '/readme.md';
    if (is_file($readme_path) && is_readable($readme_path)) {
        $readme_content = nl2br(htmlspecialchars(file_get_contents($readme_path)));
    }

    foreach ($items as $item) {
        if (strtolower($item) === 'readme.md') continue;
        $itemPath = $current_dir . '/' . $item;
        $itemRelativePath = ltrim($relative_path . '/' . $item, '/');
        if (is_dir($itemPath)) {
            $directories[] = ['name' => $item, 'path' => $itemRelativePath];
        } else {
            $files_list[] = [
                'name' => $item,
                'path' => $itemRelativePath,
                'size' => round(filesize($itemPath) / (1024 * 1024), 2) . ' MB'
            ];
        }
    }
}

// --- 面包屑导航 ---
$breadcrumbs = [['name' => '主目录', 'path' => '']];
if (!$is_search && !empty($relative_path)) {
    $path_parts = explode('/', $relative_path);
    $current_crumb_path = '';
    foreach ($path_parts as $part) {
        $current_crumb_path .= $part;
        $breadcrumbs[] = ['name' => $part, 'path' => $current_crumb_path];
        $current_crumb_path .= '/';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YZFN 网盘</title>
    <link rel="icon" href="https://s3.yangzihome.space/logo.ico" type="image/x-icon">
    <style>
        html { font-size: 87.5%; }
        body, html { margin: 0; padding: 0; min-height: 100%; background-color: #fff; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .container { width: 100%; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 90px 20px 40px; box-sizing: border-box; }
        .content-group { width: 100%; max-width: 800px; text-align: left; z-index: 10; }
        #header-overlay {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          padding: 10px 36px;
          box-sizing: border-box;
          display: flex;
          justify-content: space-between;
          align-items: center;
          z-index: 20;
        }
        #header-overlay a {
          text-decoration: none;
        }
        .header-name {
          font-size: 1.8rem;
          color: #3d474d;
          font-weight: bold;
          margin: 0;
        }
        .header-avatar-container {
          width: 50px;
          height: 50px;
          border: 2px solid #89949B;
          border-radius: 50%;
          overflow: hidden;
          display: flex;
          justify-content: center;
          align-items: center;
          cursor: default; 
        }
        .header-avatar {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }
        .profile-name { 
          font-size: 2rem;
          color: #3d474d;
          margin-bottom: 20px;
          text-align: center;
        }
        .card { background: #f8f9fa; border: 1px solid #E8EBED; border-radius: 8px; padding: 24px; margin-bottom: 24px; }
        .footer { margin-top: 40px; text-align: center; color: #89949B; font-size: 0.8rem; }
        .footer a { color: #89949B; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }

        .navigation-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 15px; }
        .breadcrumbs { display: flex; align-items: center; flex-wrap: wrap; gap: 5px; font-size: 1rem; }
        .breadcrumbs a { color: #5a666d; text-decoration: none; padding: 5px 8px; border-radius: 4px; }
        .breadcrumbs a:hover { background-color: #e9ecef; text-decoration: underline; }
        .breadcrumbs span { color: #89949B; }
        .breadcrumbs .current { color: #3d474d; font-weight: 500; }

        .search-form { display: flex; gap: 8px; }
        .search-form input { border: 1px solid #d1d5d8; border-radius: 4px; padding: 8px 12px; font-size: 0.9rem; }
        .search-form button {
            padding: 8px 16px; text-align: center; background: #5a666d; border: 0;
            border-radius: 4px; color: white; text-decoration: none; font-weight: 500;
            font-size: 0.9rem; transition: all 0.3s; white-space: nowrap; cursor: pointer;
        }
        .search-form button:hover { background-color: #3d474d; }

        .item-list { display: flex; flex-direction: column; gap: 5px; }
        .list-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; border-radius: 6px; transition: background-color 0.2s; }
        .list-item:hover { background-color: #f8f9fa; }
        .item-name { display: flex; align-items: center; gap: 12px; font-weight: 500; color: #3d474d; text-decoration: none; flex-grow: 1; }
        .item-name svg { width: 20px; height: 20px; color: #5a666d; }
        .item-path { font-size: 0.8em; color: #89949B; margin-left: 12px; }
        .item-details { display: flex; align-items: center; gap: 15px; }
        .file-size { font-size: 0.9em; color: #666; white-space: nowrap; }
        .nav-btn {
          padding: 6px 14px; text-align: center; background: #E8EBED; border: 2px solid #89949B;
          border-radius: 4px; color: #5a666d; text-decoration: none; font-weight: 500;
          font-size: 0.9rem; transition: all 0.3s; cursor: pointer;
        }
        .nav-btn:hover { background: #89949B; color: white; }
        .placeholder { padding: 40px; text-align: center; color: #89949B; }
        .search-results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .search-results-header h2 { margin: 0; font-size: 1.2rem; }
        .search-results-header a { 
          font-size: 0.9rem;
          padding: 6px 14px;
          text-align: center;
          background: #E8EBED;
          border: 2px solid #89949B;
          border-radius: 4px;
          color: #5a666d;
          text-decoration: none;
          font-weight: 500;
          transition: all 0.3s;
        }
        .search-results-header a:hover {
          background: #89949B;
          color: white;
        }

        .readme-card h2 { font-size: 1.2rem; color: #3d474d; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e8ebed; }
        .readme-content { font-size: 0.95rem; line-height: 1.7; color: #5a666d; white-space: pre-wrap; word-break: break-word; }
        @media (max-width: 768px) {
            html {
                font-size: 100%;
            }
            #header-overlay {
                padding: 8px 15px;
            }
            .header-name {
                font-size: 1.5rem;
            }
            .header-avatar-container {
                width: 40px;
                height: 40px;
            }
            .container {
                padding: 70px 15px 20px;
                justify-content: flex-start;
            }
            .card {
                padding: 20px 15px;
            }
            .navigation-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-form {
                width: 100%;
            }
            .search-form input {
                flex-grow: 1;
            }
            .list-item {
                flex-wrap: wrap;
                gap: 10px;
            }
            .item-details {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div id="header-overlay">
      <a href="/"><h1 class="header-name">YZFN 网盘</h1></a>
      <div class="header-avatar-container">
        <img src="https://s3.yangzihome.space/logo.png" class="header-avatar" alt="Avatar">
      </div>
    </div>
    <div class="container">
        <div class="content-group">
            <div class="card">
                <div class="navigation-bar">
                    <?php if (!$is_search): ?>
                        <div class="breadcrumbs">
                            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                                <?php if ($i > 0) echo '<span>/</span>'; ?>
                                <a href="?path=<?php echo urlencode($crumb['path']); ?>" class="<?php echo ($i === count($breadcrumbs) - 1) ? 'current' : ''; ?>">
                                    <?php echo htmlspecialchars($crumb['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form action="" method="get" class="search-form">
                        <input type="text" name="q" placeholder="搜索文件或文件夹..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit">搜索</button>
                    </form>
                </div>

                <?php if ($is_search): ?>
                    <div class="search-results-header">
                        <h2>搜索"<?php echo htmlspecialchars($search_query); ?>"的结果</h2>
                        <a href="?">清除搜索</a>
                    </div>
                <?php endif; ?>

                <div class="item-list">
                    <?php if (empty($directories) && empty($files_list)): ?>
                        <p class="placeholder"><?php echo $is_search ? '未找到匹配项。' : '此文件夹为空。'; ?></p>
                    <?php else: ?>
                        <?php foreach ($directories as $dir): ?>
                            <div class="list-item">
                                <a href="?path=<?php echo urlencode($dir['path']); ?>" class="item-name">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" /></svg>
                                    <?php echo htmlspecialchars($dir['name']); ?>
                                    <?php if ($is_search): ?>
                                        <span class="item-path"><?php echo htmlspecialchars(dirname($dir['path'])); ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($files_list as $file): ?>
                            <div class="list-item">
                                <span class="item-name">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>
                                    <?php echo htmlspecialchars($file['name']); ?>
                                    <?php if ($is_search): ?>
                                        <span class="item-path"><?php echo htmlspecialchars(dirname($file['path'])); ?></span>
                                    <?php endif; ?>
                                </span>
                                <div class="item-details">
                                    <span class="file-size"><?php echo $file['size']; ?></span>
                                    <a href="download.php?file=<?php echo urlencode($file['path']); ?>" class="nav-btn">下载</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$is_search && !empty($readme_content)): ?>
            <div class="card readme-card">
                <h2>说明</h2>
                <div class="readme-content">
                    <?php echo $readme_content; ?>
                </div>
            </div>
            <?php endif; ?>

            <footer class="footer">
                <p>Powered by YZFN | <a href="https://www.yangzihome.space/security-statement" target="_blank" rel="noopener noreferrer">安全声明</a></p>
            </footer>
        </div>
    </div>
</body>
</html>
