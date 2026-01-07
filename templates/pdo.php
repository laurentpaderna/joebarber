<?php

// Define table for client entries
$requestCreateTableClients = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber','CREATE TABLE IF NOT EXISTS clients (clientID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, lastName VARCHAR(255), firstName VARCHAR(255), address VARCHAR(255), phoneNumber VARCHAR(16))');
$requestCreateTableDates = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber','CREATE TABLE IF NOT EXISTS dates (dateID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, dateType VARCHAR(20), datePeriod VARCHAR(100), dateTime DATE, ClientID INT)');
$requestCreateTableAppointments = setDatabaseRequest('joebarber_db','joebarber_db', 'joe','barber','CREATE TABLE IF NOT EXISTS appointments (appointmentID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, dateID INT, clientID INT, appointmentStatus VARCHAR(100))');

function setDatabaseRequest(string $serverName,string $databaseName,string $userName,string $passWord,string $query)  {
    $result = NULL;
    $error = NULL;
    // Retrieve username and password from database
    try {
        $connection = new PDO("mysql:host=$serverName;dbname=$databaseName", $userName, $passWord);
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