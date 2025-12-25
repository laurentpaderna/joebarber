<?php

require __DIR__.'/../vendor/autoload.php';

use benhall14\phpCalendar\Calendar as Calendar;

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
$dateStartWeek = new DateTime('now');
if ($dateStartWeek->format('l') !== 'Monday') {
    // Get previous monday
    $dateStartWeek = new dateTime('@' . strtotime('last Monday'));
}
// $dateStartWeek = ((new DateTime())->modify('-1 day'));

// Set events for appointments
$eventsAppointment = [
    '2025-12-24_10:00_10:30',
    '2025-12-22_09:00_09:30',
];
$calendar->addDataAttribute($dateStartWeek->format('Y-m-d'), 'data', 'off');

// Set events for days off
$eventDaysOff = ['2025-12-16_08:00_08:30'];

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
        $inputCheckboxValue = $dateStartWeek->format('Y-m-d') . '_' . $eventStartDateData . '_' . $eventEndDateData;
        $inputCheckboxAvailable = 'available;' . $eventStartDateData .'_' . $eventEndDateData . ';' . $checkboxAvailableIdentifier . ';' . $inputCheckboxValue;
        $checkboxDayOffIdentifier = $contentId++;
        $inputCheckboxDayOff = 'off;' . $eventStartDateData .'_' . $eventEndDateData . ';' . $checkboxDayOffIdentifier . ';' . $inputCheckboxValue;
        $appointmentCard = 'appointment;' . $eventStartDateData .' - ' . $eventEndDateData . ';DOE;John;24 rue de knowhere DOECITY 65420;0548 96 52 63d';
        
        // Set event content from dates in array
        // Appointments
        $isAppointment = in_array( $inputCheckboxValue, $eventsAppointment);
        $eventContent = ($isAppointment) ? $appointmentCard : $inputCheckboxAvailable;

        // Days off
        $isOff = in_array( $inputCheckboxValue, $eventDaysOff);
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
    'calendar' => $calendar
));


