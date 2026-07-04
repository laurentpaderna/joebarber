<?php

require __DIR__.'/../vendor/autoload.php';

// Set class to translate date
$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::LONG);
$formatter->setPattern('EEEE d MMMM yyyy');

// Set error and state
$wrongFirstName = NULL;
$wrongLastName = NULL;
$wrongAddress = NULL;
$wrongEmail = NULL;
$wrongDate = NULL;
$wrongPhone = NULL;
$wrongPeriod = NULL;
$currentFirstName = NULL;
$currentLastName = NULL;
$currentAddress = NULL;
$currentEmail = NULL;
$currentDate = NULL;
$currentPhone = NULL;
$currentPeriod = NULL;
$errorMessage = NULL;
$formSubmitted = false;
$hasError = false;
$desktopLink = NULL;
$requestClientError = [];
$requestDateError = [];

if (isset($_POST['customer-submit'])) {

    // Get current user appointment data
    $currentFirstName = $_POST['customer-firstname'];
    $currentLastName = $_POST['customer-lastname'];
    $currentAddress = $_POST['customer-address'];
    $currentEmail = $_POST['customer-email'];
    $currentDate = $_POST['customer-date'];
    $currentPhone = $_POST['customer-phone'];
    $currentPeriod = $_POST['customer-period'];

    // check user input
    $checkFirstName = strlen(trim($currentFirstName));
    $checkLastName = strlen(trim($currentLastName));
    $checkAddress = strlen(trim($currentAddress));
    $checkEmail = strlen(trim($currentEmail));
    $checkDate = strlen($currentDate);
    $checkPhone = strlen($currentPhone);
    $checkPeriod = strlen($currentPeriod);

    // Reset error input and form state
    $wrongFirstName = '';
    $wrongLastName = '';
    $wrongAddress = '';
    $wrongEmail = '';
    $wrongDate = '';
    $wrongPeriod = '';
    $wrongPhone = '';
    $errorMessage = '';

    // Check if empty input
    $wrongFirstName = ($checkFirstName === 0) ? 'Votre nom' : '';
    $wrongLastName = ($checkLastName === 0) ? 'Votre prénom' : '';
    $wrongAddress = ($checkAddress === 0) ? 'Votre adresse' : '';
    $wrongEmail = ($checkEmail === 0) ? 'Votre adresse email' : '';
    $wrongDate = ($checkDate === 0 or validateDate($_POST['customer-date']) === false) ? 'La date de votre rendez-vous' : '';
    $wrongPeriod = ($checkPeriod === 0) ? 'La date de votre rendez-vous' : '';
    $wrongPhone = ($checkPhone === 0) ? 'Le numéro de téléphone' : '';

    // Check if appointment date exists in database
    $isDateExist = setDatabaseRequest("SELECT datePeriod, dateTime FROM dates WHERE DatePeriod = '$currentPeriod' AND DateTime = '$currentDate'");

    // Check if client exist
    $isClientExist = setDatabaseRequest("SELECT lastName, firstName, address, phoneNumber, email FROM clients WHERE lastName = '$currentLastName' AND firstName = '$currentFirstName' AND address = '$currentAddress' AND phoneNumber = '$currentPhone' AND email = '$currentEmail'");


    // If errors
    if (strlen($wrongFirstName) != 0 or strlen($wrongLastName) != 0 or strlen($wrongAddress) != 0 or strlen($wrongEmail) != 0 or strlen($wrongDate) != 0 or strlen($wrongPeriod) != 0 or strlen($wrongPhone) != 0 or validateDate($_POST['customer-date']) === false or count($isDateExist['result'])) {
        $hasError = true;

        // Set error message
        $errorMessage = count($isDateExist['result']) ? 'Veuillez sélectionner une autre créneau pour votre rendez-vous, celui-ci est indisponible.' : 'Veuillez corriger votre saisi sur les champs suivants:';
    }

    // If inputs are correct
    if ($wrongFirstName == '' and $wrongLastName == '' and $wrongAddress == '' and $wrongEmail == '' and $wrongDate == '' and $wrongPeriod == '' and !count($isDateExist['result'])) {

        // Set new client
        if (!count($isClientExist['result'])) {
            $requestInsertClient = setDatabaseRequest("INSERT INTO clients (lastName, firstName, address, phoneNumber, email) VALUES ('$currentLastName', '$currentFirstName', '$currentAddress', '$currentPhone', '$currentEmail')");

            //Check if client request is successful
            $requestClientError = $requestInsertClient['error'];
        }

        // Get last inserted client ID
        $requestClientID = setDatabaseRequest("SELECT clientID FROM clients WHERE lastName = '$currentLastName' AND firstName = '$currentFirstName' AND address = '$currentAddress' AND phoneNumber = '$currentPhone' AND email = '$currentEmail'");
        $currentClientID = $requestClientID['result'][0]['clientID'];

        // Set new date
        if (!count($isDateExist['result'])) {
            $requestInsertDate = setDatabaseRequest("INSERT INTO dates (dateType, datePeriod, dateTime, clientID) VALUES ('appointment','$currentPeriod','$currentDate','$currentClientID')");

            //Check if client request is successful
            $requestDateError = $requestInsertDate['error'];
        }

        // Get last inserted date ID
        $requestDateID = setDatabaseRequest("SELECT dateID FROM dates WHERE DatePeriod = '$currentPeriod' AND DateTime = '$currentDate'");
        $currentDateID = $requestDateID['result'][0]['dateID'];

        // Set new appointment
        $requestInsertAppointment = setDatabaseRequest("INSERT INTO appointments (dateID, clientID, appointmentStatus) VALUES ('$currentDateID','$currentClientID','pending')");

        //Check if client request is successful
        $requestAppointmentError = $requestInsertAppointment['error'];

        // If no request error
        if (empty($requestClientError) and empty($requestDateError) and  empty($requestAppointmentError)) {
            $formSubmitted = true;

            // Get user email appointment time
            $currentEmailDate = new DateTime($currentDate);

            // Get formatted time and period
            $currentDateTime = $formatter->format($currentEmailDate);
            $searchPeriod = ['-',':'];
            $replacePeriod = [' à ','h'];
            $currentDatePeriod = str_replace($searchPeriod, $replacePeriod, $currentPeriod);
            $desktopLink .= '&datePeriod=' . urlencode($currentDatePeriod) . '&dateTime=' . urlencode($currentDateTime) . '&address=' . urlencode($currentAddress);

            // Set template and image for email confirmation
            $templatePath = '/views/emails/appointment_accepted.html.twig';
            $imgSrc = 'https://iili.io/Cafee6J.md.png';

            // Get email template
            $templateEmail = $GLOBALS['twig']->load($templatePath)->render([
                'newsletter' => true,
                'imgSrc' => $imgSrc,
                'title' => "Ta demande a bien été prise en compte!",
                'introText' => "Ta demande pour une coupe de cheveu a été envoyée. Pour rappel:",
                'address' => $currentAddress,
                'dateTime' => $currentDateTime,
                'datePeriod' => $currentDatePeriod,
                'desktopLink' => $desktopLink,
                'hasCancel' => false,
                'hasAdmin' => false,
                'hasRequest' => true,
            ]);

            // Send notification to user
            $resultMail = sendHtmlEmail(
                mail: $GLOBALS['mail'],
                toAddress: $currentEmail,
                toName: $currentLastName,
                emailSubject: quoted_printable_encode('Votre demande de rendez-vous a été envoyée!'),
                emailBody: $templateEmail,
                emailAlt: "Votre demande de rendez-vous a été envoyée, pour le $currentDateTime de $currentDatePeriod"
            );

            // Empty all recipients
            $GLOBALS['mail']->clearAllRecipients();

            // Get email template
            $templateEmailAdmin = $GLOBALS['twig']->load($templatePath)->render([
                'newsletter' => true,
                'imgSrc' => $imgSrc,
                'title' => 'Un client réclame vos services!',
                'introText' => "$currentLastName $currentFirstName vous a adressé une demande de rendez-vous.",
                'address' => $currentAddress,
                'dateTime' => $currentDateTime,
                'datePeriod' => $currentDatePeriod,
                'desktopLink' => $desktopLink,
                'hasCancel' => false,
                'hasAdmin' => true,
                'adminLink' => 'https://joebarber.local.com/joe',
            ]);

            // Send notification to admin
            $resultMailAdmin = sendHtmlEmail(
                mail: $GLOBALS['mail'],
                toAddress: $_ENV['APP_USER_EMAIL'],
                toName: $_ENV['APP_USER'],
                emailSubject: quoted_printable_encode('Une demande de rendez-vous vous a été adressée!'),
                emailBody: $templateEmailAdmin,
                emailAlt: "Une demande de rendez-vous vous a été adressée, pour le $currentDateTime de $currentDatePeriod"
            );

            // Empty all recipients
            $GLOBALS['mail']->clearAllRecipients();

            // Empty user input
            $currentFirstName = NULL;
            $currentLastName = NULL;
            $currentAddress = NULL;
            $currentEmail = NULL;
            $currentDate = NULL;
            $currentPhone = NULL;
            $currentPeriod = NULL;
        }
    }
}

// Render content to template
$template = $GLOBALS['twig']->load('/views/main.html.twig');
echo $template->render([
    'formSubmitted' => $formSubmitted,
    'hasError' => $hasError,
    'errorMessage' => $errorMessage,
    'wrongFirstName' => $wrongFirstName,
    'wrongLastName' => $wrongLastName,
    'wrongAddress' => $wrongAddress,
    'wrongEmail' => $wrongEmail,
    'wrongDate' => $wrongDate,
    'wrongPeriod' => $wrongPeriod,
    'wrongPhone' => $wrongPhone,
    'currentFirstName' => $currentFirstName,
    'currentLastName' => $currentLastName,
    'currentAddress' => $currentAddress,
    'currentEmail' => $currentEmail,
    'currentDate' => $currentDate,
    'currentPhone' => $currentPhone,
    'currentPeriod' => $currentPeriod,
]);
