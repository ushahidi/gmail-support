<?php
use Ushahidi\Gmail\GmailController;

$apiVersion = '5';
$apiBase = 'api/v' . $apiVersion;

$router->group([
    'prefix' => $apiBase . '/config/data-provider',
], function () use ($router) {
    $router->get('gmail/intitalize', [GmailController::class, 'intitalize']);
    $router->post('gmail/authorize', [GmailController::class, 'authorize']);
    $router->post('gmail/unauthorize', [GmailController::class, 'unauthorize']);
});
