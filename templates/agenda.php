<?php

require __DIR__.'/../vendor/autoload.php';

use benhall14\phpCalendar\Calendar as Calendar;

$databaseConnectionError = NULL;
$eventDaysOff = [];

// Define new calendar
$calendar = new Calendar();

// Define return link
$returnLink = '/dashboard';

// Set style for agenda
$calendar->addTableClasses('is-agenda');
$calendar->hideMondays();

// Set list of event to define content on calendar
$events = array();

// Set starting hours and finishing hours
$hourStart = '8:00';
$hourEnd = '19:00';
$timestampStart = strtotime($hourStart);
$timestampEnd = strtotime($hourEnd);

// Set interval per hour
$hourInterval = 30;

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
$requestDates = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber','SELECT * FROM Dates WHERE DateTime BETWEEN "' . $dateStart . '" AND "' . $dateEnd .'"');
foreach ($requestDates['result'] as $requestResult) {
    // Set event for day off
    if ($requestResult['DateType'] == 'off') {
        $eventDaysOff[] = $requestResult['DateTime'] . '_' . $requestResult['DatePeriod'];
    }
}

// Record user selection
if (isset($_POST['submit_agenda_add'])) {
    // Remove useless element from array
    unset($_POST['submit_agenda_add']);

    foreach ($_POST as $post_data) {

        // Get data from form
        $dataDate = explode('_', $post_data)[0];
        $dataPeriod = explode('_', $post_data)[1];

        // Check if entry exist
        $isDateExist = setDatabaseRequest('joebarber_db','joebarber_db', 'joe', 'barber', 'SELECT DatePeriod, DateTime FROM Dates WHERE DatePeriod = "' . $dataPeriod . '" AND  DateTime = "' . $dataDate . '"');

        if (!count($isDateExist['result'])) {
            // Insert data into tables
            $requestInsertDates = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber','INSERT INTO Dates (DateType, DatePeriod, DateTime, ClientID) VALUES ("off","' . $dataPeriod . '","' . $dataDate . '",NULL)');

            // Set error content
            $databaseConnectionError = $requestInsertDates["error"];
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

// Set events for appointments
$eventsAppointment = [
    '2025-12-24_10:00_10:30',
    '2025-12-22_09:00_09:30',
];
$calendar->addDataAttribute($dateStartWeek->format('Y-m-d'), 'data', 'off');

// Set ids for content
$contentId = 00;

$currentDate = new dateTime('now');

// Set content for every day of the week
for ($i = 1; $i <= 7; $i++) {

    // Set content for every hour
    while ($timestampStart <= $timestampEnd) {

        // Define dates of each event
        $eventStartDate = $dateStartWeek->format('Y-m-d') . ' ' . date('H:i', $timestampStart);
        $eventStartDateData = date('H:i',$timestampStart);
        $eventEndDateData = date('H:i', $timestampStart+(60*$hourInterval));
        $eventEndDate = $dateStartWeek->format('Y-m-d') . ' ' . $eventEndDateData;
        $eventMask = $currentDate->format('Y-m-d') > $dateStartWeek->format('Y-m-d');

        // Define input content
        $checkboxAvailableIdentifier = $contentId++;
        $inputCheckboxValue = $dateStartWeek->format('Y-m-d') . '_' . $eventStartDateData . '-' . $eventEndDateData;
        $inputCheckboxAvailable = 'available;' . $eventStartDateData .'_' . $eventEndDateData . ';' . $checkboxAvailableIdentifier . ';' . $inputCheckboxValue;
        $checkboxDayOffIdentifier = $contentId++;
        $inputCheckboxDayOff = 'off;' . $eventStartDateData .'_' . $eventEndDateData . ';' . $checkboxDayOffIdentifier . ';' . $inputCheckboxValue;
        $appointmentCard = 'appointment;' . $eventStartDateData .' - ' . $eventEndDateData . ';DOE;John;24 rue de knowhere DOECITY 65420;0548 96 52 63d';
        
        // Set event content from dates in array
        // Appointments
        $isAppointment = in_array( $inputCheckboxValue, $eventsAppointment);
        $eventContent = ($isAppointment) ? $appointmentCard : $inputCheckboxAvailable;

        // Days off
        $isOff = false;
        foreach ($eventDaysOff as $dayOff) {
//            echo 'off:' . $dayOff . ' input:' . $inputCheckboxValue . '<br> result:';
            if ($dayOff === $inputCheckboxValue) {
                $isOff = true;
            }
        }
        if (!$isAppointment) {
            $eventContent = ($isOff) ? $inputCheckboxDayOff : $inputCheckboxAvailable;
        }

        // Define content of event
        $events[] = array(
            'start' => $eventStartDate,
            'end' => $eventEndDate,
            'summary' => $eventContent,
            'mask' => $eventMask
        );

        // Increment hour by 30 minutes
        $timestampStart = $timestampStart+(60*$hourInterval);
    }

    // Increment day by 1
    $dateStartWeek = $dateStartWeek->modify('+ 1day');

    // Reset value of hour for the next day
    $timestampStart = strtotime($hourStart);
}

// Set calendar, with French language, using view per week, set hour limit
$calendar->useMondayStartingDate();
$agenda = $calendar
    ->addEvents($events)
    ->setLocale('fr')
    ->useWeekView()
    ->setTimeFormat($hourStart, $hourEnd, 30)
    ->draw(date('Y-m-d',strtotime('next monday')));

// Render content to template
$template = $twig->load('/views/agenda.html.twig');
echo $template->render(array(
    'returnLink' => $returnLink,
    'calendar' => $calendar,
    'nextMonday' => $dateStartWeek->format('Y-m-d'),
    'databaseConnectionError' => $databaseConnectionError
));


