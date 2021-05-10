<?php

$router->group([], function () use ($router) {
    $router->get('gmail/initialize', 'GmailController@initialize');
    $router->post('gmail/authorize', 'GmailController@authorize');
    $router->post('gmail/unauthorize', 'GmailController@unauthorize');
});
