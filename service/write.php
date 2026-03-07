<?php
/*************************************************************************
         (C) Copyright AudioLabs 2017 
**************************************************************************/

// Google Sheets configuration
$spreadsheet_id = "1d4l9trxa7RUsRC3UzYK3uckzHOKfLHH5suRcJlQk5YM";
$service_account_email = "tonecraft@tonecraft-489423.iam.gserviceaccount.com";
$private_key = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDIx/HC3RLy1p3M\nxbfQt7Lr5BGtQqlyZ0t+puSo5VQlI1ET7TDPa+g8AQ3+Otje8rcExy/nAGVwGIM1\nX/mXoA4amc0aNfgDrHf2YT9yDGu/A9Uljz5SdtPYMwP+qS/bJFX2FAFqBRayPVhl\nJLvmsE+e6xN1vn93+UQxvdZHW22j946h8SXADZMvVSgHl5/pmyVdWtXRX+CziTqW\nJsGXT/g6YOMIM7kplsiWaPZxKaT4b7NZXA4YJa1Iml92/dxT5CpODp9bsxZBbW8S\nhxOZap2I5ee5zerJLKYH60+IWLD39w2HJUnw56FJqiwa05R03Wal/TEDj/edMOZB\njG5BgTZhAgMBAAECggEAUCxzD/5KNjjX2zBqdYgdBI/WNSrE0d+IaGMJNJ4aaxf+\npKfWkUUD/43mQgnKlsAxr9FAAgyI9Ol83z/bQR4S87FNkrVRO1pPrsznUkm+bpB/\nuNdJqhE0B4Vbh4GUj/ui7bfQVr8AcJ0JYp34ACaQLKOu6hnu+X/KKNimq2jJT6ry\nEa9GFV09P0qWVgMI/80cU15HZ35ZX0/zw3QsO34to5ZlpJUgJJ4ot2JnaYx+K6R+\n3wXC7dG83IJzMFhF1Z9NiV5OJaKJRl9gNCoAOdRm93O48iQrXAILRZtf8I7Xl8gM\nCpbzyV/PFYWFRpKEXnCTJhF6oaElD7dx230HF8m8/wKBgQDrpdoPkSfcdJ7L8pWv\n4598Oh77gnozT+vO2sYmq9FYnavrZ3ddAWkC/EGsKl8o/oVJGn/LQyiqpAJEHe3i\nrIhZly5cE6EJPikL9NF5GNcnVQFFvd1E8iJTvVEc96hEt4ay5drha9Z7ddkmAKBv\nMzYhR1VQDZclhlWTMryW7PR9GwKBgQDaHzDe0Ya3S+Jtli9mriuh2tfZhx+yqkZX\nnEoiGdqspQHEOf+kGBz45om7cwhe0Eo/e1Bv5MuGPi3sCtVE9uSYitFW8xgVIg5C\nsSR85cTDFDdFOVuKNTsc/FoFDudPcP9Lv+ybMvjpjg/tMMtSIO/Q9UEX5Il8S1WR\nuXTzxjt+MwKBgFr1hOt3W0yUU2tj8vWWxkv8X1Mz9RlzYxFQjyYEMmWjTuYp5QAo\neDFnz622GU9Il4g1S1jZTow3jIxghR92+5ahbYqrJQDdVpi/4k99ECelfz09YXio\ngimSrQmiavhDYyIQ6WET8BFt1uj9WvAxc1Z7I2ooJMyeQs/zyKn1QxY7AoGAKTu+\nPFD1m47v1fGPMmT4gJdjOI7vshG4THWSGhIzIXHr/JFOP1IDoBXMsa/UREAx2QBR\nu2VQCCeW/MkreecGXcPYQQyhX9VZRsg/8pBo+svGiwKFyIG5lAgsaEph9cWRrVsx\nukEPhu9BGYCg3vy0+RZz4LVmPCXtniel8TNnKbsCgYEAzamAN2oE0zlG06DbrFXT\nYju5uhiJgOcHCWsBOfnBYcdHla1SnLhxYOvQ/4dd96yYhs29xREGz4chPTsj6fAt\n1XlgCbSyL6TpdcpKcvpLt8EKxQCTT0SB3o+ktUONf5tEjU2QyZ04ehaPaTWOkQcq\nonuJ8oAVmYcPacUO7TJKXkQ=\n-----END PRIVATE KEY-----\n";

function sanitize($string = '', $is_filename = FALSE)
{
    $string = preg_replace('/[^\w\-'. ($is_filename ? '~_\.' : ''). ']+/u', '-', $string);
    return strtolower(preg_replace('/--+/u', '-', $string));
}

function getGoogleAccessToken($service_account_email, $private_key) {
    $now = time();
    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claim = base64_encode(json_encode([
        'iss' => $service_account_email,
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]));
    $header = str_replace(['+', '/', '='], ['-', '_', ''], $header);
    $claim = str_replace(['+', '/', '='], ['-', '_', ''], $claim);
    $sig_input = $header . '.' . $claim;
    openssl_sign($sig_input, $signature, $private_key, 'SHA256');
    $sig = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $sig_input . '.' . $sig;

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function appendToSheet($spreadsheet_id, $access_token, $rows) {
    $range = 'Sheet1';
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$range}:append?valueInputOption=RAW&insertDataOption=INSERT_ROWS";
    $body = json_encode(['values' => $rows]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$sessionParam = null;
if(version_compare(PHP_VERSION, '8.0.0', '<') and get_magic_quotes_gpc()){
    $sessionParam = stripslashes($_POST['sessionJSON']);
} else {
    $sessionParam = $_POST['sessionJSON'];
}

$session = json_decode($sessionParam);

$length = isset($session->participant->name) ? count($session->participant->name) : 0;

// mushra
$write_mushra = false;
$mushraCsvData = array();

$header = array("session_test_id");
for($i = 0; $i < $length; $i++){
    array_push($header, $session->participant->name[$i]);
}
array_push($header, "session_uuid", "trial_id", "rating_stimulus", "rating_score", "rating_time", "rating_comment");
array_push($mushraCsvData, $header);

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
    global $spreadsheet_id, $service_account_email, $private_key;
    $access_token = getGoogleAccessToken($service_account_email, $private_key);
    if ($access_token) {
        appendToSheet($spreadsheet_id, $access_token, $mushraCsvData);
    } else {
        error_log("Failed to get Google access token");
    }
}
?>
