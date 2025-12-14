<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/validation.php';

require_login();

$page_title = 'Manage Rooms';
$flash = get_flash();
$errors = [];
$success_message = '';
$pdo = get_pdo();

$room_form = [
    'room_number' => '',
    'room_type' => '',
    'capacity' => '',
    'price_per_night' => '',
    'status' => 'available',
];
$is_editing = false;

$requested_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $room_form['room_number'] = trim($_POST['room_number'] ?? '');
    $room_form['room_type'] = trim($_POST['room_type'] ?? '');
    $room_form['capacity'] = trim($_POST['capacity'] ?? '');
    $room_form['price_per_night'] = trim($_POST['price_per_night'] ?? '');
    $room_form['status'] = $_POST['status'] ?? 'available';

    if (!validate_required($room_form['room_number'])) {
        $errors[] = 'Room number is required.';
    }
    if (!validate_required($room_form['room_type'])) {
        $errors[] = 'Room type is required.';
    }
    if (!validate_required($room_form['capacity']) || !validate_int($room_form['capacity'])) {
        $errors[] = 'Capacity must be a valid number.';
    }
    if (!validate_required($room_form['price_per_night'])) {
        $errors[] = 'Price per night is required.';
    } elseif (!is_numeric($room_form['price_per_night'])) {
        $errors[] = 'Price per night must be numeric.';
    }

    if (!$errors) {
        if ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO rooms (room_number, room_type, capacity, price_per_night, status) VALUES (:room_number, :room_type, :capacity, :price_per_night, :status)');
            $stmt->bindValue(':room_number', $room_form['room_number']);
            $stmt->bindValue(':room_type', $room_form['room_type']);
            $stmt->bindValue(':capacity', (int) $room_form['capacity'], PDO::PARAM_INT);
            $stmt->bindValue(':price_per_night', $room_form['price_per_night']);
            $stmt->bindValue(':status', $room_form['status']);

            try {
                $stmt->execute();
                set_flash('success', 'Room created.');
                header('Location: rooms.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Unable to create room. Ensure the room number is unique.';
            }
        }

        if ($action === 'update' && $room_id > 0) {
            $stmt = $pdo->prepare('UPDATE rooms SET room_number = :room_number, room_type = :room_type, capacity = :capacity, price_per_night = :price_per_night, status = :status WHERE id = :id');
            $stmt->bindValue(':room_number', $room_form['room_number']);
            $stmt->bindValue(':room_type', $room_form['room_type']);
            $stmt->bindValue(':capacity', (int) $room_form['capacity'], PDO::PARAM_INT);
            $stmt->bindValue(':price_per_night', $room_form['price_per_night']);
            $stmt->bindValue(':status', $room_form['status']);
            $stmt->bindValue(':id', $room_id, PDO::PARAM_INT);

            try {
                $stmt->execute();
                set_flash('success', 'Room updated.');
                header('Location: rooms.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Unable to update room.';
            }
        }

        if ($action === 'delete' && $room_id > 0) {
            $stmt = $pdo->prepare('DELETE FROM rooms WHERE id = :id');
            $stmt->bindValue(':id', $room_id, PDO::PARAM_INT);

            try {
                $stmt->execute();
                set_flash('success', 'Room deleted.');
            } catch (PDOException $e) {
                set_flash('error', 'Unable to delete room. It may be linked to bookings.');
            }

            header('Location: rooms.php');
            exit();
        }
    }
}

if ($action === 'edit' && $requested_id > 0) {
    $is_editing = true;
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = :id');
    $stmt->bindValue(':id', $requested_id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $room = $stmt->fetch();

        if ($room) {
            $room_form = [
                'room_number' => $room['room_number'],
                'room_type' => $room['room_type'],
                'capacity' => (string) $room['capacity'],
                'price_per_night' => (string) $room['price_per_night'],
                'status' => $room['status'],
            ];
        } else {
            $errors[] = 'Room not found.';
        }
    } catch (PDOException $e) {
        $errors[] = 'Unable to load room details.';
    }
}

try {
    $rooms_stmt = $pdo->query('SELECT * FROM rooms ORDER BY room_number ASC');
    $rooms = $rooms_stmt->fetchAll();
} catch (PDOException $e) {
    $rooms = [];
    $errors[] = 'Unable to load rooms.';
}

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/nav.php';
?>
<div class="card">
    <h2><?php echo $is_editing ? 'Edit room' : 'Add new room'; ?></h2>
    <?php require __DIR__ . '/../templates/messages.php'; ?>
    <form method="post" novalidate>
        <input type="hidden" name="action" value="<?php echo $is_editing ? 'update' : 'create'; ?>">
        <?php if ($is_editing): ?>
            <input type="hidden" name="id" value="<?php echo $requested_id; ?>">
        <?php endif; ?>
        <div>
            <label for="room_number">Room number</label>
            <input type="text" name="room_number" id="room_number" value="<?php echo htmlspecialchars($room_form['room_number']); ?>" required>
        </div>
        <div>
            <label for="room_type">Room type</label>
            <input type="text" name="room_type" id="room_type" value="<?php echo htmlspecialchars($room_form['room_type']); ?>" required>
        </div>
        <div>
            <label for="capacity">Capacity</label>
            <input type="number" name="capacity" id="capacity" value="<?php echo htmlspecialchars($room_form['capacity']); ?>" min="1" required>
        </div>
        <div>
            <label for="price_per_night">Price per night</label>
            <input type="number" step="0.01" name="price_per_night" id="price_per_night" value="<?php echo htmlspecialchars($room_form['price_per_night']); ?>" required>
        </div>
        <div>
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="available" <?php echo $room_form['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                <option value="maintenance" <?php echo $room_form['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                <option value="occupied" <?php echo $room_form['status'] === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
            </select>
        </div>
        <div>
            <input type="submit" value="<?php echo $is_editing ? 'Update room' : 'Create room'; ?>">
            <?php if ($is_editing): ?>
                <a class="button-link" href="rooms.php">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>Rooms list</h2>
    <?php if ($rooms): ?>
        <table>
            <thead>
                <tr>
                    <th>Room number</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                        <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                        <td><?php echo htmlspecialchars($room['price_per_night']); ?></td>
                        <td><?php echo htmlspecialchars($room['status']); ?></td>
                        <td>
                            <a class="button-link" href="rooms.php?action=edit&id=<?php echo $room['id']; ?>">Edit</a>
                            <form method="post" action="rooms.php" class="inline-form" onsubmit="return confirm('Delete this room?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                                <input type="submit" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No rooms yet.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
