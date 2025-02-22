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
            $destinationPath = $destination;

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
        if (! $this->check('config')) {
            $this->publishStubs([
                __DIR__.'/../stubs/config/project.stub' => $this->app->configPath('project.php'),
            ]);
            $this->commitChanges('Add Project config');
        }

        // Install Larastan
        if (! $this->check('larastan')) {
            $command->comment('Installing Larastan...');
            $result = Process::quietly()->run('composer require --dev "larastan/larastan:^2.0"');
            if ($result->successful()) {
                $this->publishStubs([
                    __DIR__.'/../stubs/phpstan.stub' => $this->app->basePath('phpstan.neon'),
                ]);

                $this->commitChanges('Add Larastan');
            }
        } else {
            $command->comment('Larastan already installed.');
        }

        // Install pest
        if (! $this->check('pest')) {
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
        } else {
            $command->comment('Pest already installed.');
        }

        // Install duster
        if (! $this->check('duster')) {
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
        } else {
            $command->comment('Duster already installed.');
        }
    }

    protected function installTailwindCSS(InstallCommand $command): void
    {
        if ($this->check('tailwind') && ! confirm('TailwindCSS already installed. Would you like to reinstall?')) {
            return;
        }

        $command->comment('Installing TailwindCSS...');

        $result = Process::pipe(function (Pipe $pipe) {
            $pipe->command('npm install -D tailwindcss postcss autoprefixer');
            $pipe->command('npx tailwindcss init -p');
        }, function (string $type, string $output) {
            echo $output;
        });

        if ($result->successful()) {
            $this->commitChanges('Add TailwindCSS');
        }
    }

    protected function installFilament(InstallCommand $command): void
    {
        if ($this->check('filament') && ! confirm('Filament already installed. Would you like to reinstall?')) {
            return;
        }

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
        if ($this->check('prettier') && ! confirm('Prettier already installed. Would you like to reinstall?')) {
            return;
        }

        $command->comment('Installing Prettier...');

        $result = Process::pipe(function (Pipe $pipe) {
            $pipe->command('npm install -D prettier@^3.4.2 prettier-plugin-blade prettier-plugin-tailwindcss@^0.6.10');
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

        if ($this->check('github_actions') && ! confirm('GitHub Actions already installed. Would you like to reinstall?')) {
            return;
        }

        $command->comment('Installing GitHub Actions...');

        $this->publishStubs([
            __DIR__.'/../stubs/workflows/'.$workflow.'.stub' => $this->app->basePath('.github/workflows/ci.yml'),
        ]);

        $this->commitChanges('Add GitHub Actions');
    }

    protected function installDBUpdates(InstallCommand $command): void
    {
        if ($this->check('db_updates')) {
            return;
        }

        $command->comment('Installing DB updates...');

        $filename = date('Y_m_d_his').'_create_database_updates_table.php';

        $this->publishStubs([
            __DIR__.'/../database/migrations/create_database_updates_table.stub' => $this->app->databasePath('migrations/'.$filename),
        ]);

        $result = Process::run('php artisan migrate --path='.$filename);

        if ($result->successful()) {
            $this->commitChanges('Add DB updates');
        }
    }

    protected function check(string $feature): bool
    {
        return match ($feature) {
            'config' => File::exists($this->app->configPath('project.php')),
            'larastan' => File::exists($this->app->basePath('vendor/larastan/larastan')),
            'pest' => File::exists($this->app->basePath('vendor/pestphp/pest')),
            'tailwind' => File::exists($this->app->basePath('node_modules/tailwindcss')),
            'filament' => File::exists($this->app->basePath('vendor/filament/filament')),
            'db_updates' => collect(File::allFiles($this->app->databasePath('migrations')))->map->getFilename()->filter(fn ($file) => str_contains($file, 'create_database_updates_table'))->isNotEmpty(),
            'prettier' => File::exists($this->app->basePath('.prettierrc')),
            'duster' => File::exists($this->app->basePath('vendor/tightenco/duster')),
            'github_actions' => File::exists($this->app->basePath('.github/workflows/ci.yml')),
            default => false,
        };
    }
}
