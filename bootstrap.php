<?php

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    echo <<<EOT
You must install the vendors before using this library:

    curl -s http://getcomposer.org/installer | php
    php composer.phar install

EOT;
    exit(1);
}

return require_once __DIR__.'/vendor/autoload.php';