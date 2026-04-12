<?php

// Set class to translate date
$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::LONG);
$formatter->setPattern('EEEE d MMMM yyyy');

$messageMailError = NULL;
$messageMailSuccess = NULL;
$appointmentID = NULL;
$templatePath = NULL;
$desktopLink = NULL;
$emailSubject = NULL;
$emailAlt = NULL;
$dateID = NULL;
$messageNotification = NULL;
$messageDatabaseConnectionError = NULL;
$currentAppointmentStatus = NULL;

// Define return link
$returnLink = '/dashboard';

$resultMail = [];

if (isset($_POST)) {

    $keys = array_keys($_POST);
    foreach ($keys as $key) {
        if (str_contains($key, 'accept')) {
            $appointmentStatus = 'accepted';
            $currentAppointmentStatus = 'validée';
            $emailSubject = 'Vous allez vous mettre sur votre 31 !';
            $emailAlt = "Nous te remercions d'avoir réservé ta coupe de cheveux. Nous te confirmons ton rendez-vous le";
            $templatePath = '/views/emails/appointment_accepted.html.twig';
            $desktopLink = 'https://joebarber.local.com/appointment?template=accept';
        }
        if (str_contains($key, 'reject')) {
            $appointmentStatus = 'rejected';
            $currentAppointmentStatus = 'refusée';
            $emailSubject = 'Ce sera pour la prochaine fois...';
            $emailAlt = "Votre demande de rendez-vous a été refusée, pour le";
            $templatePath = '/views/emails/appointment_refused.html.twig';
            $desktopLink = 'https://joebarber.local.com/appointment?template=reject';
        }
        if (str_contains($key, 'cancel')) {
            $appointmentStatus = 'pending';
        }
        if (str_contains($key, 'delete')) {
            $appointmentStatus = 'pending';
            $dateID = explode('-', $key)[2];
        }
        if (str_contains($key, 'cancel') or str_contains($key, 'delete')) {
            $currentAppointmentStatus = 'modifiée';
        }
        $appointmentID = explode('-', $key)[1];

        // Generate token for cancel appointment
        $currentToken = bin2hex(random_bytes(16));

        // If the appointment is deleted
        if (str_contains($key, 'delete')) {
            // Delete lines from dates and appointment
            $requestDeleteAppointmentData = setDatabaseRequest("DELETE appointments, dates FROM appointments INNER JOIN dates WHERE appointments.appointmentID = '$appointmentID' AND dates.dateID = '$dateID';");

            // Set error content
            $messageDatabaseConnectionError = $requestDeleteAppointmentData['error'];

            // Set success message
            if (empty($messageDatabaseConnectionError)) {
                $messageNotification = 'La demande a été supprimée.';
            }
        } else {

            // reset token if the status is cancel
            if (str_contains($key, 'cancel')) {
                $currentToken = NULL;
            }

            // Update appointment status
            $requestUpdateAppointmentData = setDatabaseRequest("UPDATE appointments SET appointmentStatus = '$appointmentStatus', appointmentToken = '$currentToken' WHERE appointmentID = '$appointmentID'");

            // Set error content
            $messageDatabaseConnectionError = $requestUpdateAppointmentData['error'];

            // Set success message
            if (empty($messageDatabaseConnectionError)) {
                // Set appointment status for success message
                $messageNotification = "La demande a été $currentAppointmentStatus.";
            }
        }

        // Request data from current update
        $requestUpdateData = setDatabaseRequest("SELECT dates.dateID, dates.dateTime, dates.datePeriod, appointments.appointmentID, appointments.dateID, appointments.clientID, clients.clientID, clients.email, clients.lastName FROM dates, appointments, clients WHERE appointments.appointmentID = '$appointmentID' AND dates.dateID = appointments.dateID AND clients.clientID = appointments.clientID;");

        if ($requestUpdateData['result'] != NULL and sizeof($requestUpdateData['result']) and $appointmentStatus != 'pending') {

            // Get current data result
            $currentAppointment = $requestUpdateData['result'];

            // Send mail to user
            foreach ($currentAppointment as $appointment) {

                // Get user email address
                $currentEmailDate = new DateTime($appointment['dateTime']);

                // Get formatted time and period
                $currentDateTime = $formatter->format($currentEmailDate);
                $searchPeriod = ['-',':'];
                $replacePeriod = [' à ','h'];
                $currentDatePeriod = str_replace($searchPeriod, $replacePeriod, $appointment['datePeriod']);
                $desktopLink .= '&datePeriod=' . urlencode($currentDatePeriod) . '&dateTime=' . urlencode($currentDateTime);
                $linkCancel = 'https://joebarber.local.com/cancel?token=' . $currentToken . '&id=' . $appointment['dateID'];

                // Get email template
                $templateEmail = $GLOBALS['twig']->load($templatePath)->render(['newsletter' => true, 'dateTime' => $currentDateTime, 'datePeriod' => $currentDatePeriod, 'desktopLink' => $desktopLink, 'linkCancel' => $linkCancel]);

                // Send notification to user
                $resultMail = sendHtmlEmail(
                    mail: $GLOBALS['mail'],
                    toAddress: $appointment['email'],
                    toName: $appointment['lastName'],
                    emailSubject: $emailSubject,
                    emailBody: $templateEmail,
                    emailAlt: "$emailAlt $currentDateTime de $currentDatePeriod"
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
$requestAppointmentsData = setDatabaseRequest("SELECT dates.dateID, dates.dateTime, dates.datePeriod, appointments.appointmentID, appointments.dateID, appointments.clientID, appointments.appointmentStatus, clients.clientID, clients.lastName, clients.firstName, clients.address, clients.phoneNumber, clients.email FROM dates, appointments, clients WHERE dates.dateID = appointments.dateID AND clients.clientID = appointments.clientID ORDER BY dates.dateTime ASC;");
if ($requestAppointmentsData['result'] != NULL and sizeof($requestAppointmentsData['result'])) {
    $appointments = $requestAppointmentsData['result'];
}

// Render content to template
$template = $GLOBALS['twig']->load('/views/requests.html.twig');
echo $template->render([
    'returnLink' => $returnLink,
    'appointments' => $appointments,
    'messageMailError' => $messageMailError,
    'messageMailSuccess' => $messageMailSuccess,
    'messageNotification' => $messageNotification,
    'messageDatabaseConnectionError' => $messageDatabaseConnectionError,
]);