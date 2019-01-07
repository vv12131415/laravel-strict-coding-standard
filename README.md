# Laravel strict coding standard

[PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) coding standards for Laravel,
do force developers not to use bad design decisions that are supported
by [Laravel](https://laravel.com)

## Sniffs included in this standard

### LaravelStrictCodingStandard.Laravel.DisallowUseOfGlobalFunctions

* checks for functions that are declared in

  * `vendor/laravel/framework/src/Illuminate/Foundation/helpers.php`
  * `vendor/laravel/framework/src/Illuminate/Support/helpers.php`

### LaravelStrictCodingStandard.Laravel.DisallowUseOfFacades

* checks for usage of Laravel Facades (including Real-time facades)
* if not configured it will use default path for `Illuminate\Foundation\Application`
instance `bootstrap/app.php`