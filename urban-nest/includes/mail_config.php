<?php

const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 465;
const SMTP_ENCRYPTION = 'ssl';

define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_USERNAME);
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Urban Nest');

const SMTP_VERIFY_CERT = true;
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/apartment-rental', '/'));
