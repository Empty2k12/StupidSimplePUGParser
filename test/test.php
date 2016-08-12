<?php

require_once '../StupidSimplePugParser.php';

$start_c = microtime(true);
echo StupidSimplePugParser::create()
        ->withFile('test.pug')
        ->setOptions(array(
            'cache' => false,
            'csrfToken' => hash('sha256', 'gero'),
            'variables' => array(
                'name' => 'Gero Gerke'
            )
        ))
        ->toHtml();
$end_c = microtime(true);
echo "<br>" . ($end_c - $start_c) . " Seconds without Cache";

//15.873917818069 Seconds