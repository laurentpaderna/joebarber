<?php

/**
 * Execute a request on the database, returns result and errors
 *
 * @param string $query the query to execute on the database
 * @return array return an array with 2 sub arrays, result and error
 */
function setDatabaseRequest(string $query): array {
    $result = NULL;
    $error = NULL;
    // Retrieve username and password from database
    try {
        $connection = new PDO('mysql:host=joebarberdb;dbname=joebarberdb;port=3306', 'joe', 'barber');
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

/**
 * Check user connection status
 *
 * @param bool $connectionStatus status of the connection
 * @param string $url url to redirect to user if status is valid
 */
function checkConnection(bool $connectionStatus, string $url): void {
    if ($connectionStatus) {
        // Redirect to url
        header($url);
        exit();
    }
}

/**
 * Recursive search of a key in an array, simple or multidimensional
 *
 * @param array $array the array where to search
 * @param string $key the key to search
 */
function multiDimensionalArrayKeyExists(array $array, string $key): bool {
    // Check if key exists in current level
    if (array_key_exists($key, $array)) {
        return true;
    }

    // Go through in each array
    foreach ($array as $element) {
        if (is_array($element) && multiDimensionalArrayKeyExists($element, $key)) {
            return true;
        }
    }

    return false;
}

/**
 * Beautify dumped data from var_dump function
 *
 * @param mixed $data data to dump
 */
function beautifyVarDump($data): void {
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
}

/**
 * Beautify dumped data from var_dump function
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mail instance of the PHPMailer class
 * @param string $toAddress address used for the target parameter
 * @param string $toName name used for the target parameter
 * @param int $port number of SMTP port
 * @param string $emailSubject subject of the email
 * @param string $emailBody body of the email
 * @param string $emailAlt text version of the email
 */
$currentHost = $_SERVER['HTTP_HOST'];
function sendHtmlEmail(PHPMailer\PHPMailer\PHPMailer $mail, string $toAddress, string $toName, string $emailSubject, string $emailBody, string $emailAlt): array {
    try {
        $debugOutput = '';
        // Empty log file
        file_put_contents('mail.log', '');

        // SMTP parameters
        $mail->isSMTP();
        $mail->Host = $_ENV['APP_MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['APP_MAIL_USER'];
        $mail->Password = $_ENV['APP_MAIL_API'];
//        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = $_ENV['APP_MAIL_PORT'];
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function ($str, $level) use (&$debugOutput) {
            // Set new content
            file_put_contents('mail.log', $str, FILE_APPEND);
        };

        // Sending parameters
        $mail->setFrom($_ENV['APP_USER_USER_EMAIL'], $_ENV['APP_USER']);
        $mail->addAddress($toAddress, $toName);

        // CC and BCC
//        $mail->addCC('cc1@exemple.com', 'Elena');
//        $mail->addBCC('bcc1@exemple.com', 'Alex');

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $emailSubject;
        $mail->Body = $emailBody;
        $mail->AltBody = $emailAlt;

        if(!$mail->send()) {
            return array (
                'error' => "La notification n'a pu être envoyé: <br/>$mail->ErrorInfo <br> $debugOutput"
            );
        } else {
            return array (
                'success' => "La notification a pu être envoyée. <br> $debugOutput"
            );
        }

    } catch (Exception $e) {
        return array (
            'error' => "La notification n'a pu être envoyé: <br/>$mail->ErrorInfo <br> $debugOutput"
        );
    }
}
