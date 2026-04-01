<?php

// Set error content
$emptyLogin = NULL;
$emptyPassword = NULL;
$wrongLogin = NULL;
$wrongPassword = NULL;
$errorClassLogin = NULL;
$errorClassPassword = NULL;
$messageErrorDatabaseConnection = NULL;

// Retrieve username and password from database
$userName = NULL;
$passWord = NULL;
$requestUser = setDatabaseRequest("SELECT identifier, password FROM users");
$hasRequestUserResult = sizeof($requestUser['result']);
if ($hasRequestUserResult > 0) {
    $userName = multiDimensionalArrayKeyExists($requestUser,'identifier') ? $requestUser['result'][0]['identifier'] : '';
    $passWord = multiDimensionalArrayKeyExists($requestUser,'password') ? $requestUser['result'][0]['password'] : '';
}

// Set error content
$messageErrorDatabaseConnection = $requestUser['error'];

// Set input content
$valueLogin = NULL;
$valuePassword = NULL;

// If login form is submitted
if(isset($_POST['joe_submit'])) {

    // Get current user credential
    $currentLogin = strlen(trim($_POST['joe_login']));
    $currentPassword = strlen(trim($_POST['joe_password']));

    // Reset all error messages
    $emptyLogin = '';
    $wrongLogin = '';
    $emptyPassword = '';
    $wrongPassword = '';
    $errorClassLogin = '';
    $errorClassPassword = '';

    // Display current credential
    $valueLogin = $_POST['joe_login'];
    $valuePassword = $_POST['joe_password'];

    // If login is empty
    $emptyLogin = ($currentLogin === 0) ? 'Veuillez saisir votre identifiant.' : '';

    // If login is wrong
    $wrongLogin = ($emptyLogin == "" and $valueLogin != $userName) ? 'Cet identifiant est inconnu.' : '';

    // If password is empty
    $emptyPassword = ($currentPassword === 0) ? 'Veuillez saisir votre mot de passe.' : '';

    // If password is wrong
    $wrongPassword = ($emptyPassword == '' and $valuePassword != $passWord) ? 'Ce mot de passe est inconnu.' : '';

    // Set error classes
    $errorClassLogin = ($emptyLogin != '' or $wrongLogin != '') ? ' has-error' : '';
    $errorClassPassword = ($emptyPassword != '' or $wrongPassword != '') ? ' has-error' : '';

    // If login and password are good
    if ($emptyLogin == '' and $wrongLogin == '' and $emptyPassword == '' and $wrongPassword == '') {
        $_SESSION['joe_logged'] = true;
        header('Location: /dashboard');
        exit();
    }
}

// render content to template
echo $template->render(array(
    'messageErrorDatabaseConnection' => $messageErrorDatabaseConnection,
    'emptyLogin' => $emptyLogin,
    'emptyPassword' => $emptyPassword,
    'wrongLogin' => $wrongLogin,
    'wrongPassword' => $wrongPassword,
    'valueLogin' => $valueLogin,
    'valuePassword' => $valuePassword,
    'errorClassLogin' => $errorClassLogin,
    'errorClassPassword' => $errorClassPassword
));