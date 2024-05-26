<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PageSociauxModel;
use Illuminate\Support\Facades\Http;
use DateTime;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdCreative;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


use Validator;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class CalendarController extends Controller
{


    public function getEvents(Request $request)
    {

        $userId = $request->query('userId');
        $user = User::find(  $userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $userPageSociaux  = $user->pageSociaux;
        $userMetaPosts = [];

        $metapost = Post::where('idpage',NULL)->get();

            foreach(   $userPageSociaux as $page){
                foreach($metapost as $post){
                    if($page->page_id==$post->page_id){
                        $userMetaPosts[]=
                            $post;
                            
                        

                    }
                }
                 
            }
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
       
        $userPosts = collect($userPosts)->concat($userMetaPosts);

        // Retourner le JSON contenant la liste des posts
        return response()->json($userPosts);
    }
    public function fetchPostsFromMeta(Request $request)
    {
        
        $page_id = $request->query('page_id'); // Récupérer idpage à partir de la requête
        $page = PageSociauxModel::where('page_id', $page_id)->first();

        if (!$page) {
            return response()->json(['error' =>$page_id ], 404);
        }

            $pageId = $page->page_id;
            $access_token = $page->access_token; 

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
                        if (isset($mediaItem['image'])) {
                            $mediaPath = $mediaItem['image']['src'];
                        } elseif (isset($mediaItem['video'])) {
                            $mediaPath = $mediaItem['video']['src'];
                        }
                    }

                    $programmingOptions = 'published';

                    $existingPost = Post::where('social_id', $socialId)->first();
                

                    $pageName = $page->page_name;

                    if ($existingPost)   {
                        // Update the existing post
                        $existingPost->update([
                            'message' => $message,
                            'media_path' => $mediaPath,
                            'access_token' => $access_token,
                            'Programming_options' => $programmingOptions,
                            'page_name' =>$pageName,
                    
                        ]);
                        $existingPost->created_at = new Carbon($postData['created_time']);
                    

                        $existingPost->update();
                    
                    } else {
                        // Create a new post record
                        $post = new Post();




                        $post->social_id = $socialId;
                        $post->page_id = $pageId;
                
                    
                        $post->page_name = $pageName;
                        $post->media_path = $mediaPath;

                        $post->message = $message;
                        $post->access_token = $access_token;
                        $post->Programming_options = $programmingOptions;
                
                        // $post->idpage =$idpage;
                        $post->created_at = new Carbon($postData['created_time']);
                        
                        $post->save();
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
