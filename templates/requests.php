<?php

require __DIR__.'/../vendor/autoload.php';

// Define return link
$returnLink = '/dashboard';

if (isset($_POST)) {
    $keys = array_keys($_POST);
    foreach ($keys as $key) {
        if (str_contains($key, 'accept')) {
            $appointmentID =  explode('-', $key)[1];
            $appointmentStatus = 'accepted';
        }
        if (str_contains($key, 'reject')) {
            $appointmentID =  explode('-', $key)[1];
            $appointmentStatus = 'rejected';
        }
        if (str_contains($key, 'cancel')) {
            $appointmentID =  explode('-', $key)[1];
            $appointmentStatus = 'pending';
        }
        $requestClientData = setDatabaseRequest("UPDATE appointments SET appointmentStatus = '$appointmentStatus' WHERE appointmentID = '$appointmentID'");
    }
}

// Set request content
$appointments = [];

// Request dates and clients
$requestClientData = setDatabaseRequest("SELECT dates.dateID, dates.dateTime, dates.datePeriod, appointments.appointmentID, appointments.dateID, appointments.clientID, appointments.appointmentStatus, clients.clientID, clients.lastName, clients.firstName, clients.address, clients.phoneNumber FROM dates, appointments, clients WHERE dates.dateID = appointments.dateID AND clients.clientID = appointments.clientID;");

if (sizeof($requestClientData['result'])) {
    $appointments = $requestClientData['result'];
}

// Render content to template
$template = $twig->load('/views/requests.html.twig');
echo $template->render([
    'returnLink' => $returnLink,
    'appointments' => $requestClientData['result'],
]);