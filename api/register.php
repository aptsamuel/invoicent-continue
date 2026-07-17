<?php
/**
 * Registration handler - small cleanup and safer binds
 */

require_once __DIR__ . '/../config/config.php';

// Load Composer autoloader to make PHPMailer classes available
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF token
    if (empty($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    // Validate input
    $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $input['password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
    $first_name = sanitizeInput($input['first_name'] ?? '');
    $last_name = sanitizeInput($input['last_name'] ?? '');

    $errors = [];

    // Validation
    if (!$email) {
        $errors[] = 'Valid email is required';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    if (strlen($first_name) < 2) {
        $errors[] = 'First name must be at least 2 characters';
    }

    if (strlen($last_name) < 2) {
        $errors[] = 'Last name must be at least 2 characters';
    }

    // Check password strength
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors[] = 'Password must contain uppercase, lowercase, number, and special character';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Generate email verification token
    $verification_token = bin2hex(random_bytes(32));
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, first_name, last_name, email_verification_token, email_verification_expires) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $email, $password_hash, $first_name, $last_name, $verification_token, $verification_expires);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $user_id = $conn->insert_id;
    $stmt->close();

    // Create user settings record
    $default_business_name = $first_name . ' ' . $last_name;
    $currency_code = 'NGN';
    $currency_symbol = '₦';

    $stmt = $conn->prepare("INSERT INTO user_settings (user_id, business_name, currency_code, currency_symbol) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("isss", $user_id, $default_business_name, $currency_code, $currency_symbol);
    $stmt->execute();
    $stmt->close();

    // ==========================================
    // PHPMailer: SEND VERIFICATION EMAIL
    // ==========================================
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();                                            
        $mail->Host       = SMTP_HOST;               
        $mail->SMTPAuth   = SMTP_AUTH;                                   
        $mail->Username   = SMTP_USER;      
        $mail->Password   = SMTP_PASS;                      
        $mail->Port       = SMTP_PORT;                                    
        $mail->SMTPSecure = SMTP_ENCRYPTION;    

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $first_name . ' ' . $last_name);     

        // Build verification link
        $verification_link = APP_URL . "/verify.php?token=" . $verification_token;

        $mail->isHTML(true);                                  
        $mail->Subject = 'Verify Your Account - ' . SMTP_FROM_NAME;
        $mail->Body    = "<h1>Welcome, " . htmlspecialchars($first_name) . "!</h1>"
                       . "<p>Thank you for registering at " . SMTP_FROM_NAME . ". Please click the link below to verify your account:</p>"
                       . "<p><a href='" . $verification_link . "' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify My Email Address</a></p>"
                       . "<p>This link will expire in 24 hours.</p>";
        $mail->AltBody = "Welcome, " . $first_name . "!\n\nPlease copy and paste the following link into your browser to verify your email address:\n" . $verification_link . "\n\nThis link will expire in 24 hours.";

        $mail->send();
    } catch (Exception $e) {
        // Log mail errors to file but allow registration to complete
        logError("Verification email failed to send", ['user_id' => $user_id, 'mailer_error' => $mail->ErrorInfo]);
    }
    // ==========================================

    logActivity($user_id, 'registration', 'User registered successfully');

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Please check your email to verify your account.',
        'user_id' => $user_id
    ]);

} catch (Exception $e) {
    logError("Registration error", ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

/**
 * Sanitize input string
 */
function sanitizeInput($input) {
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}
?>
