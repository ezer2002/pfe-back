<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Http;
use DateTime;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdCreative;
use Carbon\Carbon;

class CalendarController extends Controller
{


    public function getEvents()
    {

        //$posts = Post::all();
        // $posts = Post::select('id','page_name', 'page_id', 'Programming_options', 'created_at', 'scheduledDateTime')->get();
        $posts = Post::all();
        $events = $posts->map(function($post) {
            $event = [
                'id' => $post->id,
                'title' => $post->page_name,
                'start' => $post->Programming_options === 'Publier' ? $post->created_at : ($post->Programming_options === 'Programmée' ? $post->scheduledDateTime : $post->created_at),
                'end' => $post->Programming_options === 'Publier' ? $post->created_at : ($post->Programming_options === 'Programmée' ? $post->scheduledDateTime : $post->created_at),
                'color' => $post->Programming_options === 'Publier' ? 'green'
                        : ($post->Programming_options === 'Programmée' ? 'orange'
                        : ($post->Programming_options === 'Meta Business Suite' ? 'purple'
                        : ($post->Programming_options === 'Meta Business Suite_Programmer' ? 'yellow' : 'blue'))),
                'subtitle' => $post->Programming_options,
            ];

            return $event;
        });


        return response()->json(  $posts);
    }


    public function fetchPostsFromMeta()
    {
        $pageId = "115449061452354";
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        // Fetch both published and scheduled posts in a single request
        $url = "https://graph.facebook.com/v17.0/{$pageId}/feed?fields=id,message,created_time,scheduled_publish_time,attachments{media}&access_token={$access_token}&is_published=false";

        $response = Http::get($url);


        if ($response->successful()) {
            $postsData = $response->json()['data'];
            //dd($postsData);
            foreach ($postsData as $postData) {
                $socialId = $postData['id'];
                $message = $postData['message'] ?? '';
                $mediaPath = null;

                if (!empty($postData['attachments']['data'])) {
                    $mediaItem = $postData['attachments']['data'][0]['media'];
                    $mediaPath = $mediaItem['image']['src'] ?? ($mediaItem['video']['src'] ?? null);
                }

                // Determine if the post is scheduled or published based on the existence of 'scheduled_publish_time'
                $isScheduled = isset($postData['scheduled_publish_time']);
                //dd($isScheduled);
                $dateField = $isScheduled ? 'scheduledDateTime' : 'created_at';
                $dateValue = $isScheduled ? Carbon::createFromTimestamp($postData['scheduled_publish_time']) : new Carbon($postData['created_time']);
                $programmingOptions = $isScheduled ? 'Meta Business Suite_Programmer' : 'Meta Business Suite';

                // Check for an existing post using 'social_id'
                $existingPost = Post::where('social_id', $socialId)->first();

                if ($existingPost)   {
                    // Update the existing post
                    $existingPost->update([
                        'message' => $message,
                        'media_path' => $mediaPath,
                        'access_token' => $access_token,
                        'Programming_options' => $programmingOptions,
                    ]);
                    // Update the date fields based on post type
                    if ($isScheduled)
                      {
                        $existingPost->scheduledDateTime = $dateValue;

                    } else {
                        $existingPost->created_at = $dateValue;
                    }

                    $existingPost->save();
                } else {
                    // Create a new post record
                    $newPost = new Post([
                        'social_id' => $socialId,
                        'page_id' => $pageId,
                        'message' => $message,
                        'media_path' => $mediaPath,
                        'access_token' => $access_token,
                        'Programming_options' => $programmingOptions,
                    ]);
                    // Set the date fields based on post type
                    if ($isScheduled) {
                       // return($isScheduled);
                        $newPost->scheduledDateTime = $dateValue;
                    } else {
                        $newPost->created_at = $dateValue;
                    }
                    return($isScheduled);
                    $newPost->save();
                }
            }

            return response()->json([
                'message' => 'Publications récupérées avec succès.',
            ]);
        } else {
            return response()->json([
                'error' => 'Une erreur s\'est produite lors de la récupération des publications.',
                'details' => $response->body() // Include the body of the response for debugging
            ], 500);
        }
    }

}
