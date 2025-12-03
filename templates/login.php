<?php

// Set error content
$emptyLogin = NULL;
$emptyPassword = NULL;
$wrongLogin = NULL;
$wrongPassword = NULL;
$errorClassLogin = NULL;
$errorClassPassword = NULL;
$databaseConnectionError = NULL;

// Retrieve username and password from database
$servername = "joebarber_db";
$databaseName = "joebarber_db";
$username = NULL;
$password = NULL;
try {
    $connection = new PDO("mysql:host=$servername;dbname=$databaseName", "joe", "barber");
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $request = $connection->prepare("SELECT identifier, password FROM users");
    $request->execute();
    $result = $request->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        $username = $row["identifier"];
        $password = $row["password"];
    }
} catch(PDOException $e) {
    $databaseConnectionError = $e->getMessage();
}
$connection = null;

// Set input content
$valueLogin = NULL;
$valuePassword = NULL;

// If login form is submitted
if(isset($_POST["joe_submit"])) {

    // Get current user credential
    $currentLogin = strlen(trim($_POST["joe_login"]));
    $currentPassword = strlen(trim($_POST["joe_password"]));

    // Reset all error messages
    $emptyLogin = "";
    $wrongLogin = "";
    $emptyPassword = "";
    $wrongPassword = "";
    $errorClassLogin = "";
    $errorClassPassword = "";

    // Display current credential
    $valueLogin = $_POST["joe_login"];
    $valuePassword = $_POST["joe_password"];

    // If login is empty
    $emptyLogin = ($currentLogin === 0) ? "Veuillez saisir votre identifiant." : "";

    // If login is wrong
    $wrongLogin = ($emptyLogin == "" and $valueLogin != $username) ? "Cet identifiant est inconnu." : "";

    // If password is empty
    $emptyPassword = ($currentPassword === 0) ? "Veuillez saisir votre mot de passe." : "";

    // If password is wrong
    $wrongPassword = ($emptyPassword == "" and $valuePassword != $password) ? "Ce mot de passe est inconnu." : "";

    // Set error classes
    $errorClassLogin = ($emptyLogin != "" or $wrongLogin != "") ? " has-error" : "";
    $errorClassPassword = ($emptyPassword != "" or $wrongPassword != "") ? " has-error" : "";

    // If login and password are good
    if ($emptyLogin == "" and $wrongLogin == "" and $emptyPassword == "" and $wrongPassword == "") {
        $_SESSION['joe_logged'] = true;
        header("Location: /dashboard");
        exit();
    }
}

// render content to template
echo $template->render(array(
    "databaseConnectionError" => $databaseConnectionError,
    "emptyLogin" => $emptyLogin,
    "emptyPassword" => $emptyPassword,
    "wrongLogin" => $wrongLogin,
    "wrongPassword" => $wrongPassword,
    "valueLogin" => $valueLogin,
    "valuePassword" => $valuePassword,
    "errorClassLogin" => $errorClassLogin,
    "errorClassPassword" => $errorClassPassword
));