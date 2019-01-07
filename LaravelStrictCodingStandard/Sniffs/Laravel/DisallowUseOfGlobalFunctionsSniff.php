<?php

declare(strict_types=1);

namespace LaravelStrictCodingStandard\Sniffs\Laravel;

use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;

class DisallowUseOfGlobalFunctionsSniff extends ForbiddenFunctionsSniff
{
    public const CODE_LARAVEL_GLOBAL_FUNCTION_USAGE = 'LaravelGlobalFunctionUsage';

    public function __construct()
    {
        // just get 2 first functions from 2 different helper files; the Support and the Foundation
        $laravelHelperFiles = [
            (new \ReflectionFunction('abort'))->getFileName(),
            (new \ReflectionFunction('append_config'))->getFileName(),
        ];
        foreach (get_defined_functions()['user'] as $functionName) {
            $function = new \ReflectionFunction($functionName);
            if (in_array($function->getFileName(), $laravelHelperFiles, true)) {
                $this->forbiddenFunctions[$functionName] = null;
            }
        }
    }

    protected function addError($phpcsFile, $stackPtr, $function, $pattern = null)
    {
        $data = [$function];
        $error = 'Laravel function %s() has been deprecated, it is highly recommended not to use it';
        $type = self::CODE_LARAVEL_GLOBAL_FUNCTION_USAGE;
        $phpcsFile->addError($error, $stackPtr, $type, $data);
    }
}