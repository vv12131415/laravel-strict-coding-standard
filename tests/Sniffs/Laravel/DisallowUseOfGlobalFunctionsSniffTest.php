<?php

declare(strict_types=1);

namespace LaravelStrictCodingStandard\Sniffs\Laravel;

use SlevomatCodingStandard\Sniffs\TestCase;

class DisallowUseOfGlobalFunctionsSniffTest extends TestCase
{
    public function testErrorsWithExcludeFunctions(): void
    {
        $report = self::checkFile(
            __DIR__ . '/data/globalFunctionUsedFileWithExcludeFunction.php',
            [
                'excludeFunctions' => [
                    'app'
                ],
            ]
        );
        self::assertSame(2, $report->getErrorCount());

        self::assertSniffError($report, 8, DisallowUseOfGlobalFunctionsSniff::CODE_LARAVEL_GLOBAL_FUNCTION_USAGE);
        self::assertSniffError($report, 9, DisallowUseOfGlobalFunctionsSniff::CODE_LARAVEL_GLOBAL_FUNCTION_USAGE);
    }

    public function testErrorsWithoutExcludeFunctions(): void
    {
        $report = self::checkFile(__DIR__ . '/data/globalFunctionUsedFile.php');
        self::assertSame(2, $report->getErrorCount());

        self::assertSniffError($report, 7, DisallowUseOfGlobalFunctionsSniff::CODE_LARAVEL_GLOBAL_FUNCTION_USAGE);
        self::assertSniffError($report, 8, DisallowUseOfGlobalFunctionsSniff::CODE_LARAVEL_GLOBAL_FUNCTION_USAGE);
    }

    public function testNoErrors(): void
    {
        self::assertNoSniffErrorInFile(self::checkFile(__DIR__ . '/data/noGlobalFunctionUsedFile.php'));
    }
}
