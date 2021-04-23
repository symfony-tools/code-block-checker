<?php

// Fake autoloader.

foreach (glob(__DIR__.'/../src/**') as $path) {
    require_once $path;
}
