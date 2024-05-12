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
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CalendarController extends Controller
{


    public function getEvents(Request $request)
    {

        $userId = $request->query('userId');
        $user = User::find(1);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $userPageSociaux  = $user->pageSociaux;


        $userPosts = [];

        foreach ($userPageSociaux as $pageSociaux) {
            // Récupérer les posts associés à ce PageSociaux
            $posts = $pageSociaux->posts;

            // Ajouter chaque post au tableau des posts
            foreach ($posts as $post) {
                $userPosts[] = [
                    'id' => $post->id,
                    'social_id' => $post->social_id,
                    'page_name' => $post->page_name,
                    'page_id' => $post->page_id,
                    'message' => $post->message,
                    'media_path' => $post->media_path,
                    'media_paths' => $post->media_paths,
                    'access_token' => $post->access_token,
                    'Programming_options' => $post->Programming_options,
                    'scheduledDateTime' => $post->scheduledDateTime,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                    'idpage' => $post->idpage,
                ];
            }
        }

        // Retourner le JSON contenant la liste des posts
        return response()->json($userPosts);
    }
public function fetchPostsFromMeta()
    {
        $pageId = "115449061452354";
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        $url = "https://graph.facebook.com/v17.0/{$pageId}/feed?fields=id,message,created_time,attachments{media}&access_token={$access_token}&is_published=false";
        $response = Http::get($url);

        if ($response->successful()) {
            $postsData = $response->json()['data'];

            foreach ($postsData as $postData) {
                $socialId = $postData['id'];
                $message = $postData['message'] ?? '';
                $mediaPath = null;

                if (!empty($postData['attachments']['data'])) {
                    $mediaItem = $postData['attachments']['data'][0]['media'];
                    $mediaPath = $mediaItem['image']['src'] ?? ($mediaItem['video']['src'] ?? null);
                }

                $programmingOptions = 'published';

                $existingPost = Post::where('social_id', $socialId)->first();

                // Récupérer le nom de la page à partir de l'ID de la page
                $responsePage = Http::get("https://graph.facebook.com/v17.0/{$pageId}?fields=name&access_token={$access_token}");

                if ($responsePage->successful()) {
                    $pageData = $responsePage->json();
                    $pageName = $pageData['name'];
                } else {
                    $pageName = 'Innovation page';
                }

                if ($existingPost)   {
                    // Update the existing post
                    $existingPost->update([
                        'message' => $message,
                        'media_path' => $mediaPath,
                        'access_token' => $access_token,
                        'Programming_options' => $programmingOptions,
                        'page_name' => $pageName,
                    ]);
                    $existingPost->created_at = new Carbon($postData['created_time']);

                    $existingPost->user_id = Auth::user()->id;
                    $existingPost->save();
                } else {
                    // Create a new post record
                    $newPost = new Post([
                        'social_id' => $socialId,
                        'page_id' => $pageId,
                        'page_name' => $pageName,
                        'message' => $message,
                        'media_path' => $mediaPath,
                        'access_token' => $access_token,
                        'Programming_options' => $programmingOptions,
                    ]);
                    $newPost->created_at = new Carbon($postData['created_time']);
                    $newPost->user_id = Auth::user()->id;
                    $newPost->save();
                }
            }

            return response()->json([
                'message' => 'Publications récupérées avec succès.',
            ]);
        } else {
            return response()->json([
                'error' => 'Une erreur s\'est produite lors de la récupération des publications.',
                'details' => $response->body()
            ], 500);
        }
    }

}
