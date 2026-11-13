<?php
session_start();

if (isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$email = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        include "connection.php";

        $userQuery = $conn->prepare(
            'SELECT id, name, password FROM users WHERE email = ?'
        );
        if (!$userQuery) {
            throw new RuntimeException('Could not prepare login query: ' . $conn->error);
        }

        $userQuery->bind_param('s', $email);
        if (!$userQuery->execute()) {
            throw new RuntimeException('Could not check login credentials: ' . $userQuery->error);
        }

        $userQuery->bind_result($userId, $storedUserName, $storedPassword);
        if ($userQuery->fetch() && password_verify($password, $storedPassword)) {
            $userQuery->close();
            session_regenerate_id(true);
            $_SESSION['id'] = (int)$userId;
            $_SESSION['user_name'] = $storedUserName;

            header('Location: index.php');
            exit;
        }
        $userQuery->close();
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - ShopEase</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-4">Login</h1>
                    <?php if ($error !== null) { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" name="email" type="email" class="form-control" required
                                   autocomplete="username"
                                   value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" name="password" type="password"
                                   class="form-control" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <a href="forgot_password.php" class="btn btn-link w-100">Forgot your password?</a>
                    <a href="register.php" class="btn btn-link w-100">Create an account</a>
                    <a href="index.php" class="btn btn-link w-100 mt-2">Continue shopping</a>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
