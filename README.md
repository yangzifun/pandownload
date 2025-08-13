## 简单网盘下载站

/path/to/your/project/

├── public_html/   <-- 你的Web服务器根目录

├── index.php         <-- 主页，显示文件列表

├── download.php      <-- 下载处理器

├── .htaccess         <-- (可选，用于IIS等服务器可能有web.config)

└── files/         <-- 存放文件的目录，推荐放在Web根目录之外！

​    ├── file1.zip

​    ├── document.pdf

​    └── image.jpg```



   

**重要提示**: 将`files/`目录放在`public_html/`（或`www`、`htdocs`之类的Web根目录）之外是**最推荐且最安全**的做法。这样，用户永远无法通过直接URL访问到你的文件，必须通过`download.php`脚本。如果无法做到，则需要通过Web服务器配置（如Apache的`.htaccess`或Nginx配置）来阻止对`private_files`目录的直接访问。

```php
// config.php (可选，如果文件少可以直接写在脚本中)
<?php
define('DOWNLOAD_DIR', '/path/to/your/project/files/'); // !!! 请替换为你的实际文件路径，确保Web服务器用户有读取权限
?>
```

### 部署步骤

1. **创建目录**: 在服务器上创建`project/public_html`和`project/public_html/files`目录。

2. **放置文件**: 将`index.php`和`download.php`放入`public_html`目录。将你要提供下载的文件放入`files`目录。

3. **修改路径**: **务必**将`index.php`和`download.php`中`define('DOWNLOAD_DIR', '...');`的路径修改为你服务器上`files`目录的**绝对路径**。

4. **设置权限**: 确保Web服务器用户（例如`www-data`或`apache`）对`private_files`目录及其中的文件有**读取权限**。

5. **配置Web服务器**: 如果`private_files`在Web根目录内，请按照上面的说明配置Apache或Nginx以阻止直接访问。

6. 测试

   :

   - 访问`http://yourdomain.com`，应该能看到文件列表。
   - 点击文件链接，验证是否能成功下载。
   - 尝试直接访问`http://yourdomain.com?path=files/file1.zip`（如果它在Web根目录内），检查是否被拒绝访问。

### 已实现功能

- [x] 文件夹文件读取
- [x] 文件搜索
- [x] 解析readme.md（仅实现解析内容，未实现展示效果）
- [ ] **文件分类/标签**: 如果文件很多，可以考虑数据库来存储文件信息，实现分类、搜索等功能。
- [ ] **下载计数**: 记录每个文件的下载次数。
- [ ]  **权限管理**: 对某些文件设置下载权限，只有登录用户才能下载。
- [ ] **错误页面**: 更友好的错误提示页面，而不是简单的`die()`。
- [ ] **大文件下载优化**: 对于超大文件，可以考虑使用X-Sendfile（Apache/Nginx模块）或分块传输（Chunked Transfer Encoding）来优化性能。

