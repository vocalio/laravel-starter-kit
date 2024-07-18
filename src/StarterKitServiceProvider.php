<?php

namespace Vocalio\LaravelStarterKit;

use Illuminate\Process\Pipe;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vocalio\LaravelStarterKit\Commands\MakeUpdateCommand;
use Vocalio\LaravelStarterKit\Commands\UpdateDatabaseCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class StarterKitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('starter-kit')
            ->hasCommand(UpdateDatabaseCommand::class)
            ->hasCommand(MakeUpdateCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->startWith(function (InstallCommand $command) {

                    $this->setup($command);

                    if (confirm(label: 'Would you like install Tailwind CSS?', default: true)) {
                        $this->installTailwindCSS($command);
                    }

                    if (confirm(label: 'Would you like install Filament?', default: true)) {
                        $this->installFilament($command);
                    }

                    if (confirm(label: 'Would you like install DB updates?', default: true)) {
                        $this->installDBUpdates($command);
                    }

                    if (confirm(label: 'Would you like install Prettier?', default: true)) {
                        $this->installPrettier($command);
                    }

                    $this->installGithubActions(select(
                        label: 'Would you like to setup a GitHub Actions workflows?',
                        options: [
                            'none' => 'No workflows',
                            'checks' => 'Setup checks only',
                            'deployer' => 'Setup checks and deployer',
                        ],
                        default: 'none',
                    ), $command);
                });
            });
    }

    protected function commitChanges(string $message): void
    {
        Process::pipe([
            'git add .',
            "git commit -q -m \"$message\"",
        ]);
    }

    protected function publishStubs(array $stubs)
    {
        foreach ($stubs as $source => $destination) {
            $destinationPath = $this->app->basePath($destination);

            // Ensure the directory exists
            File::ensureDirectoryExists(dirname($destinationPath));

            // Copy the file
            File::copy($source, $destinationPath);
        }
    }

    protected function setup(InstallCommand $command): void
    {
        $command->info('Setting up your Laravel app...');

        // Add project config
        $this->publishStubs([
            __DIR__.'/../stubs/config/project.stub' => $this->app->configPath('project.php'),
        ]);
        $this->commitChanges('Add Project config');

        // Install Larastan
        $command->comment('Installing Larastan...');
        $result = Process::quietly()->run('composer require --dev "larastan/larastan:^2.0"');
        if ($result->successful()) {
            $this->publishStubs([
                __DIR__.'/../stubs/phpstan.stub' => $this->app->basePath('phpstan.neon'),
            ]);

            $this->commitChanges('Add Larastan');
        }

        // Install pest
        $command->comment('Installing Pest...');
        $result = Process::pipe([
            'composer remove phpunit/phpunit',
            'composer require pestphp/pest --dev --with-all-dependencies',
            './vendor/bin/pest --init',
        ]);
        if ($result->successful()) {
            $this->publishStubs([
                __DIR__.'/../stubs/tests/pest.stub' => $this->app->basePath('tests/Pest.php'),
                __DIR__.'/../stubs/tests/architecture-test.stub' => $this->app->basePath('tests/ArchitectureTest.php'),
                __DIR__.'/../stubs/tests/Unit/example-test.stub' => $this->app->basePath('tests/Unit/ExampleTest.php'),
                __DIR__.'/../stubs/tests/Feature/example-test.stub' => $this->app->basePath('tests/Feature/ExampleTest.php'),
            ]);

            $this->commitChanges('Add Pest');
        }

        // Install duster
        $command->comment('Installing Duster...');
        $result = Process::quietly()->run('composer require tightenco/duster --dev');
        if ($result->successful()) {
            $this->publishStubs([
                __DIR__.'/../stubs/pint.stub' => $this->app->basePath('pint.json'),
                __DIR__.'/../stubs/tlint.stub' => $this->app->basePath('tlint.json'),
                __DIR__.'/../stubs/duster.stub' => $this->app->basePath('duster.json'),
            ]);

            $this->commitChanges('Add Duster');
        }
    }

    protected function installTailwindCSS(InstallCommand $command): void
    {
        $command->comment('Installing TailwindCSS...');

        $result = Process::pipe(function (Pipe $pipe) {
            $pipe->command('npm install -D tailwindcss postcss autoprefixer');
            $pipe->command('npx tailwindcss init');
        }, function (string $type, string $output) {
            echo $output;
        });

        if ($result->successful()) {
            $this->commitChanges('Add TailwindCSS');
        }
    }

    protected function installFilament(InstallCommand $command): void
    {
        $command->comment('Installing Filament...');

        $result = Process::pipe(function (Pipe $pipe) {
            $pipe->command('composer require filament/filament:"^3.2" -W');
            $pipe->command('php artisan filament:install --panels');
        }, function (string $type, string $output) {
            echo $output;
        });

        if ($result->successful()) {
            $this->commitChanges('Add Filament');
        }
    }

    protected function installPrettier(InstallCommand $command): void
    {
        $command->comment('Installing Prettier...');

        $result = Process::pipe(function (Pipe $pipe) {
            $pipe->command('npm install -D prettier@^3.2.5 prettier-plugin-blade prettier-plugin-tailwindcss@^0.5.11');
        }, function (string $type, string $output) {
            echo $output;
        });

        if ($result->successful()) {
            $this->publishStubs([
                __DIR__.'/../stubs/duster-with-prettier.stub' => $this->app->basePath('duster.json'),
                __DIR__.'/../stubs/.prettierrc.stub' => $this->app->basePath('.prettierrc'),
                __DIR__.'/../stubs/.prettierignore.stub' => $this->app->basePath('.prettierignore'),
            ]);

            $this->commitChanges('Add Prettier');
        }
    }

    protected function installGithubActions(string $workflow, InstallCommand $command): void
    {
        if ($workflow === 'none') {
            return;
        }

        $command->comment('Installing GitHub Actions...');

        $this->publishStubs([
            __DIR__.'/../stubs/.github/workflows/'.$workflow.'.yml' => $this->app->basePath('.github/workflows/'.$workflow.'.yml'),
        ]);

        $this->commitChanges('Add GitHub Actions');
    }

    protected function installDBUpdates(InstallCommand $command): void
    {
        $command->comment('Installing DB updates...');

        $result = Process::pipe(function (Pipe $pipe) {
            $pipe->command('composer require --dev beyondcode/laravel-dump-server');
            $pipe->command('php artisan vendor:publish --provider="BeyondCode\DumpServer\DumpServerServiceProvider"');
            $pipe->command('php artisan migrate');
        }, function (string $type, string $output) {
            echo $output;
        });

        if ($result->successful()) {
            $this->commitChanges('Add DB updates');
        }
    }
}
