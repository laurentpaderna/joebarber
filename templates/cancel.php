<?php

// Set class to translate date
$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::LONG);
$formatter->setPattern('EEEE d MMMM yyyy');

$currentToken = NULL;

if (isset($_GET)) {
    // Get current token and date
    $currentToken = $_GET['token'];
    $currentID = urldecode($_GET['id']);

    // Gather data for the newsletter
    $requestAppointmentData = setDatabaseRequest("SELECT clients.clientID, clients.lastName, clients.firstName, clients.address, dates.datePeriod, dates.dateTime, appointments.dateID, appointments.clientID FROM clients, dates, appointments WHERE appointments.appointmentToken = '$currentToken' AND clients.clientID = appointments.clientID AND dates.dateID = '$currentID';");

    if ($requestAppointmentData['result'] != NULL and sizeof($requestAppointmentData['result'])) {

        // Get current data result
        $currentClientData = $requestAppointmentData['result'];

        // Send mail to admin
        foreach ($currentClientData as $currentClient) {

            // Get user email address
            $currentEmailDate = new DateTime($currentClient['dateTime']);

            $currentDateTime = $formatter->format($currentEmailDate);
            $searchPeriod = ['-',':'];
            $replacePeriod = [' à ','h'];
            $emailSubject = 'Annulation de prestation';
            $currentDatePeriod = str_replace($searchPeriod, $replacePeriod, $currentClient['datePeriod']);
            $emailAlt = $currentClient['lastName'] . " " . $currentClient['firstName'] . "a annulé sa coupe de cheveux prévue le " . $currentDateTime . " de " . $currentDatePeriod . " au " . $currentClient['address'];
            $desktopLink = 'https://joebarber.local.com/appointment?template=cancel&lastName=' . $currentClient['lastName'] . '&firstName=' . $currentClient['firstName'] . '&address=' . urlencode($currentClient['address']) . '&dateTime=' . urlencode($currentDateTime) . '&datePeriod=' . urlencode($currentDatePeriod);

            // Get email template
            $templateEmail = $GLOBALS['twig']->load('/views/emails/appointment_canceled.html.twig')->render(['newsletter' => true, 'lastName' => $currentClient['lastName'], 'firstName' => $currentClient['firstName'], 'address' => $currentClient['address'], 'dateTime' => $currentDateTime, 'datePeriod' => $currentDatePeriod, 'desktopLink' => $desktopLink]);

            // Send notification to user
            $resultMail = sendHtmlEmail(
                mail: $GLOBALS['mail'],
                toAddress: 'phpstorm202604@outlook.fr',
                toName: "Jo'barber",
                emailSubject: $emailSubject,
                emailBody: $templateEmail,
                emailAlt: $emailAlt
            );

            if (multiDimensionalArrayKeyExists($resultMail, 'error')) {
                $messageMailError = $resultMail['error'];
            }
            if (multiDimensionalArrayKeyExists($resultMail, 'success')) {
                $messageMailSuccess = $resultMail['success'];
            }

        }
    }

    // Delete appointment and date data
    $requestDeleteAppointmentData = setDatabaseRequest("DELETE appointments, dates FROM appointments INNER JOIN dates WHERE appointments.appointmentToken = '$currentToken' AND dates.dateID = '$currentID';");
}

// Render content to template
$template = $GLOBALS['twig']->load('/views/cancel.html.twig');
echo $template->render([]);