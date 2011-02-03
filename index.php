<?php

chdir(dirname(__FILE__));

include_once 'ae/core.php';

AeCore::load();

include 'debug.php';
// ========================================================================== //

$sh = stream_context_create(array(
    'http' => array(
        'method' => 'GET',
        'header' => "Content-type: multipart/form-data\r\n" .
                    "X-Debug-String: method=get;\r\n" .
                    "X-Debug-String: end;",
        'content' => http_build_query(array(
            'foo' => 'foo',
            'bar' => 'bar',
            'baz' => 'baz'
        ))
    )
));

$url = 'http://' . $_SERVER['HTTP_HOST'] . '/test/index.php?gettest=somevalue';

$response = file_get_contents($url, false, $sh);

echo 'Response: ' . $response;