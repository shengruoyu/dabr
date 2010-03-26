<?php

require 'mvc.php';
require 'config.php';

$d = new Dispatcher();
$d->defaultController = 'Twitter';
$d->dispatch();
