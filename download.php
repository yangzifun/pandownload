<?php
// 引入配置文件（如果使用单独的配置文件）
// include_once 'config.php';

// 直接定义下载目录路径，如果config.php不存在
define('DOWNLOAD_DIR', __DIR__ . '/files/'); // 设置下载目录为 'files' 文件夹

if (!isset($_GET['file'])) {
    die("非法请求：未指定文件。");
}

// 从GET参数获取相对路径，并进行清理
$relativePath = $_GET['file'];
// 移除任何可能导致路径遍历的字符
$relativePath = str_replace('..', '', $relativePath);
// 规范化路径分隔符
$relativePath = trim(str_replace('\\', '/', $relativePath), '/');

$fileName = basename($relativePath); // 获取纯文件名用于下载

// 构造文件完整路径
$filePath = DOWNLOAD_DIR . $relativePath;

// === 安全检查 ===
// 1. 检查文件是否存在
if (!file_exists($filePath)) {
    die("错误：文件不存在。");
}

// 2. 检查文件是否可读
if (!is_readable($filePath)) {
    die("错误：文件不可读。请检查服务器权限。");
}

// 3. 检查文件是否在允许的下载目录内（防止路径遍历攻击）
// realpath() 会解析所有 /../ 和 /./ 并返回规范化的绝对路径
$realFilePath = realpath($filePath);
$realDownloadDir = realpath(DOWNLOAD_DIR);

if ($realFilePath === false || strpos($realFilePath, $realDownloadDir) !== 0) {
    die("错误：尝试访问的路径不被允许。");
}

// 获取文件类型以设置正确的Content-Type头
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

if ($mimeType === false) {
    $mimeType = 'application/octet-stream'; // 默认通用二进制流
}

// === 设置HTTP头，强制浏览器下载文件 ===
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType); // 文件类型
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"'); // 强制下载并指定文件名
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath)); // 文件大小

// 清除并刷新输出缓冲区，确保没有其他内容在文件流之前输出
ob_clean();
flush();

// 输出文件内容
readfile($filePath);

exit; // 确保脚本执行在此结束
?>
