<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Helpers\SqlMigrations;
class ConvertMigrationsCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'convert:migrations {database} {--ignore=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts an existing MySQL database to migrations.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ignoreInput = str_replace(' ', '', $this->option('ignore'));
        $ignoreInput = explode(',', $ignoreInput);

        $migrate = new SqlMigrations;
        $migrate->ignore($ignoreInput);
        $migrate->convert($this->argument('database'));

        $migrate->write();
        $this->info('Migration Created Successfully');
    }
}
