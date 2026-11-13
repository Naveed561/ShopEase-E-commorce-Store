# ShopEase email setup

## Install

The PHPMailer dependency is declared in `composer.json` and locked in `composer.lock`. From the ShopEase project directory, run:

```text
composer install
```

Import `password_resets.sql` into the `shopease` database. The application database user needs normal `SELECT`, `INSERT`, `UPDATE`, and `DELETE` access to this table.

## Configure SMTP

Set these environment variables for the PHP/Apache process, or use the local configuration file described below. Do not put SMTP passwords in source control.

| Variable | Example | Notes |
| --- | --- | --- |
| `APP_BASE_URL` | `http://localhost/ShopEase` | Absolute base URL for this app; use the public HTTPS URL in production. |
| `MAIL_HOST` | `smtp.gmail.com` | Optional. Defaults to Gmail SMTP; set this only for another provider. |
| `MAIL_PORT` | `587` | Optional. Defaults to 587 for STARTTLS; use 465 for implicit TLS. |
| `MAIL_USERNAME` | `your-address@gmail.com` | SMTP account username. |
| `MAIL_PASSWORD` | *(app password)* | For Gmail, use a Google App Password with 2-Step Verification enabled, not your normal account password. |
| `MAIL_FROM_ADDRESS` | `your-address@gmail.com` | Optional; defaults to `MAIL_USERNAME`. |
| `MAIL_FROM_NAME` | `ShopEase` | Optional sender display name. |
| `MAIL_TEST_RECIPIENT` | `your-address@example.com` | Optional; recipient used only by the command-line SMTP test. |

### Gmail and XAMPP on Windows

1. In the Google Account that will send the mail, enable **2-Step Verification**, then create an **App Password** for ShopEase under Google Account security. Use the generated App Password, not your usual Gmail password. Never send the password to anyone or commit it to the project.
2. The simplest local setup is to open `mail.local.php` in the ShopEase project directory and fill in `MAIL_USERNAME` with your Gmail address and `MAIL_PASSWORD` with the generated App Password. Optionally set `MAIL_FROM_ADDRESS` to that same Gmail address. This file is excluded from Git. Do not share it or commit it.
3. Alternatively, add the settings to the Apache configuration used by this site (for example, `C:\xampp\apache\conf\extra\httpd-vhosts.conf` inside the site's `<VirtualHost>`). Replace the example address and password:

```apache
SetEnv MAIL_HOST smtp.gmail.com
SetEnv MAIL_PORT 587
SetEnv MAIL_USERNAME "your-address@gmail.com"
SetEnv MAIL_PASSWORD "your-16-character-app-password"
SetEnv MAIL_FROM_ADDRESS "your-address@gmail.com"
SetEnv MAIL_FROM_NAME "ShopEase"
SetEnv APP_BASE_URL "http://localhost/ShopEase"
```

4. If Google displays the App Password in groups separated by spaces, either enter it without spaces or leave the spaces; ShopEase removes spaces from Gmail App Passwords before authenticating.
5. If using Apache settings, save the configuration and restart Apache in the XAMPP Control Panel. The settings must be available to Apache/PHP; setting them in a separate terminal is not enough.
6. Sign in to ShopEase and place an order to test the receipt email. Guest orders do not send receipts. For password-reset mail, use the Forgot Password page with a registered account.
7. To test SMTP without placing an order, open PowerShell in the ShopEase project directory, set the SMTP variables and `MAIL_TEST_RECIPIENT` for that PowerShell process, then run the test script. For example:

```powershell
$env:MAIL_HOST = "smtp.gmail.com"
$env:MAIL_PORT = "587"
$env:MAIL_USERNAME = "your-address@gmail.com"
$env:MAIL_PASSWORD = "your-app-password"
$env:MAIL_TEST_RECIPIENT = "your-address@example.com"
& "C:\xampp\php\php.exe" .\test_email.php
```

Replace the example values and use your XAMPP PHP path if it differs. Do not save the App Password in a script. The test script is CLI-only.

Use `smtp.gmail.com` on port `587` with STARTTLS, or port `465` with implicit TLS. The application defaults to the port-587 settings. Ensure the OpenSSL extension is enabled for the PHP version used by Apache and that the machine/network can connect to `smtp.gmail.com` on the selected port.

### If sending still fails

- For order receipts, read the warning shown in the cart after checkout. The order can still be saved even if the email fails.
- Check the Apache/PHP error log for the detailed SMTP failure. In a typical XAMPP install, check `C:\xampp\apache\logs\error.log`.
- “Could not authenticate” usually means the Gmail address is wrong, 2-Step Verification/App Password setup is incomplete, the normal account password was used, or an old App Password was revoked. Create a fresh App Password and update Apache's `MAIL_PASSWORD`.
- Connection timeouts usually mean outbound SMTP is blocked by a firewall, hosting provider, or network. Try another permitted SMTP provider if port 587/465 is blocked.
- Keep `MAIL_FROM_ADDRESS` the same as `MAIL_USERNAME` unless the sender address is configured as an authorized Gmail alias.
- Do not publish a diagnostic PHP page that reveals the environment variables or SMTP password. Never share that password in chat, screenshots, logs, or source code.

The password reset page responds with the same confirmation for registered and unregistered addresses. Reset requests are limited to one email per account per minute. Reset links expire after one hour and can only be used once.

When a signed-in customer confirms an order, ShopEase emails a receipt containing every product in that order, its quantity and price, and the order total. The cart displays a success message only after the SMTP server accepts the email. Missing SMTP settings are named in the checkout warning; other delivery errors are recorded in the PHP/Apache error log. Guest orders remain supported, but no email is sent because guest checkout does not collect an email address. The homepage does not currently have a contact/message form.
