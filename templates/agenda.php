<?php

require __DIR__.'/../vendor/autoload.php';

$messageDatabaseConnectionError = NULL;
$messageNotification = NULL;

// Define return link
$returnLink = '/dashboard';

// Set list of event to define content on calendar
$events = [];
$event = [];
$eventsDaysOff = [];
$eventsAllDaysOff = [];

// Set starting hours and finishing hours
$hourStart = '8:00';
$hourEnd = '19:00';
$timestampStart = strtotime($hourStart);
$timestampEnd = strtotime($hourEnd);

// Set interval per hour
$hourInterval = 30;

// Record user selection
if (isset($_POST['submit_agenda_add'])) {
    // Remove useless element from array
    unset($_POST['submit_agenda_add']);

    foreach ($_POST as $post_data) {

        // Get data from form
        $dataDate = explode('_', $post_data)[0];
        $dataPeriod = explode('_', $post_data)[1];

        // Check if entry exist
        $isDateExist = setDatabaseRequest("SELECT datePeriod, dateTime FROM dates WHERE DatePeriod = '$dataPeriod' AND DateTime = '$dataDate'");

        if (!count($isDateExist['result'])) {
            // Insert data into tables
            $requestInsertDates = setDatabaseRequest("INSERT INTO dates (dateType, datePeriod, dateTime, clientID) VALUES ('off','$dataPeriod','$dataDate',NULL)");

            // Set error content
            $messageDatabaseConnectionError = $requestInsertDates['error'];

            // Set success message
            if (empty($messageDatabaseConnectionError)) {
                $messageNotification = 'Vos absences ont été validées.';
            }
        }
    }
}

// Delete user selection
if (isset($_POST['submit_agenda_delete'])) {
    // Remove useless element from array
    unset($_POST['submit_agenda_delete']);

    foreach ($_POST as $post_data) {

        // Get data from form
        $dataDate = explode('_', $post_data)[0];
        $dataPeriod = explode('_', $post_data)[1];

        // Delete data into tables
        $requestInsertDates = setDatabaseRequest("DELETE FROM dates WHERE datePeriod = '$dataPeriod' AND  dateTime = '$dataDate'");

        // Set error content
        $messageDatabaseConnectionError = $requestInsertDates["error"];

        // Set success message
        $isOffDisabled = setDatabaseRequest("SELECT * FROM dates WHERE datePeriod = '$dataPeriod' AND dateTime = '$dataDate'");
        if (!count($isOffDisabled['result'])) {
            $messageNotification = 'Vos absences ont été annulées.';
        }
    }
}

// Set monday of the agenda
$dateStartWeek = isset($_GET) ? new DateTime($_GET['date'], new DateTimeZone('Europe/Paris')) : new DateTime('now', new DateTimeZone('Europe/Paris'));

if ($dateStartWeek->format('l') !== 'Monday') {
    // Get previous monday
    $dateStartWeek = new dateTime('@' . strtotime('last Monday'));
}

// Get next sunday for database request
$nextSunday = new DateTime($dateStartWeek->format('Y-m-d'));
$nextSunday->modify('next Sunday');

// Get information from database
$dateStart = $dateStartWeek->format('Y-m-d');
$dateEnd = $nextSunday->format('Y-m-d');

$requestDates = setDatabaseRequest("SELECT * FROM dates WHERE dateTime BETWEEN '$dateStart' AND '$dateEnd'");

if (sizeof($requestDates['result'])) {
    foreach ($requestDates['result'] as $requestResult) {
        // Set event for day off
        if ($requestResult['dateType'] == 'off' ) {
            ($requestResult['datePeriod'] == '08:00-19:30') ? $eventsAllDaysOff[] = $requestResult['dateTime'] . '_' . $requestResult['datePeriod'] : $eventsDaysOff[] = $requestResult['dateTime'] . '_' . $requestResult['datePeriod'];
        }
    }
}

// Set events for appointments
$eventsAppointment = [];
$requestAppointmentsData = setDatabaseRequest("SELECT dates.dateID, dates.dateTime, dates.datePeriod, appointments.appointmentID, appointments.dateID, appointments.clientID, appointments.appointmentStatus, clients.clientID, clients.lastName, clients.firstName, clients.address, clients.phoneNumber, clients.email FROM dates, appointments, clients WHERE dates.dateID = appointments.dateID AND clients.clientID = appointments.clientID;");

if (sizeof($requestAppointmentsData['result'])) {
    foreach ($requestAppointmentsData['result'] as $requestResult) {
        // Set event for day off
        array_push($eventsAppointment, $requestResult);
    }
}

// Set ids for content
$contentId = 00;

// Set current date
$currentDate = new dateTime('now');

// Set content for every day of the week
for ($i = 1; $i <= 7; $i++) {

    // Set array for events of the day
    $dayName = $dateStartWeek->format('l');
    $event[$dayName] = [];
    $event[$dayName]['data'] = [];
    $isAllDayOff = false;

    // Check if current day if off from day name
    foreach ($eventsAllDaysOff as $allDayOff) {
        if (explode('_',$allDayOff)[0] == $dateStartWeek->format('Y-m-d')) {
            $isAllDayOff = true;
        }
    }

    // Set content for every hour
    while ($timestampStart <= $timestampEnd) {

        // Define dates of each event
        $eventStartDate = $dateStartWeek->format('Y-m-d');
        $eventPeriod = date('H:i',$timestampStart) . '-' . date('H:i', $timestampStart+(60*$hourInterval));
        $eventMask = $currentDate->format('Y-m-d') > $dateStartWeek->format('Y-m-d');

        // Define input id
        $checkboxAvailableIdentifier = $contentId++;

        // If event is day off
        $isOff = false;
        foreach ($eventsDaysOff as $dayOff) {
            if ($dayOff === $eventStartDate . '_' . $eventPeriod) {
                $isOff = true;
            }
        }

        // Appointments
        $isAppointment = false;
        $currentAppointment = [];
        foreach ($eventsAppointment as $appointmentData) {
            if ($appointmentData['appointmentStatus'] == 'accepted' && $appointmentData['dateTime'] == $eventStartDate && strpos($appointmentData['datePeriod'], $eventPeriod) !== false) {
                $isAppointment = true;
                $currentAppointment = $appointmentData;
            }
        }
        $type = ($isAppointment) ? 'appointment' : 'available';

        // If event is an appointment
        if (!$isAppointment) {
            $type = ($isOff) ? 'off' : 'available';
        }

        // Push data for events
        $eventCurrent = [
            'period' => $eventPeriod,
            'type' => $type,
            'id' => $contentId,
        ];

        // Push data for appointment
        if ($isAppointment) {
            $currentAppointment['period'] = $eventPeriod;
            $currentAppointment['type'] = $type;
            $currentAppointment['id'] = $contentId;
            $eventCurrent = $currentAppointment;
        }

        $event[$dayName]['startDate'] = $eventStartDate;
        $event[$dayName]['mask'] = $eventMask;
        $event[$dayName]['class'] = $isAllDayOff ? 'has-all-day-off' : '';
        array_push($event[$dayName]['data'], $eventCurrent);

        // Increment hour by 30 minutes
        $timestampStart = $timestampStart+(60*$hourInterval);
    }

    // Increment day by 1
    $dateStartWeek = $dateStartWeek->modify('+ 1day');

    // Reset value of hour for the next day
    $timestampStart = strtotime($hourStart);
}

// Render content to template
$template = $GLOBALS['twig']->load('/views/agenda.html.twig');
echo $template->render([
    'eventsAllDaysOff' => $eventsAllDaysOff,
    'event' => $event,
    'returnLink' => $returnLink,
    'nextMonday' => $dateStartWeek->format('Y-m-d'),
    'messageDatabaseConnectionError' => $messageDatabaseConnectionError,
    'messageNotification' => $messageNotification,
]);


