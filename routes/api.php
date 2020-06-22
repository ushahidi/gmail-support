<?php
use Ushahidi\Gmail\GmailController;

$apiVersion = '5';
$apiBase = 'api/v' . $apiVersion;

$router->group([
    'prefix' => $apiBase . '/config/data-provider',
], function () use ($router) {
    $router->get('gmail/initialize', 'GmailController@initialize');
    $router->post('gmail/authorize', 'GmailController@authorize');
    $router->post('gmail/unauthorize', 'GmailController@unauthorize');
});
