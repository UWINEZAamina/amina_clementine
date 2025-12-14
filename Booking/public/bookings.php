<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/validation.php';

require_login();

$page_title = 'Manage Bookings';
$flash = get_flash();
$errors = [];
$success_message = '';
$pdo = get_pdo();

$rooms = [];
try {
    $room_stmt = $pdo->query("SELECT id, room_number FROM rooms WHERE status != 'maintenance' ORDER BY room_number");
    $rooms = $room_stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Unable to load rooms list.';
}

$booking_form = [
    'guest_name' => '',
    'room_id' => '',
    'check_in' => '',
    'check_out' => '',
    'status' => 'pending',
];
$is_editing = false;
$requested_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $booking_form['guest_name'] = trim($_POST['guest_name'] ?? '');
    $booking_form['room_id'] = trim($_POST['room_id'] ?? '');
    $booking_form['check_in'] = trim($_POST['check_in'] ?? '');
    $booking_form['check_out'] = trim($_POST['check_out'] ?? '');
    $booking_form['status'] = $_POST['status'] ?? 'pending';

    if (!validate_required($booking_form['guest_name'])) {
        $errors[] = 'Guest name is required.';
    }
    if (!validate_required($booking_form['room_id']) || !validate_int($booking_form['room_id'])) {
        $errors[] = 'Room selection is required.';
    }
    if (!validate_required($booking_form['check_in'])) {
        $errors[] = 'Check-in date is required.';
    }
    if (!validate_required($booking_form['check_out'])) {
        $errors[] = 'Check-out date is required.';
    }
    if (!$errors && strtotime($booking_form['check_in']) >= strtotime($booking_form['check_out'])) {
        $errors[] = 'Check-out must be after check-in.';
    }

    if (!$errors) {
        if ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO bookings (user_id, room_id, guest_name, check_in, check_out, status) VALUES (:user_id, :room_id, :guest_name, :check_in, :check_out, :status)');
            $stmt->bindValue(':user_id', (int) ($_SESSION['user_id'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':room_id', (int) $booking_form['room_id'], PDO::PARAM_INT);
            $stmt->bindValue(':guest_name', $booking_form['guest_name']);
            $stmt->bindValue(':check_in', $booking_form['check_in']);
            $stmt->bindValue(':check_out', $booking_form['check_out']);
            $stmt->bindValue(':status', $booking_form['status']);

            try {
                $stmt->execute();
                set_flash('success', 'Booking created.');
                header('Location: bookings.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Unable to create booking. Ensure the room exists.';
            }
        }

        if ($action === 'update' && $booking_id > 0) {
            $stmt = $pdo->prepare('UPDATE bookings SET room_id = :room_id, guest_name = :guest_name, check_in = :check_in, check_out = :check_out, status = :status WHERE id = :id');
            $stmt->bindValue(':room_id', (int) $booking_form['room_id'], PDO::PARAM_INT);
            $stmt->bindValue(':guest_name', $booking_form['guest_name']);
            $stmt->bindValue(':check_in', $booking_form['check_in']);
            $stmt->bindValue(':check_out', $booking_form['check_out']);
            $stmt->bindValue(':status', $booking_form['status']);
            $stmt->bindValue(':id', $booking_id, PDO::PARAM_INT);

            try {
                $stmt->execute();
                set_flash('success', 'Booking updated.');
                header('Location: bookings.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Unable to update booking.';
            }
        }

        if ($action === 'delete' && $booking_id > 0) {
            $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = :id');
            $stmt->bindValue(':id', $booking_id, PDO::PARAM_INT);

            try {
                $stmt->execute();
                set_flash('success', 'Booking deleted.');
            } catch (PDOException $e) {
                set_flash('error', 'Unable to delete booking.');
            }

            header('Location: bookings.php');
            exit();
        }
    }
}

if ($action === 'edit' && $requested_id > 0) {
    $is_editing = true;
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id');
    $stmt->bindValue(':id', $requested_id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $booking = $stmt->fetch();

        if ($booking) {
            $booking_form = [
                'guest_name' => $booking['guest_name'],
                'room_id' => (string) $booking['room_id'],
                'check_in' => $booking['check_in'],
                'check_out' => $booking['check_out'],
                'status' => $booking['status'],
            ];
        } else {
            $errors[] = 'Booking not found.';
        }
    } catch (PDOException $e) {
        $errors[] = 'Unable to load booking.';
    }
}

try {
    $bookings_stmt = $pdo->query('SELECT b.id, b.guest_name, b.check_in, b.check_out, b.status, r.room_number, u.name AS created_by
        FROM bookings b
        INNER JOIN rooms r ON r.id = b.room_id
        INNER JOIN users u ON u.id = b.user_id
        ORDER BY b.check_in DESC');
    $bookings = $bookings_stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
    $errors[] = 'Unable to load bookings.';
}

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/nav.php';
?>
<div class="card">
    <h2><?php echo $is_editing ? 'Edit booking' : 'Add new booking'; ?></h2>
    <?php require __DIR__ . '/../templates/messages.php'; ?>
    <?php if ($rooms): ?>
        <form method="post" novalidate>
            <input type="hidden" name="action" value="<?php echo $is_editing ? 'update' : 'create'; ?>">
            <?php if ($is_editing): ?>
                <input type="hidden" name="id" value="<?php echo $requested_id; ?>">
            <?php endif; ?>
            <div>
                <label for="guest_name">Guest name</label>
                <input type="text" name="guest_name" id="guest_name" value="<?php echo htmlspecialchars($booking_form['guest_name']); ?>" required>
            </div>
            <div>
                <label for="room_id">Room</label>
                <select name="room_id" id="room_id" required>
                    <option value="">Select a room</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo $room['id']; ?>" <?php echo $booking_form['room_id'] === (string) $room['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($room['room_number']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="check_in">Check-in</label>
                <input type="date" name="check_in" id="check_in" value="<?php echo htmlspecialchars($booking_form['check_in']); ?>" required>
            </div>
            <div>
                <label for="check_out">Check-out</label>
                <input type="date" name="check_out" id="check_out" value="<?php echo htmlspecialchars($booking_form['check_out']); ?>" required>
            </div>
            <div>
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="pending" <?php echo $booking_form['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $booking_form['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="cancelled" <?php echo $booking_form['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div>
                <input type="submit" value="<?php echo $is_editing ? 'Update booking' : 'Create booking'; ?>">
                <?php if ($is_editing): ?>
                    <a class="button-link" href="bookings.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    <?php else: ?>
        <p>Please add rooms before creating bookings.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Bookings list</h2>
    <?php if ($bookings): ?>
        <table>
            <thead>
                <tr>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Created by</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                        <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
                        <td><?php echo htmlspecialchars($booking['status']); ?></td>
                        <td><?php echo htmlspecialchars($booking['created_by']); ?></td>
                        <td>
                            <a class="button-link" href="bookings.php?action=edit&id=<?php echo $booking['id']; ?>">Edit</a>
                            <form method="post" action="bookings.php" class="inline-form" onsubmit="return confirm('Delete this booking?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $booking['id']; ?>">
                                <input type="submit" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No bookings recorded yet.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
