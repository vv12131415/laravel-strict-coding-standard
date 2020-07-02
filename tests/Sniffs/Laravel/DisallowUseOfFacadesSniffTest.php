<?php

declare(strict_types=1);

namespace LaravelStrictCodingStandard\Sniffs\Laravel;

use SlevomatCodingStandard\Sniffs\TestCase;

class DisallowUseOfFacadesSniffTest extends TestCase
{
    public function testNoErrors(): void
    {
        $file = self::checkFile(
            __DIR__ . '/data/NoFacadeUsageClass.php',
            [
                'laravelApplicationInstancePath' => '..' . __DIR__ . '/LaravelApplication/bootstrap/app.php',
            ]
        );
        self::assertNoSniffErrorInFile($file);
    }

    public function testErrors(): void
    {
        $report = self::checkFile(
            __DIR__ . '/data/FacadeUsageClass.php',
            [
                'laravelApplicationInstancePath' => '..' . __DIR__ . '/LaravelApplication/bootstrap/app.php',
            ]
        );

        self::assertSame(2, $report->getErrorCount());

        self::assertSniffError($report, 12, DisallowUseOfFacadesSniff::CODE_LARAVEL_FACADE_INSTANCE_USAGE);
        self::assertSniffError($report, 18, DisallowUseOfFacadesSniff::CODE_LARAVEL_FACADE_INSTANCE_USAGE);
    }

    public function testRealTimeFacadeErrors(): void
    {
        $report = self::checkFile(
            __DIR__ . '/data/RealTimeFacadeUsageClass.php',
            [
                'laravelApplicationInstancePath' => '..' . __DIR__ . '/LaravelApplication/bootstrap/app.php',
            ]
        );
        self::assertSame(1, $report->getErrorCount());

        self::assertSniffError($report, 12, DisallowUseOfFacadesSniff::CODE_LARAVEL_REAL_TIME_FACADE_INSTANCE_USAGE);
    }
}
