<?php
header('Content-Type: application/json');

// 数据文件路径
$dataFile = 'index.data';

// 确保 navbackup 目录存在
$backupDir = 'navbackup';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// 处理请求
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'read') {
    // 读取数据
    if (file_exists($dataFile)) {
        $data = file_get_contents($dataFile);
        echo $data;
    } else {
        // 如果文件不存在，返回默认数据（包含管理员用户名和密码）
        $defaultData = [
            'navItems' => [],
            'bodyBackground' => '',
            'siteTitle' => 'Moster怪兽桌游吧mini',
            'adminUser' => 'mifen',
            'adminPassword' => '222222'
        ];
        echo json_encode($defaultData);
    }
} elseif ($action === 'write') {
    // 写入数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data) {
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '无效的数据']);
    }
} elseif ($action === 'upload') {
    // 上传图片
    if (isset($_FILES['image']) && isset($_POST['text'])) {
        $text = $_POST['text'];
        $type = isset($_POST['type']) ? $_POST['type'] : 'nav';
        $uploadDir = ($type === 'body') ? 'body_backgrounds/' : 'nav_images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $file = $_FILES['image'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = ($type === 'body') ? 'body_background.' . $ext : $text . '.' . $ext;
        $filepath = $uploadDir . $filename;

        // 如果是导航图片，删除旧图片
        if ($type !== 'body') {
            $existingFiles = glob($uploadDir . $text . '.*');
            foreach ($existingFiles as $oldFile) {
                unlink($oldFile);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode(['success' => true, 'imagePath' => $filepath]);
        } else {
            echo json_encode(['success' => false, 'message' => '图片上传失败']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '未提供图片或文本']);
    }
} elseif ($action === 'delete_image') {
    // 删除图片
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (isset($data['text'])) {
        $text = $data['text'];
        $uploadDir = 'nav_images/';
        $existingFiles = glob($uploadDir . $text . '.*');
        foreach ($existingFiles as $file) {
            unlink($file);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '未提供文本']);
    }
} elseif ($action === 'rename_image') {
    // 重命名图片
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (isset($data['oldText']) && isset($data['newText'])) {
        $oldText = $data['oldText'];
        $newText = $data['newText'];
        $uploadDir = 'nav_images/';
        $existingFiles = glob($uploadDir . $oldText . '.*');
        if (count($existingFiles) > 0) {
            $oldFile = $existingFiles[0];
            $ext = pathinfo($oldFile, PATHINFO_EXTENSION);
            $newFile = $uploadDir . $newText . '.' . $ext;
            if (rename($oldFile, $newFile)) {
                echo json_encode(['success' => true, 'newImagePath' => $newFile]);
            } else {
                echo json_encode(['success' => false, 'message' => '重命名图片失败']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '未找到图片']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '未提供旧文本或新文本']);
    }
} elseif ($action === 'backup') {
    // 备份数据
    if (file_exists($dataFile)) {
        $timestamp = date('Ymd_His');
        $backupFile = $backupDir . '/index_backup_' . $timestamp . '.data';
        if (copy($dataFile, $backupFile)) {
            // 保留最近的 10 个备份文件
            $backups = glob($backupDir . '/index_backup_*.data');
            usort($backups, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            while (count($backups) > 10) {
                $fileToDelete = array_pop($backups);
                unlink($fileToDelete);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '备份失败']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '数据文件不存在']);
    }
} elseif ($action === 'reboot') {
    // 重启服务器
    $output = shell_exec('sudo reboot');
    if ($output === null) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '重启命令执行失败']);
    }
} elseif ($action === 'shutdown') {
    // 关闭服务器
    $output = shell_exec('sudo shutdown -h now');
    if ($output === null) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '关机命令执行失败']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '无效的操作']);
}
?>
