<?php
require_once __DIR__.'/vendor/autoload.php';

$app = new Ark\Compare\Core\CliApp($argv);
$app->run();