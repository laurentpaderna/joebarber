<?php

$lastName = NULL;
$firstName = NULL;
$address = NULL;
$dateTime = NULL;
$datePeriod = NULL;
$templateType = NULL;
$templatePath = NULL;

// Get current data from $_GET
if (isset($_GET)) {
    $lastName = $_GET['lastName'] ?? '';
    $firstName = $_GET['firstName'] ?? '';
    $address = urldecode($_GET['address']) ?? '';
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

        case 'reject' :

            // Email template for accepted appointment
            $templatePath = '/views/emails/appointment_refused.html.twig';
            break;

        case 'cancel' :

            // Email template for accepted appointment
            $templatePath = '/views/emails/appointment_canceled.html.twig';
            break;
    }
}

// Render content to template
$template = $GLOBALS['twig']->load($templatePath);
echo $template->render([
    'newsletter' => false,
    'dateTime' => $dateTime,
    'datePeriod' => $datePeriod,
    'lastName' => $lastName,
    'firstName' => $firstName,
    'address' => $address
]);