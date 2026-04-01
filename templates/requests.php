<?php

require __DIR__.'/../vendor/autoload.php';

// Set php mailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Set class to translate date
$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::LONG);
$formatter->setPattern('EEEE d MMMM yyyy');

$messageMailError = NULL;
$messageMailSuccess = NULL;
$appointmentID = NULL;
$templatePath = NULL;
$desktopLink = NULL;

// Define return link
$returnLink = '/dashboard';

$resultMail = [];

$GLOBALS['mail'] = new PHPMailer(true);
if (isset($_POST)) {

    $keys = array_keys($_POST);
    foreach ($keys as $key) {
        if (str_contains($key, 'accept')) {
            $appointmentID =  explode('-', $key)[1];
            $appointmentStatus = 'accepted';
            $templatePath = '/views/emails/appointment_accepted.html.twig';
            $desktopLink = 'https://joebarber.local.com/appointment?template=accept';
        }
        if (str_contains($key, 'reject')) {
            $appointmentID =  explode('-', $key)[1];
            $appointmentStatus = 'rejected';
            $templatePath = '/views/emails/appointment_refused.html.twig';
            $desktopLink = 'https://joebarber.local.com/appointment?template=accept';
        }
        if (str_contains($key, 'cancel')) {
            $appointmentID =  explode('-', $key)[1];
            $appointmentStatus = 'pending';
        }

        // Update appointment status
        $requestClientData = setDatabaseRequest("UPDATE appointments SET appointmentStatus = '$appointmentStatus' WHERE appointmentID = '$appointmentID'");

        // Request data from current update
        $requestUpdateData = setDatabaseRequest("SELECT dates.dateID, dates.dateTime, dates.datePeriod, appointments.appointmentID, appointments.dateID, appointments.clientID, clients.clientID, clients.email FROM dates, appointments, clients WHERE appointments.appointmentID = '$appointmentID' AND dates.dateID = appointments.dateID AND clients.clientID = appointments.clientID;");

        if ($requestUpdateData['result'] != NULL and sizeof($requestUpdateData['result']) and $appointmentStatus != 'pending') {

            // Get current data result
            $currentAppointment = $requestUpdateData['result'];

            // Send mail to user
            foreach ($currentAppointment as $appointment) {

                // Get user email address
                $currentEmailDate = new DateTime($appointment['dateTime']);

                // Get formatted time and period
                $currentDateTime = $formatter->format($currentEmailDate);
                $currentDatePeriod = str_replace('-',' à ',$appointment['datePeriod']);
                $desktopLink = $desktopLink . '&datePeriod=' . urlencode($currentDatePeriod) . '&dateTime=' . urlencode($currentDateTime);

                // Get email template
                $templateEmail = $GLOBALS['twig']->load($templatePath)->render(['newsletter'=>true,'dateTime' => $currentDateTime, 'datePeriod' => $currentDatePeriod, 'desktopLink' => $desktopLink]);

                    // Send notification to user
                $resultMail = sendHtmlEmail(
                    mail: $GLOBALS['mail'],
//                    fromAddress: 'jocoiff77@gmail.com',
                    fromAddress: 'hyauhn@gmail.com',
                    fromName: 'laurent',
                    toAddress: $appointment['email'],
//                    toAddress: 'phpstorm202604@outlook.fr',
//              toAddress: 'jocoiff77@gmail.com',
                    toName: 'destination',
                    emailSubject: 'Vous allez vous mettre sur votre 31 !',
                    emailBody: $templateEmail,
                    emailAlt: 'This is the test 6 email alt'
                );

                if (multiDimensionalArrayKeyExists($resultMail, 'error')) {
                    $messageMailError = $resultMail['error'];
                }
                if (multiDimensionalArrayKeyExists($resultMail, 'success')) {
                    $messageMailSuccess = $resultMail['success'];
                }

            }
        }
    }
}

// Set request content
$appointments = [];

// Request dates and clients
$requestClientData = setDatabaseRequest("SELECT dates.dateID, dates.dateTime, dates.datePeriod, appointments.appointmentID, appointments.dateID, appointments.clientID, appointments.appointmentStatus, clients.clientID, clients.lastName, clients.firstName, clients.address, clients.phoneNumber, clients.email FROM dates, appointments, clients WHERE dates.dateID = appointments.dateID AND clients.clientID = appointments.clientID;");
if ($requestClientData['result'] != NULL and sizeof($requestClientData['result'])) {
    $appointments = $requestClientData['result'];
}

// Render content to template
$template = $GLOBALS['twig']->load('/views/requests.html.twig');
echo $template->render([
    'returnLink' => $returnLink,
    'appointments' => $requestClientData['result'],
    'messageMailError' => $messageMailError,
    'messageMailSuccess' => $messageMailSuccess,
]);