<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/flash.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Login';
$errors = [];
$email = '';
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!validate_required($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Email is invalid.';
    }

    if (!validate_required($password)) {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        $pdo = get_pdo();

        $stmt = $pdo->prepare('SELECT id, name, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);

        try {
            $stmt->execute();
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Unable to process request now.';
            $user = null;
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            set_flash('success', 'Welcome back, ' . $user['name'] . '!');
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/nav.php';
?>
<div class="card">
    <h2>Sign in</h2>
    <?php require __DIR__ . '/../templates/messages.php'; ?>
    <form method="post" novalidate>
        <div>
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div>
            <input type="submit" value="Login">
        </div>
    </form>
    <p class="help-text">Default admin: admin@example.com / password</p>
    <p class="help-text">Need an account? <a href="signup.php">Sign up here</a>.</p>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
