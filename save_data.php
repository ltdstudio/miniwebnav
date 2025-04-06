<?php
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'upload_image') {
    if (isset($_FILES['image']) && isset($_POST['text'])) {
        $text = $_POST['text'];
        $file = $_FILES['image'];
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = $text . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo json_encode(['success' => true, 'imagePath' => $targetPath]);
        } else {
            echo json_encode(['success' => false, 'message' => '文件移动失败']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '无效的操作']);
    }
} elseif ($action === 'upload_bg_image') {
    if (isset($_FILES['bgImage'])) {
        $file = $_FILES['bgImage'];
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = 'bg_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo json_encode(['success' => true, 'imagePath' => $targetPath]);
        } else {
            echo json_encode(['success' => false, 'message' => '文件移动失败']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '无效的操作']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '未知的操作']);
}
?>
