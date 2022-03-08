<?php


use JupiterAPI\EPhoto360;

require_once __DIR__ .'/../vendor/autoload.php';

print json_encode(EPhoto360::getTopEffects());