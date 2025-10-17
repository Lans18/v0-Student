<?php
/**
 * Email Configuration
 * Configure your email service here
 */

// Email Service Configuration
define('EMAIL_SERVICE', 'smtp'); // Options: 'smtp', 'sendgrid', 'mailgun'
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'your-email@gmail.com');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'your-app-password');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@attendance-system.com');
define('SMTP_FROM_NAME', 'Attendance System');

// SendGrid Configuration (if using SendGrid)
define('SENDGRID_API_KEY', getenv('SENDGRID_API_KEY') ?: '');

// Mailgun Configuration (if using Mailgun)
define('MAILGUN_API_KEY', getenv('MAILGUN_API_KEY') ?: '');
define('MAILGUN_DOMAIN', getenv('MAILGUN_DOMAIN') ?: '');

// Email Templates
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../email-templates/');

// Email Retry Configuration
define('EMAIL_MAX_RETRIES', 3);
define('EMAIL_RETRY_DELAY', 300); // 5 minutes in seconds

?>
