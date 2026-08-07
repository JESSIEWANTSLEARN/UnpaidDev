<?php
// mailer.php
// Uses PHPMailer, installed via Composer.
// Run this once in your project folder before this file will work:
//   composer require phpmailer/phpmailer
 
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
