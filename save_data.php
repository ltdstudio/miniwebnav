<?php
header('Content-Type: application/json');

// 数据存储文件
$dataFile = 'index.data';
$uploadDir = 'uploads/';

// 确保上传目录存在
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 获取请求的 action 参数
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 处理不同的操作
switch ($action) {
    case 'read':
        // 读取数据
        if (file_exists($dataFile)) {
            $data = json_decode(file_get_contents($dataFile), true);
            if ($data === null) {
                echo json_encode(['success' => false, 'message' => '数据解析失败']);
            } else {
                echo json_encode($data);
            }
        } else {
            // 如果数据文件不存在，返回默认空数据
            $defaultData = [
                'navItems' => [],
                'bodyBackground' => '',
                'siteTitle' => 'Moster怪兽桌游吧mini',
                'adminUser' => 'admin',
                'adminPassword' => 'admin'
            ];
            echo json_encode($defaultData);
        }
        break;

    case 'write':
        // 保存数据
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if ($data === null) {
            echo json_encode(['success' => false, 'message' => '无效的数据格式']);
        } else {
            if (file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT)) === false) {
                echo json_encode(['success' => false, 'message' => '写入文件失败']);
            } else {
                echo json_encode(['success' => true, 'message' => '数据保存成功']);
            }
        }
        break;

    case 'upload_image':
        // 上传导航块图片
        if (isset($_FILES['image']) && isset($_POST['text'])) {
            $text = $_POST['text'];
            $file = $_FILES['image'];
            $fileName = $text . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                echo json_encode(['success' => true, 'imagePath' => $targetPath]);
            } else {
                echo json_encode(['success' => false, 'message' => '文件移动失败']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '缺少必要的参数']);
        }
        break;

    case 'upload_bg_image':
        // 上传网页背景图片
        if (isset($_FILES['bgImage'])) {
            $file = $_FILES['bgImage'];
            $fileName = 'bg_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                echo json_encode(['success' => true, 'imagePath' => $targetPath]);
            } else {
                echo json_encode(['success' => false, 'message' => '文件移动失败']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '缺少必要的参数']);
        }
        break;

    case 'delete_image':
        // 删除导航块图片
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (isset($data['text'])) {
            $text = $data['text'];
            // 假设图片路径存储在 index.data 中，这里仅模拟删除逻辑
            if (file_exists($dataFile)) {
                $currentData = json_decode(file_get_contents($dataFile), true);
                foreach ($currentData['navItems'] as &$item) {
                    if ($item['text'] === $text && !empty($item['backgroundImage'])) {
                        if (file_exists($item['backgroundImage'])) {
                            unlink($item['backgroundImage']);
                        }
                        $item['backgroundImage'] = '';
                        break;
                    }
                }
                file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => '数据文件不存在']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '缺少必要的参数']);
        }
        break;

    case 'rename_image':
        // 重命名导航块图片
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (isset($data['oldText']) && isset($data['newText'])) {
            $oldText = $data['oldText'];
            $newText = $data['newText'];
            if (file_exists($dataFile)) {
                $currentData = json_decode(file_get_contents($dataFile), true);
                foreach ($currentData['navItems'] as &$item) {
                    if ($item['text'] === $oldText && !empty($item['backgroundImage'])) {
                        $oldPath = $item['backgroundImage'];
                        $newFileName = $newText . '_' . time() . '.' . pathinfo($oldPath, PATHINFO_EXTENSION);
                        $newPath = $uploadDir . $newFileName;
                        if (file_exists($oldPath)) {
                            rename($oldPath, $newPath);
                            $item['backgroundImage'] = $newPath;
                        }
                        $item['text'] = $newText;
                        break;
                    }
                }
                file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));
                echo json_encode(['success' => true, 'newImagePath' => $newPath]);
            } else {
                echo json_encode(['success' => false, 'message' => '数据文件不存在']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '缺少必要的参数']);
        }
        break;

    case 'backup':
        // 备份数据
        $backupFile = 'backup_' . date('Ymd_His') . '.data';
        if (file_exists($dataFile)) {
            if (copy($dataFile, $backupFile)) {
                echo json_encode(['success' => true, 'message' => '备份成功']);
            } else {
                echo json_encode(['success' => false, 'message' => '备份失败']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => '数据文件不存在']);
        }
        break;

    case 'reboot':
        // 重启服务器（需要权限）
        exec('sudo /sbin/reboot', $output, $return_var);
        if ($return_var === 0) {
            echo json_encode(['success' => true, 'message' => '正在重启']);
        } else {
            echo json_encode(['success' => false, 'message' => '重启失败，可能需要配置权限']);
        }
        break;

    case 'shutdown':
        // 关闭服务器（需要权限）
        exec('sudo /sbin/shutdown -h now', $output, $return_var);
        if ($return_var === 0) {
            echo json_encode(['success' => true, 'message' => '正在关闭']);
        } else {
            echo json_encode(['success' => false, 'message' => '关机失败，可能需要配置权限']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '无效的操作']);
        break;
}
?>
