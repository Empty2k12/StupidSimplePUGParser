<?php

require_once '../StupidSimplePugParser.php';

echo StupidSimplePugParser::create()
        ->withFile('test.pug')
        ->setOptions(array(
            'variables' => array(
                'name' => 'Gero Gerke'
            )
        ))
        ->toHtml();