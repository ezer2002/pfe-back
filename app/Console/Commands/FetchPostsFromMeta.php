<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CalendarController;
use App\Models\Post;
use Carbon\Carbon;

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

        $scheduledPosts = Post::where('Programming_options', 'Programmée')
                            ->where('scheduledDateTime', '<=', Carbon::now())
                            ->get();

        foreach ($scheduledPosts as $post) {
            // Mettre à jour le champ Programming_options du post en 'Publier'
            $post->Programming_options = 'Publier';
            $post->save();
        }

        $this->info('Les posts programmés ont été vérifiés et publiés.');

    }
}
