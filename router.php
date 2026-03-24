<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Handle result writing - must come before static file check
if (strpos($uri, '/service/write.php') !== false) {
    include __DIR__ . '/service/write.php';
    exit;
}

// Handle config requests without .yaml extension
if (preg_match('/^\/configs\/([^.]+)$/', $uri, $matches)) {
    $configFile = __DIR__ . '/configs/' . $matches[1] . '.yaml';
    if (file_exists($configFile)) {
        header('Content-Type: text/yaml');
        readfile($configFile);
        exit;
    }
}

// Serve static files
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Default - serve index.html
include __DIR__ . '/index.html';
?>
