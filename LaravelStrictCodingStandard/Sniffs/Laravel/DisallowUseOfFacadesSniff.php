<?php

declare(strict_types=1);

namespace LaravelStrictCodingStandard\Sniffs\Laravel;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Illuminate\Foundation\AliasLoader;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\StringHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatement;
use SlevomatCodingStandard\Helpers\UseStatementHelper;


class DisallowUseOfFacadesSniff implements Sniff
{
    public const CODE_LARAVEL_FACADE_INSTANCE_USAGE = 'LaravelFacadeInstanceUsage';
    public const CODE_LARAVEL_REAL_TIME_FACADE_INSTANCE_USAGE = 'LaravelRealTimeFacadeInstanceUsage';
    private const IS_REAL_TIME_FACADE = 'isRealTimeFacade';

    /** @var array */
    public $laravelApplicationInstancePath;

    private $facades;
    /** @var string */
    private $normalizedLaravelApplicationInstancePath;
    /** @var string */
    private $realTimeFacadesNamespace;

    //todo create something for real time facades
    public function register(): array
    {
        return [
            T_OPEN_TAG,
        ];
    }

    public function process(File $phpcsFile, $openTagPointer)
    {
        $useStatements = UseStatementHelper::getUseStatements($phpcsFile, $openTagPointer);
        $facades = $this->getFacades();
        $realTimeFacadesNamespace = $this->getRealTimeFacadesNamespace();
        foreach ($useStatements as $useStatement) {
            $hasRealTimeFacadeNamespace = StringHelper::startsWith(
                $useStatement->getFullyQualifiedTypeName(),
                $realTimeFacadesNamespace
            );
            if ($hasRealTimeFacadeNamespace === true) {
                $facades[$useStatement->getFullyQualifiedTypeName()] = [self::IS_REAL_TIME_FACADE => true];
            }
        }
        foreach ($useStatements as $useStatement) {
            if (array_key_exists($useStatement->getFullyQualifiedTypeName(), $facades)) {
                $useStatementEndPointer = TokenHelper::findNext(
                    $phpcsFile,
                    [T_SEMICOLON, T_COMMA],
                    $useStatement->getPointer() + 1
                );
                $facadeUsagePointers = $this->getFacadeUsagePointers($phpcsFile, $useStatement, $useStatementEndPointer, []);
                $extraErrorMessage = '';
                $code = self::CODE_LARAVEL_FACADE_INSTANCE_USAGE;
                if ($facades[$useStatement->getFullyQualifiedTypeName()][self::IS_REAL_TIME_FACADE] === true) {
                    $extraErrorMessage = 'Real-time';
                    $code = self::CODE_LARAVEL_REAL_TIME_FACADE_INSTANCE_USAGE;
                }
                foreach ($facadeUsagePointers as $facadeUsagePointer) {
                    $phpcsFile->addError(
                        'It is strongly discouraged not to use %s '. $extraErrorMessage .' Laravel Facade, switch to constructor injection',
                        $facadeUsagePointer,
                        $code,
                        [$useStatement->getNameAsReferencedInFile()]
                    );
                }
            }
        }
    }

    private function getFacadeUsagePointers(
        File $phpcsFile,
        UseStatement $useStatement,
        int $endPointer,
        array $facadeUsagePointers
    ): array {
        $facadeUsagePointer = TokenHelper::findNextContent($phpcsFile, T_STRING,
            $useStatement->getNameAsReferencedInFile(), $endPointer);
        if ($facadeUsagePointer !== null) {
            $facadeUsagePointers[] = $facadeUsagePointer;
            return $this->getFacadeUsagePointers(
                $phpcsFile,
                $useStatement,
                $facadeUsagePointer + 1,
                $facadeUsagePointers
            );
        }

        return $facadeUsagePointers;
    }

    private function getFacades(): array
    {
        if ($this->facades !== null) {
            return $this->facades;
        }
        $this->bootstrapAndTerminateLaravelApplication();
        $aliasesFacades = AliasLoader::getInstance()->getAliases();
        $this->facades = array_fill_keys(
            array_merge(
                array_keys($aliasesFacades),
                array_values($aliasesFacades)
            ),
            [self::IS_REAL_TIME_FACADE => false]
        );

        return $this->facades;
    }

    private function getRealTimeFacadesNamespace(): string
    {
        if ($this->realTimeFacadesNamespace !== null) {
            return $this->realTimeFacadesNamespace;
        }
        $aliasLoaderInstance = AliasLoader::getInstance();
        $facadeNamespaceReflectionProperty = (new \ReflectionClass($aliasLoaderInstance))->getProperty('facadeNamespace');
        $facadeNamespaceReflectionProperty->setAccessible(true);
        $this->realTimeFacadesNamespace = $facadeNamespaceReflectionProperty->getValue($aliasLoaderInstance);
        return $this->realTimeFacadesNamespace;
    }

    private function bootstrapAndTerminateLaravelApplication(): void
    {

        if ($this->laravelApplicationInstancePath !== null) {
            $this->normalizedLaravelApplicationInstancePath = SniffSettingsHelper::normalizeArray($this->laravelApplicationInstancePath)[0];
        } else {
            //todo find out a way to test it
            $this->normalizedLaravelApplicationInstancePath = DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
        }
        /** @var \Illuminate\Foundation\Application $laravelApplicationInstance */
        $laravelApplicationInstance = require  __DIR__
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . $this->normalizedLaravelApplicationInstancePath;
        /** @var \Illuminate\Contracts\Console\Kernel $kernel */
        $kernel = $laravelApplicationInstance->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $laravelApplicationInstance->terminate();
    }
}