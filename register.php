<?php
session_start();

if (isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = null;
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Your session has expired. Please try again.';
    } elseif ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Complete all fields to register.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        include "connection.php";

        $emailQuery = $conn->prepare('SELECT id FROM users WHERE email = ?');
        if (!$emailQuery) {
            throw new RuntimeException('Could not prepare email check: ' . $conn->error);
        }

        $emailQuery->bind_param('s', $email);
        if (!$emailQuery->execute()) {
            throw new RuntimeException('Could not check email: ' . $emailQuery->error);
        }

        $emailQuery->store_result();
        $emailExists = $emailQuery->num_rows > 0;
        $emailQuery->close();

        if ($emailExists) {
            $error = 'An account with this email already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertQuery = $conn->prepare(
                'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
            );
            if (!$insertQuery) {
                throw new RuntimeException('Could not prepare registration: ' . $conn->error);
            }

            $insertQuery->bind_param('sss', $name, $email, $passwordHash);
            if (!$insertQuery->execute()) {
                throw new RuntimeException('Could not create account: ' . $insertQuery->error);
            }

            $userId = $conn->insert_id;
            $insertQuery->close();
            session_regenerate_id(true);
            $_SESSION['id'] = (int)$userId;
            $_SESSION['user_name'] = $name;
            unset($_SESSION['csrf_token']);

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register - ShopEase</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-4">Create your account</h1>
                    <?php if ($error !== null) { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <form method="POST" action="register.php">
                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input id="name" name="name" class="form-control" required
                                   autocomplete="name"
                                   value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" name="email" type="email" class="form-control" required
                                   autocomplete="email"
                                   value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" name="password" type="password" class="form-control"
                                   minlength="8" required autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm password</label>
                            <input id="confirm_password" name="confirm_password" type="password"
                                   class="form-control" minlength="8" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    <a href="login.php" class="btn btn-link w-100 mt-2">Already have an account? Login</a>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
