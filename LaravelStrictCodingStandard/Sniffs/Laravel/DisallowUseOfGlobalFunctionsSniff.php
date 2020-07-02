<?php

declare(strict_types=1);

namespace LaravelStrictCodingStandard\Sniffs\Laravel;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use ReflectionFunction;

use function get_defined_functions;
use function in_array;

class DisallowUseOfGlobalFunctionsSniff extends ForbiddenFunctionsSniff
{
    public const CODE_LARAVEL_GLOBAL_FUNCTION_USAGE = 'LaravelGlobalFunctionUsage';

    /**
     * @psalm-suppress MissingConstructor
     * @var            list<string>|null
     */
    public $excludeFunctions;

    public function __construct()
    {
        // just get 2 first functions from 2 different helper files; the Support and the Foundation
        $laravelHelperFiles = [
            (new ReflectionFunction('abort'))->getFileName(),
            (new ReflectionFunction('append_config'))->getFileName(),
        ];
        foreach (get_defined_functions()['user'] as $functionName) {
            $function = new ReflectionFunction($functionName);
            if (! in_array($function->getFileName(), $laravelHelperFiles, true)) {
                continue;
            }

            $this->forbiddenFunctions[$functionName] = null;
        }
    }

    public function register()
    {
        if ($this->excludeFunctions !== null) {
            foreach ($this->excludeFunctions as $excludeFunction) {
                unset($this->forbiddenFunctions[$excludeFunction]);
            }
        }
        return parent::register();
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        return parent::process($phpcsFile, $stackPtr);
    }

    /**
     * {@inheritdoc}
     */
    protected function addError($phpcsFile, $stackPtr, $function, $pattern = null): void
    {
        $data  = [$function];
        $error = 'Laravel function %s() has been deprecated, it is highly recommended not to use it';
        $type  = self::CODE_LARAVEL_GLOBAL_FUNCTION_USAGE;
        $phpcsFile->addError($error, $stackPtr, $type, $data);
    }
}
