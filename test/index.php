<?php

chdir(dirname(__FILE__) . '/..');

include_once 'ae/core.php';

AeCore::load();

$fh = @fopen('./req.log', 'a');

@parse_str(@file_get_contents('php://input'), $input);

@fwrite($fh, print_r(array(
    'method' => $_SERVER['REQUEST_METHOD'],
    'get' => $_GET,
    'post' => $_POST,
    'input' => $input,
    'server' => $_SERVER
), true) . "\n" . '------------------------' . "\n");
@fclose($fh);

echo 'OK';