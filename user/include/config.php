<?php
// Database — reads from environment variables (Railway/production) or falls back to XAMPP defaults
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'crop_insurance_one');

// Base URL: empty on Railway, /KissanSure on localhost
define('BASE_URL', getenv('BASE_URL') ?: '/KissanSure');

