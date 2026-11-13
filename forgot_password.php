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
$email = '';
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedEmail = $_POST['email'] ?? '';
    $email = is_string($submittedEmail) ? trim($submittedEmail) : '';
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Your session has expired. Please try again.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        require_once __DIR__ . '/connection.php';
        ensurePasswordResetTable($conn);

        $userQuery = $conn->prepare('SELECT id, name, email FROM users WHERE email = ?');
        if (!$userQuery) {
            throw new RuntimeException('Could not prepare password reset lookup: ' . $conn->error);
        }

        $userQuery->bind_param('s', $email);
        if (!$userQuery->execute()) {
            throw new RuntimeException('Could not look up password reset account: ' . $userQuery->error);
        }
        $userQuery->bind_result($userId, $userName, $userEmail);
        $userFound = $userQuery->fetch();
        $userQuery->close();

        if ($userFound) {
            $rateQuery = $conn->prepare(
                'SELECT 1 FROM password_resets
                 WHERE user_id = ? AND created_at > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE)'
            );
            if (!$rateQuery) {
                throw new RuntimeException('Could not prepare password reset rate check: ' . $conn->error);
            }
            $userId = (int)$userId;
            $rateQuery->bind_param('i', $userId);
            if (!$rateQuery->execute()) {
                throw new RuntimeException('Could not check password reset rate: ' . $rateQuery->error);
            }
            $rateQuery->bind_result($recentRequest);
            $hasRecentRequest = $rateQuery->fetch();
            $rateQuery->close();

            if (!$hasRecentRequest) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $saveQuery = $conn->prepare(
                    'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
                     VALUES (?, ?, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR), CURRENT_TIMESTAMP)
                     ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash),
                         expires_at = VALUES(expires_at), created_at = CURRENT_TIMESTAMP'
                );
                if (!$saveQuery) {
                    throw new RuntimeException('Could not prepare password reset token: ' . $conn->error);
                }
                $saveQuery->bind_param('is', $userId, $tokenHash);
                if (!$saveQuery->execute()) {
                    throw new RuntimeException('Could not save password reset token: ' . $saveQuery->error);
                }
                $saveQuery->close();

                try {
                    require_once __DIR__ . '/mail_helper.php';
                    $baseUrl = shopEaseRequiredEnv('APP_BASE_URL');
                    $baseUrlParts = parse_url($baseUrl);
                    if (!filter_var($baseUrl, FILTER_VALIDATE_URL) ||
                        !is_array($baseUrlParts) ||
                        !in_array($baseUrlParts['scheme'] ?? '', ['http', 'https'], true) ||
                        empty($baseUrlParts['host'])) {
                        throw new RuntimeException('APP_BASE_URL must be an absolute HTTP or HTTPS URL.');
                    }

                    $resetUrl = rtrim($baseUrl, '/') . '/reset_password.php?token=' . urlencode($token);
                    $safeName = htmlspecialchars((string)$userName, ENT_QUOTES, 'UTF-8');
                    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
                    $html = '<p>Hello ' . $safeName . ',</p><p>We received a request to reset your ShopEase password.</p>'
                        . '<p><a href="' . $safeUrl . '">Reset your password</a></p>'
                        . '<p>This link expires in one hour. If you did not request this, you can ignore this email.</p>';
                    $text = "Hello {$userName},\n\nReset your ShopEase password using this link (valid for one hour):\n"
                        . $resetUrl . "\n\nIf you did not request this, you can ignore this email.";
                    shopEaseSendEmail((string)$userEmail, (string)$userName, 'Reset your ShopEase password', $html, $text);
                } catch (PHPMailer\PHPMailer\Exception | RuntimeException $mailError) {
                    error_log('Password reset email could not be sent: ' . $mailError->getMessage());
                }
            }
        }

        $notice = 'If an account exists for that email address, a password reset link will be sent shortly.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password - ShopEase</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">Forgot your password?</h1>
                    <p class="text-muted">Enter your account email and we will send a secure reset link.</p>
                    <?php if ($error !== null) { ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <?php if ($notice !== null) { ?>
                        <div class="alert alert-info" role="status">
                            <?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <form method="POST" action="forgot_password.php">
                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" name="email" type="email" class="form-control" required
                                   autocomplete="email"
                                   value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send reset link</button>
                    </form>
                    <a href="login.php" class="btn btn-link w-100 mt-2">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
