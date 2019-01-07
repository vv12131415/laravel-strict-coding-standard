<?php

declare(strict_types=1);

$app = new Illuminate\Foundation\Application(
    dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    LaravelStrictCodingStandard\Sniffs\Laravel\LaravelApplication\Kernel::class
);

return $app;
