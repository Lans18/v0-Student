<?php
/**
 * SMS Configuration
 * Configure your SMS service here
 */

// SMS Service Configuration
define('SMS_SERVICE', 'twilio'); // Options: 'twilio', 'nexmo', 'aws-sns'
define('TWILIO_ACCOUNT_SID', getenv('TWILIO_ACCOUNT_SID') ?: '');
define('TWILIO_AUTH_TOKEN', getenv('TWILIO_AUTH_TOKEN') ?: '');
define('TWILIO_PHONE_NUMBER', getenv('TWILIO_PHONE_NUMBER') ?: '');

// Nexmo Configuration (if using Nexmo)
define('NEXMO_API_KEY', getenv('NEXMO_API_KEY') ?: '');
define('NEXMO_API_SECRET', getenv('NEXMO_API_SECRET') ?: '');

// AWS SNS Configuration (if using AWS)
define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY') ?: '');
define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY') ?: '');

// SMS Retry Configuration
define('SMS_MAX_RETRIES', 3);
define('SMS_RETRY_DELAY', 300); // 5 minutes in seconds

?>
