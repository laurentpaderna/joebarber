<?php

header('Cache-Control: no-cache');
header('Pragma: no-cache');

require __DIR__.'/pdo.php';

// Load our autoloader
require_once __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 'On');

// Start session for connected pages
session_start();

// Check user connection
function checkConnection(bool $connection, string $url) {
    $isConnected = isset($_SESSION['joe_logged']) and $_SESSION['joe_logged'] == true;
    if ($connection) {
        // Redirect to url
        header($url);
        exit();
    }
}

// Set translation on calendar
use Twig\Extra\Intl\IntlExtension;

// Specify our Twig templates location
$loader = new \Twig\Loader\FilesystemLoader(__DIR__."/");
$twig = new \Twig\Environment($loader, [
    "debug" => true
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addExtension(new IntlExtension());
$twig->addGlobal('session', $_SESSION);

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
        require __DIR__ . '/views/main.html.twig';
        break;

    case '' :

        // Redirect to admin dashboard
        header('Location: /');
        break;

    case '/joe' :

        // If user is connected
        checkConnection($isConnected,'Location: /dashboard');

        // Load template for login page
        $template = $twig->load('/views/admin.html.twig');
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

    default:

        // Page not found
        http_response_code(404);
        require __DIR__ . '/views/404.html.twig';
        break;

}
