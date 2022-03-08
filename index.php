<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use JupiterAPI\EPhoto360;

require_once __DIR__ .'/vendor/autoload.php';
print json_encode(EPhoto360::create(622, [
    'image' => __DIR__ .'/storage/example.png',
    'text' => ['Jupiter', 'API'],
    'radio0' => [
        'radio' => '18a29e79-7ef2-420b-a141-d9ec93cd1ac7'
    ]
]));
//var_dump(EPhoto360::create(589, [
//    'text' => [
//       'Javad Sadeghi',
//    ]
//]));