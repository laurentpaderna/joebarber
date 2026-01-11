<?php

// Define table for client entries
$requestCreateTableClients = setDatabaseRequest('CREATE TABLE IF NOT EXISTS clients (clientID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, lastName VARCHAR(255), firstName VARCHAR(255), address VARCHAR(255), phoneNumber VARCHAR(16))');
$requestCreateTableDates = setDatabaseRequest('CREATE TABLE IF NOT EXISTS dates (dateID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, dateType VARCHAR(20), datePeriod VARCHAR(100), dateTime DATE)');
$requestCreateTableAppointments = setDatabaseRequest('CREATE TABLE IF NOT EXISTS appointments (appointmentID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, dateID INT, clientID INT, appointmentStatus VARCHAR(100))');

function setDatabaseRequest(string $query)  {
    $result = NULL;
    $error = NULL;
    // Retrieve username and password from database
    try {
        $connection = new PDO('mysql:host=joebarber_db;dbname=joebarber_db', 'joe', 'barber');
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $request = $connection->prepare($query);
        $request->execute();
        $result = $request->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
    $connection = NULL;

    // Return set of data results
    return array(
        'result' => $result,
        'error' => $error
    );
}

// Cleanup outdated database entries
// Clear dates
$currentDate = (new dateTime('now'))->format('Y-m-d');
setDatabaseRequest("DELETE FROM dates WHERE dateTime < '$currentDate'");

// Clear appointments where dateID does not exist
setDatabaseRequest('DELETE FROM appointments a WHERE NOT EXISTS ( SELECT 1 FROM dates d WHERE d.dateID = a.dateID )');

// Clear client where ID do not exist in appointments
setDatabaseRequest('DELETE FROM clients c WHERE NOT EXISTS ( SELECT 1 FROM appointments a WHERE c.clientID = a.clientID )');