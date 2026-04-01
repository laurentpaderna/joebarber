<?php

require __DIR__.'/../vendor/autoload.php';

$dateTime = NULL;
$datePeriod = NULL;
$templateType = NULL;
$templatePath = NULL;

// Get current data from $_GET
if (isset($_GET)) {
    $dateTime = urldecode($_GET['dateTime']);
    $datePeriod = urldecode($_GET['datePeriod']);
    $templateType = $_GET['template'];
}

// Get template from type
if ($templateType != NULL) {
    switch ($templateType) {

        case 'accept' :

            // Email template for accepted appointment
            $templatePath = '/views/emails/appointment_accepted.html.twig';
            break;

        default:

            // Email
            $templatePath = '/views/emails/appointment_refused.html.twig';
            break;
    }
}

// Render content to template
$template = $GLOBALS['twig']->load($templatePath);
echo $template->render([
    'dateTime' => $dateTime,
    'datePeriod' => $datePeriod,
    'newsletter' => false
]);