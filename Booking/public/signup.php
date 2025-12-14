<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/flash.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Sign up';
$errors = [];
$flash = get_flash();
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!validate_required($name)) {
        $errors[] = 'Name is required.';
    }
    if (!validate_required($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Email looks invalid.';
    }
    if (!validate_required($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $pdo = get_pdo();

        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->bindValue(':email', $email, PDO::PARAM_STR);

        try {
            $check->execute();
            if ($check->fetch()) {
                $errors[] = 'Email already registered.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to process request now.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            $stmt->bindValue(':role', 'staff', PDO::PARAM_STR);

            try {
                $stmt->execute();
                set_flash('success', 'Account created. You can sign in now.');
                header('Location: login.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = 'Failed to create account. Please try again.';
            }
        }
    }
}

require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/nav.php';
?>
<div class="card">
    <h2>Create your account</h2>
    <?php require __DIR__ . '/../templates/messages.php'; ?>
    <form method="post" novalidate>
        <div>
            <label for="name">Name</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div>
            <label for="confirm_password">Confirm password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <div>
            <input type="submit" value="Register">
        </div>
    </form>
    <p class="help-text">Already have an account? <a href="login.php">Login here</a>.</p>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
