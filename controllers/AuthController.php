<?php

class AuthController extends BaseController {
    public function login() {
        require_once __DIR__ . '/../helpers/csrf.php';
        require_once __DIR__ . '/../helpers/mailer.php';
        require_once __DIR__ . '/../helpers/rate_limiter.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (rl_is_blocked($ip)) {
                $rem = rl_lock_remaining($ip);
                $error = "Too many failed attempts. Try again in $rem seconds.";
                $this->view('auth/login.php', ['error' => $error]);
                return;
            }

            $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // generate OTP, store temporarily in session and email it
                // clear IP-based failures on success
                rl_register_attempt($ip, true);
                $otp = random_int(100000, 999999);
                $_SESSION['pre_2fa_user'] = $user['id'];
                $_SESSION['otp_code'] = (string)$otp;
                $_SESSION['otp_expires'] = time() + 300; // 5 minutes
                // rate-limit counters
                $_SESSION['otp_resend_count'] = 0;
                $_SESSION['otp_last_sent'] = time();
                $_SESSION['otp_attempts'] = 0;

                $subject = 'Your Backoffice OTP Code';
                // try to render HTML template
                $html = null;
                $tpl = __DIR__ . '/../views/email/otp.php';
                if (file_exists($tpl)) {
                    ob_start();
                    $otp_local = $otp; // template variable
                    include $tpl;
                    $html = ob_get_clean();
                }
                if ($html) {
                    mailer_send($user['email'], $subject, $html, true);
                } else {
                    $body = "Your OTP code is: $otp\nThis code is valid for 5 minutes.";
                    mailer_send($user['email'], $subject, $body);
                }

                $this->view('auth/verify_2fa.php');
                return;
            } else {
                // register failed attempt for IP
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                rl_register_attempt($ip, false);
                $d = rl_get($ip);
                if (!empty($d['lock_until'])) {
                    $rem = rl_lock_remaining($ip);
                    $error = "Too many failed attempts. Try again in $rem seconds.";
                } else {
                    $error = 'Invalid credentials';
                }
                $this->view('auth/login.php', ['error' => $error]);
            }
        } else {
            $this->view('auth/login.php');
        }
    }

    public function logout() {
        // set a one-time message after logout
        $_SESSION['flash_message'] = 'You have been logged out.';
        $_SESSION['flash_type'] = 'info';
        session_unset();
        session_destroy();
        $this->redirect('/login');
    }

    public function register() {
        require_once __DIR__ . '/../helpers/csrf.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'agent';

            if (!$name || !$email || !$password) {
                $error = 'Please fill all fields';
                $this->view('auth/register.php', ['error' => $error]);
                return;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
            try {
                $stmt->execute([$name, $email, $hash, $role]);
                // redirect to login
                $this->redirect('/login');
            } catch (Exception $e) {
                $error = 'Could not register (email may already exist)';
                $this->view('auth/register.php', ['error' => $error]);
            }
        } else {
            $this->view('auth/register.php');
        }
    }

    public function verify2fa() {
        require_once __DIR__ . '/../helpers/csrf.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $otp = trim($_POST['otp'] ?? '');
            if (empty($_SESSION['otp_code']) || time() > ($_SESSION['otp_expires'] ?? 0)) {
                $error = 'OTP expired. Please login again.';
                $this->view('auth/login.php', ['error' => $error]);
                return;
            }
            // increment attempt counter and enforce max attempts
            $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
            $maxAttempts = 5;
            if ($_SESSION['otp_attempts'] > $maxAttempts) {
                // clear sensitive session
                unset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['pre_2fa_user']);
                $error = 'Maximum OTP attempts exceeded. Please login again.';
                $this->view('auth/login.php', ['error' => $error]);
                return;
            }
            if ($otp === $_SESSION['otp_code']) {
                // finalize login
                $userId = $_SESSION['pre_2fa_user'];
                $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                // store minimal user info for views/layouts
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'] ?? '',
                    'email' => $user['email'] ?? ''
                ];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                // clear OTP
                unset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['pre_2fa_user']);

                $this->redirect('/dashboard');
            } else {
                $error = 'Invalid OTP';
                $this->view('auth/verify_2fa.php', ['error' => $error]);
            }
        } else {
            $this->view('auth/verify_2fa.php');
        }
    }

    public function resendOtp() {
        require_once __DIR__ . '/../helpers/csrf.php';
        // allow only POST to trigger resend to avoid CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }
        verify_csrf();
        // check session and cooldown
        $userId = $_SESSION['pre_2fa_user'] ?? null;
        if (empty($userId)) {
            $this->redirect('/login');
            return;
        }
        $now = time();
        $last = $_SESSION['otp_last_sent'] ?? 0;
        $resendCount = $_SESSION['otp_resend_count'] ?? 0;
        $cooldown = 60; // seconds
        $maxResends = 3;
        if ($now - $last < $cooldown) {
            $error = 'Please wait before requesting another OTP.';
            $this->view('auth/verify_2fa.php', ['error' => $error]);
            return;
        }
        if ($resendCount >= $maxResends) {
            $error = 'Resend limit reached. Please login again.';
            // clear sensitive session
            unset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['pre_2fa_user']);
            $this->view('auth/login.php', ['error' => $error]);
            return;
        }

        // generate and send new OTP
        $otp = random_int(100000, 999999);
        $_SESSION['otp_code'] = (string)$otp;
        $_SESSION['otp_expires'] = time() + 300;
        $_SESSION['otp_last_sent'] = $now;
        $_SESSION['otp_resend_count'] = $resendCount + 1;

        // load user email
        $stmt = $this->db->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if (!$u) {
            $this->redirect('/login');
            return;
        }
        $subject = 'Your Backoffice OTP Code';
        $tpl = __DIR__ . '/../views/email/otp.php';
        if (file_exists($tpl)) {
            ob_start(); $otp_local = $otp; include $tpl; $html = ob_get_clean();
            mailer_send($u['email'], $subject, $html, true);
        } else {
            $body = "Your OTP code is: $otp\nThis code is valid for 5 minutes.";
            mailer_send($u['email'], $subject, $body);
        }

        $this->view('auth/verify_2fa.php', ['message' => 'A new OTP has been sent to your email.']);
    }
}
