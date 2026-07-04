<?php

// Set env file and variable
$envFile = __DIR__ . '/../docker/.env';

// Set detection on .env file
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

try {
    // Set utilities functions
    require_once __DIR__ . '/utilities.php';
    // Load our autoloader
    require_once __DIR__.'/../vendor/autoload.php';
    // Clean entries from database
    require __DIR__.'/pdo.php';
} catch (\Error $e) {
    echo $e->getMessage();
}

// Set php mailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Set global mail variable
$GLOBALS['mail'] = new PHPMailer(true);

// Set translation on calendar
use Twig\Extra\Intl\IntlExtension;

// Remove cache on client side
header('Cache-Control: no-cache');
header('Pragma: no-cache');
//date_default_timezone_set('Europe/Paris');

// Set error reporting level
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// Start session for connected pages
session_start();

// Specify our Twig templates location
$loader = new \Twig\Loader\FilesystemLoader(__DIR__."/");
$GLOBALS['twig'] = new \Twig\Environment($loader, [
    "debug" => true
]);
$GLOBALS['twig']->addExtension(new \Twig\Extension\DebugExtension());
$GLOBALS['twig']->addExtension(new IntlExtension());
$GLOBALS['twig']->addGlobal('session', $_SESSION);

// Get current uri
$request = $_SERVER['REQUEST_URI'];
$currentUrl = parse_url($request, PHP_URL_PATH);

// Connection status
$isConnected = isset($_SESSION['joe_logged']) and $_SESSION['joe_logged'] == true;
$isNotConnected = !isset($_SESSION['joe_logged']) or $_SESSION['joe_logged'] == false;

// Set url from requested uri
switch ($request) {
    case '/' :

        // Main page
        require __DIR__ . '/main.php';

        break;

    case '' :

        // Main page
        require __DIR__ . '/main.php';

        break;

    case '/joe' :

        // If user is connected
        checkConnection($isConnected,'Location: /dashboard');

        // Load template for login page
        $template = $GLOBALS['twig']->load('/views/admin.html.twig');
        require __DIR__ . '/login.php';

        break;

    case '/dashboard' :

        // If no user is connected, or disconnected
        checkConnection($isNotConnected,'Location: /joe');

        // Admin dashboard page
        require __DIR__ . '/dashboard.php';

        break;

    case '/logout' :

        require __DIR__ . '/logout.php';

        break;

    case '/requests' :

        // If no user is connected, or disconnected
        checkConnection($isNotConnected,'Location: /joe');

        require __DIR__ . '/requests.php';

        break;

    case (str_contains($currentUrl,'agenda')) :

        // If no user is connected, or disconnected
        checkConnection($isNotConnected,'Location: /joe');

        // Load template for login page
        require __DIR__ . '/agenda.php';

        break;

    case (str_contains($currentUrl,'appointment')) :

        // Load template for login page
        require __DIR__ . '/email.php';

        break;

    case (str_contains($currentUrl,'cancel')) :

        // Load template for login page
        require __DIR__ . '/cancel.php';

        break;

    default:

        // Page not found
        http_response_code(404);
        require __DIR__ . '/views/404.html.twig';
        break;

}
