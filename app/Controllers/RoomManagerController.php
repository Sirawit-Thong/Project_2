<?php
/**
 * Room Manager Controller
 * จัดการผู้รับผิดชอบห้อง
 */
class RoomManagerController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        $pageTitle = 'ผู้รับผิดชอบห้อง';
        $viewPath = 'crud/room_managers';
        $managers = RoomManager::getAll();
        $rooms = Room::getAll();
        $users = User::getHolders();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $roomId = $_POST['room_id'] ?? null;
            $userId = $_POST['user_id'] ?? null;

            if ($roomId && $userId) {
                // Check duplicate
                if (RoomManager::isOwner($userId, $roomId)) {
                    $this->flash('danger', 'ผู้ใช้คนนี้รับผิดชอบห้องนี้อยู่แล้ว');
                } else {
                    RoomManager::create([
                        'room_id' => $roomId,
                        'user_id' => $userId,
                    ]);
                    logActivity(getCurrentUserId(), 'Add Room Manager', 'เพิ่มผู้รับผิดชอบห้อง');
                    $this->flash('success', 'เพิ่มผู้รับผิดชอบสำเร็จ');
                }
                $this->redirect(SITE_URL . '/room-managers');
            }
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function delete($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        RoomManager::delete($id);
        logActivity(getCurrentUserId(), 'Delete Room Manager', 'ลบผู้รับผิดชอบห้อง ID: ' . $id);
        $this->flash('success', 'ลบสำเร็จ');
        $this->redirect(SITE_URL . '/room-managers');
    }

    public function sync($mode)
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        if ($mode === 'fill') {
            $updated = RoomManager::syncHoldersFill();
        } else {
            $updated = RoomManager::syncHoldersOverwrite();
        }

        logActivity(getCurrentUserId(), 'Sync Holders', 'ซิงค์ผู้ถือครอง: ' . $updated . ' รายการ');
        $this->flash('success', "ซิงค์ผู้ถือครองสำเร็จ {$updated} รายการ");
        $this->redirect(SITE_URL . '/room-managers');
    }
}
