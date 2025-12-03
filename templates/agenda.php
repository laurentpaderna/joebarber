<?php

require __DIR__."/../vendor/autoload.php";

use benhall14\phpCalendar\Calendar as Calendar;

if (isset($_POST)) {
    echo "<pre>";
    var_dump($_POST);
    echo "</pre>";
}

// Define new calendar
$calendar = new Calendar();

// Set style for agenda
$calendar->addTableClasses('is-agenda');

// Set list of event to define content on calendar
$events = array();

// Set starting hours and finishing hours
$hourStart = '8:00';
$hourEnd = '19:00';
$timestampStart = strtotime($hourStart);
$timestampEnd = strtotime($hourEnd);

// Set interval per hour
$hourInterval = 30;

// Set starting day of the agenda
$dateStartWeek = (new DateTime())->modify('next monday');

// Set events for appointments
$eventsAppointment = [
    '2025-12-10_10:00_10:30',
    '2025-12-09_09:00_09:30',
];

// Set events for days off
$eventDaysOff = ['2025-12-13_08:00_08:30'];

// Set ids for content
$contentId = 00;

// Set content for every day of the week
for ($i = 1; $i <= 7; $i++) {

    // Set content for every hour
    while ($timestampStart <= $timestampEnd) {

        // Define dates of each event
        // Add to 1 minute to start date to place event in the calendar
        $eventStartDate = $dateStartWeek->format('Y-m-d') . ' ' . date('H:i', $timestampStart+(60*1.5));
        $eventStartDateData = date('H:i',$timestampStart);
        $eventEndDateData = date('H:i', $timestampStart+(60*$hourInterval));
        $eventEndDate = $dateStartWeek->format('Y-m-d') . ' ' . $eventEndDateData;

        // Define input content
        $checkboxAvailableIdentifier = $contentId++;
        $inputCheckboxValue = $dateStartWeek->format('Y-m-d') . '_' . $eventStartDateData . '_' . $eventEndDateData;
        $inputCheckboxAvailable = '<input id="' . $checkboxAvailableIdentifier . '" type="checkbox" name="agenda_' . $checkboxAvailableIdentifier . '" value="' . $inputCheckboxValue . '"/><label class="is-agenda-card" for="' . $checkboxAvailableIdentifier . '"><span class="is-agenda-card-label"><span class="is-agenda-title">' . $eventStartDateData .' - ' . $eventEndDateData . '</span></span></label>';
        $checkboxDayOffIdentifier = $contentId++;
        $inputCheckboxDayOff = '<input id="' . $checkboxDayOffIdentifier . '" type="checkbox" name="agenda_' . $checkboxDayOffIdentifier . '" value="' . $inputCheckboxValue . '"/><label class="is-agenda-card has-day-off" for="' . $checkboxDayOffIdentifier . '"><span class="is-agenda-card-label"><span class="is-agenda-title">' . $eventStartDateData .' - ' . $eventEndDateData . '</span></span></label>';
        $appointmentCard = '<div class="is-agenda-card has-appointment"><span class="is-agenda-card-label"><span class="is-agenda-title">' . $eventStartDateData .' - ' . $eventEndDateData . '</span></span><ul class="is-agenda-data-list"><li>DOE</li><li>John</li><li>24 rue de knowhere DOECITY 65420</li><li>0548 96 52 63</li></ul></div>';
        
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
            'summary' => $eventContent
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
$template = $twig->load("/views/agenda.html.twig");
echo $template->render(array(
    "agenda" => $agenda
));


