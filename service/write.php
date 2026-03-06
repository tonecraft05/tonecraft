<?php
/*************************************************************************
         (C) Copyright AudioLabs 2017 
**************************************************************************/

// Email configuration
$email_to = "tonecraft05@gmail.com";
$email_from = "tonecraft05@gmail.com";
$email_password = "gujk vrul yuhf bdqp";
$email_subject = "ToneCraft Results - New Participant";

// Load PHPMailer
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sanitize($string = '', $is_filename = FALSE)
{
 $string = preg_replace('/[^\w\-'. ($is_filename ? '~_\.' : ''). ']+/u', '-', $string);
 return strtolower(preg_replace('/--+/u', '-', $string));
}

function send_email_phpmailer($to, $from, $password, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $from;
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->setFrom($from, 'ToneCraft');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

$sessionParam = null;
if(version_compare(PHP_VERSION, '8.0.0', '<') and get_magic_quotes_gpc()){
    $sessionParam = stripslashes($_POST['sessionJSON']);
}else{
    $sessionParam = $_POST['sessionJSON'];
}

$session = json_decode($sessionParam);

// Use relative path - works on both local and Render
$resultsBase = __DIR__ . '/../results/';
$filepathPrefix = $resultsBase . sanitize($string = $session->testId, $is_filename = FALSE) . "/";
$filepathPostfix = ".csv";

if (!is_dir($resultsBase)) {
    mkdir($resultsBase, 0777, true);
}
if (!is_dir($filepathPrefix)) {
    mkdir($filepathPrefix, 0777, true);
}

$length = isset($session->participant->name) ? count($session->participant->name) : 0;

// mushra
$write_mushra = false;
$mushraCsvData = array();

$input = array("session_test_id");
for($i = 0; $i < $length; $i++){
    array_push($input, $session->participant->name[$i]);
}
array_push($input, "session_uuid", "trial_id", "rating_stimulus", "rating_score", "rating_time", "rating_comment");
array_push($mushraCsvData, $input);

foreach ($session->trials as $trial) {
  if ($trial->type == "mushra") {
    $write_mushra = true;
    foreach ($trial->responses as $response) {
        $results = array($session->testId);
        for($i = 0; $i < $length; $i++){
            array_push($results, $session->participant->response[$i]);
        }
        array_push($results, $session->uuid, $trial->id, $response->stimulus, $response->score, $response->time, $response->comment);
        array_push($mushraCsvData, $results);
    }
  }
}

if ($write_mushra) {
    // Try to save CSV (may fail on Render due to ephemeral filesystem)
    $filename = $filepathPrefix . "mushra" . $filepathPostfix;
    $isFile = is_file($filename);
    $fp = @fopen($filename, 'a');
    if ($fp) {
        foreach ($mushraCsvData as $row) {
            if ($isFile) {
                $isFile = false;
            } else {
                fputcsv($fp, $row);
            }
        }
        fclose($fp);
    }

    // Always send email regardless of file save
    global $email_to, $email_from, $email_password, $email_subject;
    $email_body = "New ToneCraft participant submission!\n\n";
    $email_body .= "UUID: " . $session->uuid . "\n";
    $email_body .= "Age: " . $session->participant->response[0] . "\n\n";
    $email_body .= "CSV Data:\n\n";
    foreach ($mushraCsvData as $row) {
        $email_body .= implode(",", $row) . "\n";
    }
    send_email_phpmailer($email_to, $email_from, $email_password, $email_subject, $email_body);
}
?>
