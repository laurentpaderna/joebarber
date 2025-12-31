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

// Define table for client entries
$requestCreateTableClients = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber',"CREATE TABLE IF NOT EXISTS Clients (RequestID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, LastName VARCHAR(255), Firstname VARCHAR(255), Address VARCHAR(255), PhoneNumber VARCHAR(16))");
$requestCreateTableDates = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber',"CREATE TABLE IF NOT EXISTS Dates (DateID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, DateType VARCHAR(10), DatePeriod VARCHAR(100), DateTime DATE, ClientID INT)");

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
        if (isset($_SESSION['joe_logged']) and $_SESSION['joe_logged'] == true) {

            // Redirect to admin dashboard
            header('Location: /dashboard');
            exit();
        }

        // Load template for login page
        $template = $twig->load('/views/admin.html.twig');
        require __DIR__ . '/login.php';

        break;

    case '/dashboard' :

        // If no user is connected, or disconnected
        if (!isset($_SESSION['joe_logged']) or $_SESSION['joe_logged'] == false) {

            // Redirect to admin dashboard
            header('Location: /joe');
            exit();
        }

        // Admin dashboard page
        $template = $twig->load('/views/dashboard.html.twig');

        // render content to template
        echo $template->render();

        break;

    case '/logout' :

        require __DIR__ . '/logout.php';

        break;

    case (str_contains($currentUrl,'agenda')) :

        // Load template for login page
        require __DIR__ . '/agenda.php';

        break;

    default:

        // Page not found
        http_response_code(404);
        require __DIR__ . '/views/404.html.twig';
        break;

}
