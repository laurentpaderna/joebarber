<?php

// Set current date
$currentDate = (new DateTime('now'))->format('Y-m-d');

// Gather data on dates and appointments
$requestAppointmentData = setDatabaseRequest("SELECT count(*) FROM appointments WHERE appointments.appointmentStatus = 'pending'");
$requestDatesData = setDatabaseRequest("SELECT count(*) FROM dates WHERE dates.dateTime = '$currentDate' AND dates.dateType = 'appointment'");
$requestOffData = setDatabaseRequest("SELECT count(*) FROM dates WHERE dates.dateTime = '$currentDate' AND dates.datePeriod = '08:30-19:30'");

// Render content to template
$template = $GLOBALS['twig']->load('/views/dashboard.html.twig');
echo $template->render([
    'appointmentAmount' => $requestAppointmentData['result'][0]['count(*)'],
    'dateAmount' => $requestDatesData['result'][0]['count(*)'],
    'offAmount' => $requestOffData['result'][0]['count(*)'],
]);