<?php

declare(strict_types=1);

namespace LaravelStrictCodingStandard\Sniffs\Laravel;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use LaravelStrictCodingStandard\Exceptions\FileNotFound;
use LaravelStrictCodingStandard\Exceptions\InstanceIsNotLaravelApplication;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use ReflectionClass;
use RuntimeException;
use SlevomatCodingStandard\Helpers\StringHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatement;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_values;
use function assert;
use function file_exists;
use function is_object;

use const DIRECTORY_SEPARATOR;
use const T_COMMA;
use const T_OPEN_TAG;
use const T_SEMICOLON;
use const T_STRING;

class DisallowUseOfFacadesSniff implements Sniff
{
    public const CODE_LARAVEL_FACADE_INSTANCE_USAGE           = 'LaravelFacadeInstanceUsage';
    public const CODE_LARAVEL_REAL_TIME_FACADE_INSTANCE_USAGE = 'LaravelRealTimeFacadeInstanceUsage';
    private const IS_REAL_TIME_FACADE                         = 'isRealTimeFacade';

    /**
     * @psalm-suppress MissingConstructor
     * @var            string|null
     */
    public $laravelApplicationInstancePath;
    /** @var array<string, array<string, bool>>|null */
    private $facades;
    /** @var string|null */
    private $realTimeFacadesNamespace;

    /**
     * @return array<int, (int|string)>
     */
    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    /**
     * @param int $openTagPointer
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function process(File $phpcsFile, $openTagPointer): void
    {
        $useStatements            = UseStatementHelper::getUseStatementsForPointer($phpcsFile, $openTagPointer);
        $facades                  = $this->getFacades($this->laravelInstancePath($phpcsFile));
        $realTimeFacadesNamespace = $this->getRealTimeFacadesNamespace();
        foreach ($useStatements as $useStatement) {
            /**
             * @psalm-suppress InternalMethod
             */
            $useStatementFullyQualifiedTypeName = $useStatement->getFullyQualifiedTypeName();
            $hasRealTimeFacadeNamespace         = StringHelper::startsWith(
                $useStatementFullyQualifiedTypeName,
                $realTimeFacadesNamespace
            );
            if ($hasRealTimeFacadeNamespace !== true) {
                continue;
            }

            $facades[$useStatementFullyQualifiedTypeName] = [self::IS_REAL_TIME_FACADE => true];
        }

        foreach ($useStatements as $useStatement) {
            /**
             * @psalm-suppress InternalMethod
             */
            $useStatementFullyQualifiedTypeName = $useStatement->getFullyQualifiedTypeName();
            if (! array_key_exists($useStatementFullyQualifiedTypeName, $facades)) {
                continue;
            }

            /**
             * @psalm-suppress InternalMethod
             */
            $useStatementStartPointer = $useStatement->getPointer() + 1;
            $useStatementEndPointer   = TokenHelper::findNext(
                $phpcsFile,
                [T_SEMICOLON, T_COMMA],
                $useStatementStartPointer
            );
            if ($useStatementEndPointer === null) {
                throw new RuntimeException('bad file provided, no semicolon on use statement');
            }

            $facadeUsagePointers = $this->getFacadeUsagePointers(
                $phpcsFile,
                $useStatement,
                $useStatementEndPointer,
                []
            );
            $extraErrorMessage   = '';
            $code                = self::CODE_LARAVEL_FACADE_INSTANCE_USAGE;
            if ($facades[$useStatementFullyQualifiedTypeName][self::IS_REAL_TIME_FACADE] === true) {
                $extraErrorMessage = ' Real-time ';
                $code              = self::CODE_LARAVEL_REAL_TIME_FACADE_INSTANCE_USAGE;
            }

            foreach ($facadeUsagePointers as $facadeUsagePointer) {
                /**
                 * @psalm-suppress InternalMethod
                 */
                $useStatementNameInFile = $useStatement->getNameAsReferencedInFile();
                $phpcsFile->addError(
                    'It is strongly discouraged not to use %s'
                    . $extraErrorMessage
                    . ' Laravel Facade, switch to constructor injection',
                    $facadeUsagePointer,
                    $code,
                    [$useStatementNameInFile]
                );
            }
        }
    }

    /**
     * @param list<int> $facadeUsagePointers
     *
     * @return list<int>
     */
    private function getFacadeUsagePointers(
        File $phpcsFile,
        UseStatement $useStatement,
        int $endPointer,
        array $facadeUsagePointers
    ): array {
        /**
         * @psalm-suppress InternalMethod
         */
        $useStatementNameInFile = $useStatement->getNameAsReferencedInFile();
        $facadeUsagePointer     = TokenHelper::findNextContent(
            $phpcsFile,
            [T_STRING],
            $useStatementNameInFile,
            $endPointer
        );
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

    /**
     * @return array<string, array<string, bool>>
     */
    private function getFacades(string $laravelInstancePath): array
    {
        if ($this->facades !== null) {
            return $this->facades;
        }

        $this->bootstrapAndTerminateLaravelApplication($laravelInstancePath);
        /**
         * @var array<string, string> $aliasesFacades
         */
        $aliasesFacades = AliasLoader::getInstance()->getAliases();
        $this->facades  = array_fill_keys(
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

        $aliasLoaderInstance               = AliasLoader::getInstance();
        $facadeNamespaceReflectionProperty = (new ReflectionClass($aliasLoaderInstance))
            ->getProperty('facadeNamespace');
        $facadeNamespaceReflectionProperty->setAccessible(true);
        /**
         * @psalm-var string realTimeFacadesNamespace
         */
        $this->realTimeFacadesNamespace = $facadeNamespaceReflectionProperty->getValue($aliasLoaderInstance);

        return $this->realTimeFacadesNamespace;
    }

    /**
     * need to do it so we can load all facades and their aliases
     */
    private function bootstrapAndTerminateLaravelApplication(string $laravelInstancePath): void
    {
        /**
         * @var            Application|mixed $laravelApplicationInstance
         * @psalm-suppress UnresolvableInclude
         */
        $laravelApplicationInstance = include $laravelInstancePath;
        if (
            ! is_object($laravelApplicationInstance)
            || (! $laravelApplicationInstance instanceof Application)
        ) {
            throw new InstanceIsNotLaravelApplication(
                'Given path is wrong [property laravelApplicationInstancePath]. path is - '
                . $laravelInstancePath
            );
        }

        $kernel = $laravelApplicationInstance->make(Kernel::class);
        assert($kernel instanceof Kernel);
        $kernel->bootstrap();
        $laravelApplicationInstance->terminate();
    }

    private function laravelInstancePath(File $phpcsFile): string
    {
        /**
         * @var ?string $basePath
         */
        $basePath = $phpcsFile->config->basepath;
        if ($basePath === null) {
            $basePath = __DIR__
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . '..';
        }

        if ($this->laravelApplicationInstancePath === null) {
            //todo find out a way to test it
            $realLaravelApplicationInstancePath = $basePath
                . DIRECTORY_SEPARATOR
                . 'bootstrap'
                . DIRECTORY_SEPARATOR
                . 'app.php';
        } else {
            $realLaravelApplicationInstancePath = $basePath
                . DIRECTORY_SEPARATOR
                . $this->laravelApplicationInstancePath;
        }

        if (! file_exists($realLaravelApplicationInstancePath)) {
            throw new FileNotFound('can\'t find laravel instance file path');
        }

        return $realLaravelApplicationInstancePath;
    }
}
