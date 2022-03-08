<?php


use JupiterAPI\EPhoto360;

require_once __DIR__ .'/../vendor/autoload.php';

print json_encode(EPhoto360::create(680, [
    'text' => ['JupiterAPI'],
    'radio0' => [
        'radio' => '2d6f1ad1-39df-4175-9faa-9348b6ba1551', // Call EPhoto360::getEffect() method to see the list of effects.
    ]
]));