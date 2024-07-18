<?php

namespace Vocalio\LaravelStarterKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeUpdateCommand extends Command
{
    public $signature = 'make:update {name : The name of the update file}';

    public $description = 'Create a new database update file';

    public function handle(): int
    {
        $name = str($this->argument('name'))->trim()->snake();
        $file = date('Y_m_d_His').'_'.$name.'.php';

        File::ensureDirectoryExists(database_path('updates'));
        File::put(database_path('updates/'.$file), File::get(__DIR__.'/../../stubs/database/database_update.stub'));

        $this->components->info(sprintf('Created database update [%s].', $file));

        return self::SUCCESS;
    }
}
