<?php
$is_logged_in = !empty($_SESSION['user_id']);
?>
<nav class="top-nav">
    <h1 class="brand">Hostel Booking</h1>
    <ul>
        <?php if ($is_logged_in): ?>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="rooms.php">Rooms</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="signup.php">Sign up</a></li>
        <?php endif; ?>
    </ul>
</nav>
<main>
