<?php
/**
 * AJAX Image Upload Endpoint
 * อัปโหลดรูปภาพครุภัณฑ์แบบ AJAX
 */

require_once '../includes/header.php';
requireRole(['admin', 'staff']);

header('Content-Type: application/json');

$pdo = getDB();
$response = ['success' => false, 'error' => '', 'images' => []];

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
    $image_type = $_POST['image_type'] ?? 'current_condition';
    $temp_session = $_POST['temp_session'] ?? session_id(); // For tracking temp images
    
    // Validate image type
    if (!in_array($image_type, ['purchase', 'current_condition'])) {
        $image_type = 'current_condition';
    }
    
    $file = $_FILES['image'];
    
    // Upload image
    $result = uploadImage($file, 'equipment');
    
    if ($result['success']) {
        if ($equipment_id > 0) {
            // If equipment exists, save to database
            $stmt = $pdo->prepare("INSERT INTO equipment_img (equipment_id, path, type) VALUES (?, ?, ?)");
            $stmt->execute([$equipment_id, $result['path'], $image_type]);
            $imageId = $pdo->lastInsertId();
        } else {
            // Store in session for new equipment (will be saved when form is submitted)
            if (!isset($_SESSION['temp_images'][$temp_session])) {
                $_SESSION['temp_images'][$temp_session] = [];
            }
            $imageId = uniqid('temp_');
            $_SESSION['temp_images'][$temp_session][] = [
                'id' => $imageId,
                'path' => $result['path'],
                'type' => $image_type
            ];
        }
        
        $response = [
            'success' => true,
            'image' => [
                'id' => $imageId,
                'path' => $result['path'],
                'url' => SITE_URL . '/uploads/' . $result['path'],
                'type' => $image_type,
                'type_label' => $image_type === 'purchase' ? 'จัดซื้อ' : 'ปัจจุบัน'
            ]
        ];
    } else {
        $response['error'] = $result['error'];
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imageId = $_POST['delete_image'];
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
    $temp_session = $_POST['temp_session'] ?? session_id();
    
    if ($equipment_id > 0 && is_numeric($imageId)) {
        // Delete from database
        $stmt = $pdo->prepare("SELECT * FROM equipment_img WHERE id = ? AND equipment_id = ?");
        $stmt->execute([$imageId, $equipment_id]);
        $img = $stmt->fetch();
        
        if ($img) {
            // Delete file
            $filepath = UPLOAD_PATH . $img['path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM equipment_img WHERE id = ?");
            $stmt->execute([$imageId]);
            
            $response['success'] = true;
        }
    } else {
        // Delete from session (temp images)
        if (isset($_SESSION['temp_images'][$temp_session])) {
            foreach ($_SESSION['temp_images'][$temp_session] as $key => $img) {
                if ($img['id'] === $imageId) {
                    // Delete file
                    $filepath = UPLOAD_PATH . $img['path'];
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                    unset($_SESSION['temp_images'][$temp_session][$key]);
                    $response['success'] = true;
                    break;
                }
            }
            $_SESSION['temp_images'][$temp_session] = array_values($_SESSION['temp_images'][$temp_session]);
        }
    }
}

// Handle cancel (delete all temp images)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_upload'])) {
    $temp_session = $_POST['temp_session'] ?? session_id();
    
    if (isset($_SESSION['temp_images'][$temp_session])) {
        foreach ($_SESSION['temp_images'][$temp_session] as $img) {
            $filepath = UPLOAD_PATH . $img['path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        unset($_SESSION['temp_images'][$temp_session]);
        $response['success'] = true;
    }
}

// Get temp images
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_temp_images'])) {
    $temp_session = $_GET['temp_session'] ?? session_id();
    $images = $_SESSION['temp_images'][$temp_session] ?? [];
    
    foreach ($images as &$img) {
        $img['url'] = SITE_URL . '/uploads/' . $img['path'];
        $img['type_label'] = $img['type'] === 'purchase' ? 'จัดซื้อ' : 'ปัจจุบัน';
    }
    
    $response = ['success' => true, 'images' => $images];
}

echo json_encode($response);
exit;
