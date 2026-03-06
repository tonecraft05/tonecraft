<?php
/*************************************************************************
         (C) Copyright AudioLabs 2017 
**************************************************************************/

// Email configuration
$email_to = "tonecraft05@gmail.com";
$email_from = "tonecraft05@gmail.com";
$email_password = "qpbx qakf uhjv cqtj";
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

$filepathPrefix = "C:/Aarush/webMUSHRA/results/".sanitize($string = $session->testId, $is_filename =FALSE)."/";
$filepathPostfix = ".csv";

if (!is_dir($filepathPrefix)) {
    mkdir($filepathPrefix);
}
$length = isset($session->participant->name) ? count($session->participant->name) : 0;

// mushra
$write_mushra = false;
$mushraCsvData = array();

$input = array("session_test_id");
for($i =0; $i < $length; $i++){
    array_push($input, $session->participant->name[$i]);
}
array_push($input, "session_uuid", "trial_id", "rating_stimulus", "rating_score", "rating_time", "rating_comment");
array_push($mushraCsvData, $input);

foreach ($session->trials as $trial) {
  if ($trial->type == "mushra") {
    $write_mushra = true;
    foreach ($trial->responses as $response) {
        $results = array($session->testId);
        for($i =0; $i < $length; $i++){
            array_push($results, $session->participant->response[$i]);
        }
        array_push($results, $session->uuid, $trial->id, $response->stimulus, $response->score, $response->time, $response->comment);
        array_push($mushraCsvData, $results);
    }
  }
}

if ($write_mushra) {
    $filename = $filepathPrefix."mushra".$filepathPostfix;
    $isFile = is_file($filename);
    $fp = fopen($filename, 'a');
    foreach ($mushraCsvData as $row) {
        if ($isFile) {
            $isFile = false;
        } else {
            fputcsv($fp, $row);
        }
    }
    fclose($fp);

    // Send email
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

// paired comparison
$write_pc = false;
$pcCsvData = array();
$input = array("session_test_id");
for($i =0; $i < $length; $i++){
    array_push($input, $session->participant->name[$i]);
}
array_push($input, "trial_id", "choice_reference", "choice_non_reference", "choice_answer", "choice_time", "choice_comment");
array_push($pcCsvData, $input);
foreach ($session->trials as $trial) {
  if ($trial->type == "paired_comparison") {
      foreach ($trial->responses as $response) {
          $write_pc = true;
          $results = array($session->testId);
          for($i =0; $i < $length; $i++){
              array_push($results, $session->participant->response[$i]);
          }
          array_push($results, $trial->id, $response->reference, $response->nonReference, $response->answer, $response->time, $response->comment);
          array_push($pcCsvData, $results);
      }
  }
}
if ($write_pc) {
    $filename = $filepathPrefix."paired_comparison".$filepathPostfix;
    $isFile = is_file($filename);
    $fp = fopen($filename, 'a');
    foreach ($pcCsvData as $row) {
        if ($isFile) { $isFile = false; } else { fputcsv($fp, $row); }
    }
    fclose($fp);
}

// bs1116
$write_bs1116 = false;
$bs1116CsvData = array();
$input = array("session_test_id");
for($i =0; $i < $length; $i++){
    array_push($input, $session->participant->name[$i]);
}
array_push($input, "trial_id", "rating_reference", "rating_non_reference", "rating_reference_score", "rating_non_reference_score", "rating_time", "choice_comment");
array_push($bs1116CsvData, $input);
foreach ($session->trials as $trial) {
  if ($trial->type == "bs1116") {
      foreach ($trial->responses as $response) {
          $write_bs1116 = true;
          $results = array($session->testId);
          for($i =0; $i < $length; $i++){
              array_push($results, $session->participant->response[$i]);
          }
          array_push($results, $trial->id, $response->reference, $response->nonReference, $response->referenceScore, $response->nonReferenceScore, $response->time, $response->comment);
          array_push($bs1116CsvData, $results);
      }
  }
}
if ($write_bs1116) {
    $filename = $filepathPrefix."bs1116".$filepathPostfix;
    $isFile = is_file($filename);
    $fp = fopen($filename, 'a');
    foreach ($bs1116CsvData as $row) {
        if ($isFile) { $isFile = false; } else { fputcsv($fp, $row); }
    }
    fclose($fp);
}

// lms
$write_lms = false;
$lmsCSVdata = array();
$input = array("session_test_id");
for($i =0; $i < $length; $i++){
    array_push($input, $session->participant->name[$i]);
}
array_push($input, "trial_id", "stimuli_rating", "stimuli", "rating_time");
array_push($lmsCSVdata, $input);
foreach($session->trials as $trial) {
    if($trial->type == "likert_multi_stimulus") {
        foreach ($trial->responses as $response) {
            $write_lms = true;
            $results = array($session->testId);
            for($i =0; $i < $length; $i++){
                array_push($results, $session->participant->response[$i]);
            }
            array_push($results, $trial->id, " $response->stimulusRating ", $response->stimulus, $response->time);
            array_push($lmsCSVdata, $results);
        }
    }
}
if($write_lms){
    $filename = $filepathPrefix."lms".$filepathPostfix;
    $isFile = is_file($filename);
    $fp = fopen($filename, 'a');
    foreach($lmsCSVdata as $row){
        if ($isFile){ $isFile = false; } else { fputcsv($fp,$row); }
    }
    fclose($fp);
}

// lss
$write_lss = false;
$lssCSVdata = array();
$input = array("session_test_id");
for($i =0; $i < $length; $i++){
    array_push($input, $session->participant->name[$i]);
}
array_push($input, "trial_id");
$ratingCount = isset($session->trials[0]->responses[0]->stimulusRating) ? count($session->trials[0]->responses[0]->stimulusRating) : 0;
if($ratingCount > 1) {
    for($i =0; $i < $ratingCount; $i++){
        array_push($input, "stimuli_rating" . ($i+1));
    }
} else {
    array_push($input, "stimuli_rating");
}
array_push($input, "stimuli", "rating_time");
array_push($lssCSVdata, $input);
foreach($session->trials as $trial) {
    if($trial->type == "likert_single_stimulus") {
        foreach ($trial->responses as $response) {
            $write_lss = true;
            $results = array($session->testId);
            for($i =0; $i < $length; $i++){
                array_push($results, $session->participant->response[$i]);
            }
            array_push($results, $trial->id);
            if(isset($response->stimulusRating)) {
                $results = array_merge($results, (array)$response->stimulusRating);
            }
            array_push($results, $response->stimulus, $response->time);
            array_push($lssCSVdata, $results);
        }
    }
}
if($write_lss){
    $filename = $filepathPrefix."lss".$filepathPostfix;
    $isFile = is_file($filename);
    $fp = fopen($filename, 'a');
    foreach($lssCSVdata as $row){
        if ($isFile){ $isFile = false; } else { fputcsv($fp,$row); }
    }
    fclose($fp);
}
?>
