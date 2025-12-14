<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/flash.php';

require_login();

$page_title = 'Dashboard';
$flash = get_flash();
$errors = [];
$success_message = '';

$stats = [
    'rooms' => 0,
    'bookings' => 0,
    'upcoming' => 0,
];
$upcoming_bookings = [];

try {
    $pdo = get_pdo();

    $stats['rooms'] = (int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    $stats['bookings'] = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();

    $upcoming_stmt = $pdo->prepare('SELECT b.id, b.guest_name, b.check_in, b.check_out, r.room_number
        FROM bookings b
        INNER JOIN rooms r ON r.id = b.room_id
        WHERE b.check_in >= CURDATE()
        ORDER BY b.check_in ASC
        LIMIT 5');
    $upcoming_stmt->execute();
    $upcoming_bookings = $upcoming_stmt->fetchAll();
    $stats['upcoming'] = count($upcoming_bookings);
} catch (PDOException $e) {
    $errors[] = 'Unable to load dashboard data right now.';
}

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/nav.php';
?>
<div class="card">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></h2>
    <?php require __DIR__ . '/../templates/messages.php'; ?>
    <div class="stat-grid">
        <div class="stat">
            <span class="stat-value"><?php echo $stats['rooms']; ?></span>
            <span class="stat-label">Rooms</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?php echo $stats['bookings']; ?></span>
            <span class="stat-label">Total bookings</span>
        </div>
        <div class="stat">
            <span class="stat-value"><?php echo $stats['upcoming']; ?></span>
            <span class="stat-label">Upcoming stays</span>
        </div>
    </div>
</div>

<div class="card">
    <h3>Upcoming bookings</h3>
    <?php if ($upcoming_bookings): ?>
        <table>
            <thead>
                <tr>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcoming_bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                        <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No upcoming bookings yet.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
