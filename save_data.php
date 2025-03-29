<!DOCTYPE html><html><head><title>DZG的文件管理调试系统</title><style>
    body { background-color: #e6f0fa; font-family: Arial, sans-serif; margin: 20px; }
    h1, h3 { color: #4682b4; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; background-color: #f0f8ff; }
    th, td { padding: 10px; border: 1px solid #b0c4de; text-align: left; }
    th { background-color: #87ceeb; color: #fff; }
    tr:nth-child(even) { background-color: #f5faff; }
    a { color: #4682b4; text-decoration: none; }
    a:hover { text-decoration: underline; }
    input[type="text"], input[type="file"], textarea { padding: 8px; border: 1px solid #b0c4de; border-radius: 5px; margin: 5px 0; }
    input[type="submit"], button { padding: 10px 20px; background: linear-gradient(#87ceeb, #4682b4); color: white; border: none; border-radius: 5px; cursor: pointer; }
    input[type="submit"]:hover, button:hover { background: linear-gradient(#b0e0e6, #5f9ea0); }
    .batch-btn { padding: 10px 20px; background: linear-gradient(#b0e0e6, #87ceeb); color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px; }
    .batch-btn:hover { background: linear-gradient(#d6e6f5, #b0e0e6); }
    .delete-btn { padding: 8px 16px; background: #ff4444; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 0 5px; }
    .delete-btn:hover { background: #cc0000; }
    .cancel-btn { padding: 8px 16px; background: #cccccc; color: black; border: none; border-radius: 5px; cursor: pointer; margin: 0 5px; }
    .cancel-btn:hover { background: #999999; }
    .info-icon { cursor: pointer; color: #4682b4; font-size: 16px; margin-left: 5px; }
    progress { width: 100%; margin: 5px 0; }
    .github-editor { max-width: 900px; margin: 20px auto; }
    .tabs { border-bottom: 1px solid #b0c4de; margin-bottom: 10px; }
    .tab { display: inline-block; padding: 10px 20px; cursor: pointer; color: #4682b4; }
    .tab.active { border-bottom: 2px solid #4682b4; color: #4682b4; }
    .content { border: 1px solid #b0c4de; padding: 10px; background: #f0f8ff; }
    #editor { width: 100%; height: 400px; border: none; padding: 10px; font-family: monospace; }
    #preview { white-space: pre-wrap; }
    .commit-section { margin-top: 20px; padding: 10px; border: 1px solid #b0c4de; background: #fff; text-align: right; }
    .commit-section input[type="submit"] { margin-left: 15px; }
    .commit-section .save-backup { margin-left: 15px; }
    .back-btn, .logout-btn { padding: 10px 20px; background: linear-gradient(#556B2F, #2F4F4F); color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 15px; text-decoration: none; display: inline-block; }
    .back-btn:hover, .logout-btn:hover { background: linear-gradient(#6B8E23, #4A6868); }
    .CodeMirror { height: 400px; border: 1px solid #b0c4de; }
    #dirTreeModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    #dirTreeContent { background: #fff; margin: 15% auto; padding: 20px; width: 50%; max-height: 70%; overflow-y: auto; border-radius: 5px; }
</style></head><body><?php
header('Content-Type: application/json');

// 启用错误日志
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// 增加资源限制
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 120);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$dataFile = 'index.data';
$uploadDir = './';
$backupDir = './navbackup/';

// 确保数据文件存在，如果不存在则创建
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([
        'navItems' => [],
        'bodyBackground' => '',
        'siteTitle' => 'Moster怪兽桌游吧mini',
        'adminUser' => 'mifen',
        'adminPassword' => '222222'
    ]));
}

// 检查数据文件权限
if (!is_readable($dataFile) || !is_writable($dataFile)) {
    error_log('数据文件权限不足: ' . $dataFile);
    echo json_encode(['success' => false, 'message' => '数据文件权限不足']);
    exit;
}

// 检查当前目录权限（用于图片上传）
if (!is_writable($uploadDir)) {
    error_log('当前目录权限不足: ' . $uploadDir);
    echo json_encode(['success' => false, 'message' => '当前目录权限不足，无法保存图片']);
    exit;
}

// 检查备份目录是否存在，如果不存在则创建
if (!file_exists($backupDir)) {
    if (!mkdir($backupDir, 0777, true)) {
        error_log('无法创建备份目录: ' . $backupDir);
        echo json_encode(['success' => false, 'message' => '无法创建备份目录']);
        exit;
    }
}

// 检查备份目录权限
if (!is_writable($backupDir)) {
    error_log('备份目录权限不足: ' . $backupDir);
    echo json_encode(['success' => false, 'message' => '备份目录权限不足']);
    exit;
}

if ($action === 'read') {
    // 读取数据
    $data = file_get_contents($dataFile);
    if ($data === false) {
        error_log('无法读取文件: ' . $dataFile);
        echo json_encode(['success' => false, 'message' => '无法读取文件']);
        exit;
    }
    $jsonData = json_decode($data, true);
    if ($jsonData === null) {
        error_log('文件内容格式错误: ' . $dataFile);
        echo json_encode(['success' => false, 'message' => '文件内容格式错误']);
        exit;
    }
    echo json_encode($jsonData);
} elseif ($action === 'write') {
    // 写入数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data === null) {
        error_log('无效的数据');
        echo json_encode(['success' => false, 'message' => '无效的数据']);
        exit;
    }
    $result = file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    if ($result === false) {
        error_log('无法写入文件: ' . $dataFile);
        echo json_encode(['success' => false, 'message' => '无法写入文件']);
        exit;
    }
    echo json_encode(['success' => true, 'message' => '数据保存成功']);
} elseif ($action === 'upload') {
    // 处理图片上传
    if (!isset($_FILES['image'])) {
        error_log('未选择文件');
        echo json_encode(['success' => false, 'message' => '未选择文件']);
        exit;
    }

    $file = $_FILES['image'];
    $type = isset($_POST['type']) ? $_POST['type'] : 'nav';
    $text = isset($_POST['text']) ? $_POST['text'] : null;

    // 检查文件是否有效
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log('文件上传失败，错误代码：' . $file['error']);
        echo json_encode(['success' => false, 'message' => '文件上传失败，错误代码：' . $file['error']]);
        exit;
    }

    // 获取文件扩展名
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExt, $allowedExts)) {
        error_log('不支持的文件格式: ' . $fileExt);
        echo json_encode(['success' => false, 'message' => '不支持的文件格式，仅支持 jpg、jpeg、png、gif']);
        exit;
    }

    // 根据类型生成文件名
    if ($type === 'body') {
        $newFileName = 'bg_背景.' . $fileExt;
    } else {
        if (!$text) {
            error_log('缺少网站名称');
            echo json_encode(['success' => false, 'message' => '缺少网站名称']);
            exit;
        }
        $newFileName = 'web_' . $text . '.' . $fileExt;
    }

    $destination = $uploadDir . $newFileName;

    // 如果文件已存在，先删除旧文件
    if ($type === 'nav' && file_exists($destination)) {
        unlink($destination);
    }

    // 移动上传的文件到当前目录
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode([
            'success' => true,
            'message' => '图片上传成功',
            'imagePath' => $newFileName
        ]);
    } else {
        error_log('无法保存文件: ' . $destination);
        echo json_encode(['success' => false, 'message' => '无法保存文件']);
    }
} elseif ($action === 'rename_image') {
    // 重命名图片
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data || !isset($data['oldText']) || !isset($data['newText'])) {
        error_log('无效的请求数据');
        echo json_encode(['success' => false, 'message' => '无效的请求数据']);
        exit;
    }

    $oldText = $data['oldText'];
    $newText = $data['newText'];

    // 查找旧图片文件（使用新的命名格式 web_网站名.*）
    $oldFiles = glob($uploadDir . 'web_' . $oldText . '.*');
    if (empty($oldFiles)) {
        echo json_encode(['success' => true, 'message' => '未找到旧图片，无需重命名']);
        exit;
    }

    $oldFile = $oldFiles[0];
    $fileExt = strtolower(pathinfo($oldFile, PATHINFO_EXTENSION));
    $newFileName = 'web_' . $newText . '.' . $fileExt;
    $newFilePath = $uploadDir . $newFileName;

    // 重命名文件
    if (rename($oldFile, $newFilePath)) {
        echo json_encode([
            'success' => true,
            'message' => '图片重命名成功',
            'newImagePath' => $newFileName
        ]);
    } else {
        error_log('图片重命名失败: ' . $oldFile . ' -> ' . $newFilePath);
        echo json_encode(['success' => false, 'message' => '图片重命名失败']);
    }
} elseif ($action === 'delete_image') {
    // 删除图片
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data || !isset($data['text'])) {
        error_log('无效的请求数据');
        echo json_encode(['success' => false, 'message' => '无效的请求数据']);
        exit;
    }

    $text = $data['text'];
    $files = glob($uploadDir . 'web_' . $text . '.*');
    if (!empty($files)) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    echo json_encode(['success' => true, 'message' => '图片删除成功']);
} elseif ($action === 'backup') {
    // 处理备份（直接复制到子文件夹，不压缩）
    $backupFolderName = 'backup_' . date('Ymd_His');
    $backupFolderPath = $backupDir . $backupFolderName . '/';

    // 创建备份子文件夹
    if (!mkdir($backupFolderPath, 0777, true)) {
        error_log('无法创建备份子文件夹: ' . $backupFolderPath
