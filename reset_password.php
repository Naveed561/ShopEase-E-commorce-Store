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
$token = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['token'] ?? '')
    : ($_GET['token'] ?? '');
$token = is_string($token) ? $token : '';
$tokenIsValid = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$resetAvailable = false;

if ($tokenIsValid) {
    require_once __DIR__ . '/connection.php';
    ensurePasswordResetTable($conn);
    $tokenHash = hash('sha256', $token);
    $tokenQuery = $conn->prepare(
        'SELECT user_id FROM password_resets
         WHERE token_hash = ? AND expires_at > CURRENT_TIMESTAMP'
    );
    if (!$tokenQuery) {
        throw new RuntimeException('Could not prepare password reset validation: ' . $conn->error);
    }
    $tokenQuery->bind_param('s', $tokenHash);
    if (!$tokenQuery->execute()) {
        throw new RuntimeException('Could not validate password reset link: ' . $tokenQuery->error);
    }
    $tokenQuery->store_result();
    $resetAvailable = $tokenQuery->num_rows === 1;
    $tokenQuery->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!is_string($submittedCsrf) ||
        !hash_equals($_SESSION['csrf_token'], $submittedCsrf)) {
        $error = 'Your session has expired. Please try again.';
    } elseif (!$resetAvailable) {
        $error = 'This reset link is invalid or has expired. Request a new one.';
    } elseif (!is_string($password) || strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters long.';
    } elseif (!is_string($confirmPassword) || $password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        $tokenHash = hash('sha256', $token);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Could not securely hash the new password.');
        }

        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Could not begin password reset: ' . $conn->error);
        }
        try {
            $updateQuery = $conn->prepare(
                'UPDATE users
                 INNER JOIN password_resets ON password_resets.user_id = users.id
                 SET users.password = ?
                 WHERE password_resets.token_hash = ?
                   AND password_resets.expires_at > CURRENT_TIMESTAMP'
            );
            if (!$updateQuery) {
                throw new RuntimeException('Could not prepare password update: ' . $conn->error);
            }
            $updateQuery->bind_param('ss', $passwordHash, $tokenHash);
            if (!$updateQuery->execute()) {
                throw new RuntimeException('Could not update password: ' . $updateQuery->error);
            }
            $passwordChanged = $updateQuery->affected_rows === 1;
            $updateQuery->close();

            if (!$passwordChanged) {
                $conn->rollback();
                $error = 'This reset link is invalid or has expired. Request a new one.';
            } else {
                $deleteQuery = $conn->prepare('DELETE FROM password_resets WHERE token_hash = ?');
                if (!$deleteQuery) {
                    throw new RuntimeException('Could not prepare password reset cleanup: ' . $conn->error);
                }
                $deleteQuery->bind_param('s', $tokenHash);
                if (!$deleteQuery->execute()) {
                    throw new RuntimeException('Could not remove used password reset token: ' . $deleteQuery->error);
                }
                $deleteQuery->close();

                if (!$conn->commit()) {
                    throw new RuntimeException('Could not complete password reset: ' . $conn->error);
                }
            }
        } catch (mysqli_sql_exception | RuntimeException $resetError) {
            $conn->rollback();
            throw $resetError;
        }

        if ($error === null) {
            unset($_SESSION['csrf_token']);
            header('Location: login.php');
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
<title>Reset Password - ShopEase</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-4">Reset your password</h1>
                    <?php if ($error !== null) { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <?php if ($resetAvailable) { ?>
                        <form method="POST" action="reset_password.php">
                            <input type="hidden" name="token"
                                   value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token"
                                   value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">New password</label>
                                <input id="password" name="password" type="password" class="form-control"
                                       minlength="8" required autocomplete="new-password">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm new password</label>
                                <input id="confirm_password" name="confirm_password" type="password"
                                       class="form-control" minlength="8" required autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Save new password</button>
                        </form>
                    <?php } elseif ($error === null) { ?>
                        <div class="alert alert-danger" role="alert">
                            This reset link is invalid or has expired. Request a new one.
                        </div>
                    <?php } ?>
                    <a href="forgot_password.php" class="btn btn-link w-100 mt-2">Request another reset link</a>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
