<?php

require_once '../StupidSimplePugParser.php';

$start = microtime(true);
StupidSimplePugParser::create()
        ->withFile('test.pug')
        ->setOptions(array(
            'cache' => false,
            'variables' => array(
                'name' => 'Gero Gerke'
            )
        ))
        ->toHtml();
$end = microtime(true);
echo ($end - $start) . " Seconds without Cache<br>";

$start_c = microtime(true);
StupidSimplePugParser::create()
        ->withFile('test.pug')
        ->setOptions(array(
            'cache' => true,
            'variables' => array(
                'name' => 'Gero Gerke'
            )
        ))
        ->toHtml();
$end_c = microtime(true);
echo ($end_c - $start_c) . " Seconds with Cache";

//15.873917818069 Seconds