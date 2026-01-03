<?php

require __DIR__.'/../vendor/autoload.php';

//use benhall14\phpCalendar\Calendar as Calendar;

$databaseConnectionError = NULL;

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
        $isDateExist = setDatabaseRequest('joebarber_db','joebarber_db', 'joe', 'barber', "SELECT DatePeriod, DateTime FROM Dates WHERE DatePeriod = '$dataPeriod' AND DateTime = '$dataDate'");

        if (!count($isDateExist['result'])) {
            // Insert data into tables
            $requestInsertDates = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber',"INSERT INTO Dates (DateType, DatePeriod, DateTime, ClientID) VALUES ('off','$dataPeriod','$dataDate',NULL)");

            // Set error content
            $databaseConnectionError = $requestInsertDates['error'];
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

        // Insert data into tables
        $requestInsertDates = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber','DELETE FROM Dates WHERE DatePeriod = "' . $dataPeriod . '" AND  DateTime = "' . $dataDate . '"');

        // Set error content
        $databaseConnectionError = $requestInsertDates["error"];
    }
}

// Set monday of the agenda
$dateStartWeek = isset($_GET) ? new DateTime($_GET['date']) : new DateTime('now');

if ($dateStartWeek->format('l') !== 'Monday') {
    // Get previous monday
    $dateStartWeek = new dateTime('@' . strtotime('last Monday'));
}

// Get next sunday for database request
$nextSunday = new dateTime($dateStartWeek->format('Y-m-d'));
$nextSunday->modify('next Sunday');

// Get information from database
$dateStart = $dateStartWeek->format('Y-m-d');
$dateEnd = $nextSunday->format('Y-m-d');

$requestDates = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber',"SELECT * FROM Dates WHERE DateTime BETWEEN '$dateStart' AND '$dateEnd'");

foreach ($requestDates['result'] as $requestResult) {
    // Set event for day off
    if ($requestResult['DateType'] == 'off' ) {
        ($requestResult['DatePeriod'] == '08:00-19:30') ? $eventsAllDaysOff[] = $requestResult['DateTime'] . '_' . $requestResult['DatePeriod'] : $eventsDaysOff[] = $requestResult['DateTime'] . '_' . $requestResult['DatePeriod'];
    }
}

// Set events for appointments
$eventsAppointment = [
    '2025-12-24_10:00_10:30',
    '2025-12-22_09:00_09:30',
];
//$calendar->addDataAttribute($dateStartWeek->format('Y-m-d'), 'data', 'off');

// Set ids for content
$contentId = 00;

// Set current date
$currentDate = new dateTime('now');

// Set class to translate date
$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::LONG);

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

        // Appointments
        $isAppointment = in_array( $eventStartDate, $eventsAppointment);
        $type = ($isAppointment) ? 'appointment' : 'available';

        // If event is day off
        $isOff = false;
        foreach ($eventsDaysOff as $dayOff) {
            if ($dayOff === $eventStartDate . '_' . $eventPeriod) {
                $isOff = true;
            }
        }
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
        //if ($isAppointment) {
        $eventCurrent = [
            'period' => $eventPeriod,
            'type' => $type,
            'id' => $contentId,
            'firstName' => 'doe',
            'lastName' => 'John',
            'address' => '24 rue de knowhere DOECITY 65420',
            'phoneNumber' => '0548 96 52 63',
        ];
        //}

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
$template = $twig->load('/views/agenda.html.twig');
echo $template->render([
    'eventsAllDaysOff' => $eventsAllDaysOff,
    'event' => $event,
    'returnLink' => $returnLink,
    'nextMonday' => $dateStartWeek->format('Y-m-d'),
    'databaseConnectionError' => $databaseConnectionError
]);


