<?php

$router->group([], function () use ($router) {
    $router->get('plugins/gmail/initialize', 'GmailController@initialize');
    $router->post('plugins/gmail/authorize', 'GmailController@authorize');
    $router->post('plugins/gmail/unauthorize', 'GmailController@unauthorize');
});
