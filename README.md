# Laravel Starter Kit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vocalio/laravel-starter-kit.svg?style=flat-square)](https://packagist.org/packages/vocalio/laravel-starter-kit)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/vocalio/laravel-starter-kit/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/vocalio/laravel-starter-kit/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/vocalio/laravel-starter-kit/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/vocalio/laravel-starter-kit/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vocalio/laravel-starter-kit.svg?style=flat-square)](https://packagist.org/packages/vocalio/laravel-starter-kit)

## Installation

Get started with Laravel Starter Kit by installing it via Composer:

```bash
composer require vocalio/laravel-starter-kit
```

After installation, run the setup command: 

```bash
php artisan starter-kit:install
```

## Usage

During the installation process, you'll be guided through selecting the features you'd like to incorporate into your project.

### Available Features
- Larastan
- Pest
- Duster (TLint, PHP_CodeSniffer, PHP CS Fixer, Pint)
- Tailwind CSS
- Filament
- DB updates
- Prettier
- Github Actions

## Feature Details

### Larastan
Enhance your code quality with static analysis. We provide a pre-configured Larastan setup at level 7.
Customize your configuration in the `phpstan.neon` file.

Run Larastan with:  

```bash
vendor/bin/phpstan analyse
```

### Pest
We've chosen Pest as our testing framework. Our setup includes pre-configured Architectural tests.

Execute Pest tests using:

```bash
php artisan test
```

### Duster
Maintain clean and consistent code with Duster. It integrates TLint, PHP_CodeSniffer, PHP CS Fixer, and Pint to check and fix your code style.

For more information, refer to the [Duster documentation](https://github.com/tighten/duster)

### Tailwind CSS
Opt for Tailwind CSS to get a default configuration ready for your project.

### Filament
Choose Filament to install the package with a default configuration.

### DB updates
Our DB updates feature simplifies database modifications and fixes. It operates similarly to Laravel migrations.

#### Usage

Generate a new DB update:

```bash
php artisan db-update:create
```

This creates a new file in `database/updates`. Here are some examples of what you can do:

Update database values after refactoring:
```php
public function __invoke(): void
{
    Order::query()
        ->where('state', 'new')
        ->update(['state' => OrderState::CREATED]);
}
```

Clean up test data from your production database:

```php
public function __invoke(): void
{
    Order::query()
        ->whereDate('created_at', '<', '2024-01-01')
        ->delete();
}
```

Execute your DB update with:

```bash
php artisan db-update:run
```

### Prettier
Prettier enforces consistent code formatting. We provide a pre-configured setup (`.prettierrc`).

Use the `.prettierignore` file to exclude specific files or directories from Prettier's formatting.

### Github Actions
Our starter kit includes a pre-configured Github Actions workflow for automated testing and code style checks on every push to the main branch.

#### Permissions
To enable Github Actions to commit changes from Duster and Prettier, set the appropriate permissions:

Navigate to `github.com -> (Your project) -> Settings -> Actions -> General -> Workflow permissions` and select `Read and write permissions`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [dommer1](https://github.com/dommer1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
