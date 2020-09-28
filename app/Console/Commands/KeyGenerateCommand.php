<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class KeyGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application key';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->isConfirmed() === false) {
            $this->comment('Phew... No changes were made to your App key.');
            return;
        }

        $key = $this->generateRandomKey();

        file_put_contents(base_path('.env'), preg_replace(
            '/^APP_KEY=[\w]*/m',
            'APP_KEY='.$key,
            file_get_contents(base_path('.env'))
        ));

        $this->info("Application key [$key] set successfully.");
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return str_random(32);
    }

    /**
     * Check if the modification is confirmed.
     *
     * @return bool
     */
    protected function isConfirmed()
    {
        return $this->confirm('This will invalidate all existing tokens. Are you sure you want to override the App key?');
    }
}
