<?php
declare(strict_types=1);

$pages = [
    'index' => 'index.php',
    'about' => 'about.php',
    'services' => 'services.php',
    'service' => 'service.php',
    'contact' => 'contact.php',
    'messages' => 'messages.php',
];
$page = $_GET['page'] ?? 'index';

if (!is_string($page) || !isset($pages[$page])) {
    http_response_code(404);
    exit('Page not found.');
}

$pageFile = $pages[$page];
$_SERVER['PHP_SELF'] = '/' . $pageFile;
require dirname(__DIR__) . '/' . $pageFile;
