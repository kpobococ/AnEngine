<?php

chdir(realpath(dirname(__FILE__) . '/..'));
include "./ae/core.class.php";

AeCore::load();

$string = new AeString('Привет, хуй моржовый');

echo $string->replace(',', '')
            ->toCamelCase()
            ->hyphenate('-', 'А-Я');