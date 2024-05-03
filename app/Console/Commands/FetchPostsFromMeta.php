<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CalendarController;

class FetchPostsFromMeta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-posts-from-meta';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve posts from Meta automatically every minute.';
    
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
     */
    public function handle()
    {
        $controller = new CalendarController();
        $controller->fetchPostsFromMeta();
    }
}
