<?php

chdir(realpath(dirname(__FILE__) . '/..'));
include "./ae/core.class.php";

AeCore::load();

$node = new AeNode;

$node->set(array(
    'foo' => 'one',
    'bar' => 'two',
    'test',
    'baz' => 'lightyear'
));

$string = serialize($node);

echo $string . "\n\n";