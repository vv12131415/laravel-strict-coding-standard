<?php

declare(strict_types=1);

//special autoloader for psalm usages, since php_codesniffer can't autoload some things by it's self

require __DIR__ . '/vendor/squizlabs/php_codesniffer/autoload.php';
require __DIR__ . '/vendor/squizlabs/php_codesniffer/src/Util/Tokens.php';
require __DIR__ . '/vendor/autoload.php';
