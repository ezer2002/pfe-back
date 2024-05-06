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
    /*public function getEvents()
    {
        $posts = Post::all();
        
        $events = $posts->map(function($post) {
            $event = [
                'id' => $post->id,
                'title' => $post->page_id,
                'start' => $post->Programming_options === 'published' ? $post->created_at : ($post->Programming_options === 'Programmée' ? $post->scheduledDateTime : $post->created_at), 
                'end' => $post->Programming_options === 'published' ? $post->created_at : ($post->Programming_options === 'Programmée' ? $post->scheduledDateTime : $post->created_at),
                'color' => $post->Programming_options === 'published' ? 'green' : ($post->Programming_options === 'Programmée' ? 'orange' : 'blue'),
                'subtitle' => $post->Programming_options,
            ];
            
            return $event;
        });

        return response()->json($events);
    }*/

    public function getEvents()
    {
        
        //$posts = Post::all();
        $posts = Post::select('id', 'page_id', 'Programming_options', 'created_at', 'scheduledDateTime')->get();
        
        $events = $posts->map(function($post) {
            $event = [
                'id' => $post->id,
                'title' => $post->page_id,
                'start' => $post->Programming_options === 'published' ? $post->created_at : ($post->Programming_options === 'programmed' ? $post->scheduledDateTime : $post->created_at),
                'end' => $post->Programming_options === 'published' ? $post->created_at : ($post->Programming_options === 'programmed' ? $post->scheduledDateTime : $post->created_at),
                'color' => $post->Programming_options === 'published' ? 'green' 
                        : ($post->Programming_options === 'programmed' ? 'orange' 
                        : ($post->Programming_options === 'Meta Business Suite' ? 'purple'
                        : ($post->Programming_options === 'Meta Business Suite_Programmer' ? 'yellow' : 'blue'))),
                'subtitle' => $post->Programming_options,
            ];
            
            return $event;
        });
        /*$events = $posts->map(function($post) {
            $programmingOption = $post->Programming_options;
            $isPublished = $programmingOption === 'published';
            $isScheduled = $programmingOption === 'programmed';
            
            $event = [
                'id' => $post->id,
                'title' => $post->page_id,
                'start' => $isPublished || !$isScheduled ? $post->created_at : $post->scheduledDateTime, 
                'end' => $isPublished || !$isScheduled ? $post->created_at : $post->scheduledDateTime,
                'color' => $isPublished ? 'green' : ($isScheduled ? 'orange' : 'blue'),
                'subtitle' => $programmingOption,
            ];
            
            return $event;
        });
 */

        return response()->json($events);
    }

   /*public function fetchPostsFromMeta()
    {
        $pageId = "115449061452354";
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";

        // Récupération des publications déjà publiées
        $publishedUrl = "https://graph.facebook.com/{$pageId}/published_posts";
        $publishedUrl .= "?access_token={$access_token}&fields=id,message,created_time,attachments{media}";

        $publishedResponse = Http::get($publishedUrl);

        if ($publishedResponse->successful()) {
            $publishedPosts = $publishedResponse->json()['data'];

            foreach ($publishedPosts as $postData) {
                // Traitement des publications déjà publiées
                $socialId = $postData['id'];
                $message = isset($postData['message']) ? $postData['message'] : '';
                $mediaPath = null;

                if (!empty($postData['attachments']['data'])) {
                    $mediaItem = $postData['attachments']['data'][0]['media'];
                    $mediaPath = $mediaItem['image']['src'] ?? ($mediaItem['video']['src'] ?? null);
                }

                $existingPost = Post::where('social_id', $socialId)->first();

                if ($existingPost) {
                    $existingPost->update([
                        'message' => $message,
                        'media_path' => $mediaPath,
                        'created_at' => $postData['created_time'],
                    ]);
                } else {
                    Post::create([
                        'social_id' => $socialId,
                        'page_id' => $pageId,
                        'message' => $message,
                        'media_path' => $mediaPath,
                        'created_at' => $postData['created_time'],
                        'access_token' => $access_token,
                        'Programming_options' => 'Meta Business Suite',
                    ]);
                }
            }
        }

        // Récupération des publications programmées
        $scheduledUrl = "https://graph.facebook.com/{$pageId}/scheduled_posts?access_token={$access_token}&fields=id,message,scheduled_publish_time,attachments{media}";
        $scheduledPosts = $this->fetchScheduledPosts($pageId, $access_token);

        $scheduledResponse = Http::get($scheduledUrl);

        foreach ($scheduledPosts as $scheduledPost) {
            $socialId = $scheduledPost['id'];
            $message = isset($scheduledPost['message']) ? $scheduledPost['message'] : '';
            $mediaPath = null;
        
            if (!empty($scheduledPost['attachments']['data'])) {
                $mediaItem = $scheduledPost['attachments']['data'][0]['media'];
                $mediaPath = $mediaItem['image']['src'] ?? ($mediaItem['video']['src'] ?? null);
            } 
        
            $existingScheduledPost = Post::where('social_id', $socialId)->whereNull('created_at')->first();
        
            if ($existingScheduledPost) {
                $existingScheduledPost->update([
                    'message' => $message,
                    'media_path' => $mediaPath,
                    'scheduledDateTime' => Carbon::createFromTimestamp($scheduledPost['scheduled_publish_time']),
                ]);
            } else {
                Post::create([
                    'social_id' => $socialId,
                    'page_id' => $pageId,
                    'message' => $message,
                    'media_path' => $mediaPath,
                    'scheduledDateTime' => Carbon::createFromTimestamp($scheduledPost['scheduled_publish_time']),
                    'access_token' => $access_token,
                    'Programming_options' => 'Meta Business Suite_Programmer',
                ]);
            }
        }

        return response()->json([
            'message' => 'Publications récupérées avec succès.',
        ]);

    }

    private function fetchScheduledPosts($pageId, $access_token)  
    {
        $scheduledUrl = "https://graph.facebook.com/{$pageId}/scheduled_posts?access_token={$access_token}&fields=id,message,scheduled_publish_time,attachments{media}";
        
        $scheduledResponse = Http::get($scheduledUrl);

        if ($scheduledResponse->successful()) {
            return $scheduledResponse->json()['data'];
        } else {
            return [];
        }
    }*/


    /*public function fetchMetaBusinessSuitePosts()
    {
        $pageId = "115449061452354";
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        $Programming_options = 'Meta Business Suite';

        $response = Http::get("https://graph.facebook.com/{$pageId}/posts", [
            'access_token' => $access_token,
            'fields' => 'id,message,created_time,attachments'
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch posts from MetaBusinessSuite'], 500);
        }

        $postsData = $response->json()['data'];

        foreach ($postsData as $postData) {
            $socialId = $postData['id'];
    
        // Vérifier si le message existe dans $postData
        $message = isset($postData['message']) ? $postData['message'] : null;

        // Vérifier si le social_id existe déjà dans la base de données
        $existingPost = Post::where('social_id', $socialId)->first();

        if ($existingPost) {
            // Vérifier s'il y a des modifications aux champs
            if ($existingPost->message != $message) {
                $existingPost->message = $message;
            }
    
                if (isset($postData['attachments']['data'][0]['media']['image'])) {
                    $image = $postData['attachments']['data'][0]['media']['image']['src'];
                    if ($existingPost->media_path != $image) {
                        $existingPost->media_path = $image;
                    }
                }
    
                if (isset($postData['attachments']['data'][0]['media']['video'])) {
                    $image = $postData['attachments']['data'][0]['media']['video']['src'];
                    if ($existingPost->media_path != $video) {
                        $existingPost->media_path = $video;
                    }
                }
    
                // Enregistrer les modifications
                $existingPost->save();
            } else {
                // Créer un nouveau post s'il n'existe pas encore
                $newPost = new Post();
                $newPost->social_id = $socialId;
                $newPost->page_id = $pageId;
                $newPost->message = $postData['message'] ?? '';
                $newPost->created_at = $postData['created_time'];
                $newPost->access_token = $access_token;
                $newPost->Programming_options = $Programming_options;
    
                if (isset($postData['attachments']['data'][0]['media']['image'])) {
                    $image = $postData['attachments']['data'][0]['media']['image']['src'];
                    $newPost->media_path = $image;
                }
    
                if (isset($postData['attachments']['data'][0]['media']['video'])) {
                    $video = $postData['attachments']['data'][0]['media']['video']['src'];
                    $post->media_path = $video;
                }
    
                $newPost->save();
            }
        }
    

        return response()->json(['message' => 'Posts fetched and saved successfully']);
    }

    public function fetchScheduledPosts()
    {
        $pageId = "115449061452354";
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        $Programming_options = 'Meta Business Suite_Programmer';
        $graphUrl = "https://graph.facebook.com/v17.0/me/media_publishing_schedules?filter=page_id%3D{$pageId}&fields=id,scheduled_publish_time,message,attachments&access_token={$access_token}";

        try {
            $response = Http::get($graphUrl);

            if ($response->successful()) {
                $scheduledPosts = $response->json()['data'];

                foreach ($scheduledPosts as $scheduledPost) {
                    $socialId = $scheduledPost['id'];
                    $scheduledTime = $scheduledPost['scheduled_publish_time'];
                    $message = isset($scheduledPost['message']) ? $scheduledPost['message'] : null;
                    $attachments = $scheduledPost['attachments'] ?? null;

                    // Vérifier si le message existe dans $postData
                    $message = isset($scheduledPost['message']) ? $scheduledPost['message'] : null;

                    // Vérifier si le post programmé existe déjà dans la base de données
                    $existingPost = Post::where('social_id', $socialId)->first();

                    if ($existingPost) {
                        // Mettre à jour les champs du post existant
                        $existingPost->message = $message;
                        $existingPost->scheduledDateTime = $scheduledTime;

                        if (isset($scheduledPost['attachments']['data'][0]['media']['image'])) {
                            $image = $scheduledPost['attachments']['data'][0]['media']['image']['src'];
                            if ($existingPost->media_path != $image) {
                                $existingPost->media_path = $image;
                            }
                        }
            
                        if (isset($scheduledPost['attachments']['data'][0]['media']['video'])) {
                            $video = $scheduledPost['attachments']['data'][0]['media']['video']['src'];
                            if ($existingPost->media_path != $video) {
                                $existingPost->media_path = $video;
                            }
                        }

                        $existingPost->save();
                    } else {
                        // Créer un nouveau post programmé s'il n'existe pas encore
                        $newPost = new Post();
                        $newPost->social_id = $socialId;
                        $newPost->page_id = $pageId;
                        $newPost->message = $message;
                        $newPost->scheduledDateTime = $scheduledTime;

                        if (isset($scheduledPost['attachments']['data'][0]['media']['image'])) {
                        $image = $scheduledPost['attachments']['data'][0]['media']['image']['src'];
                        $newPost->media_path = $image;
                        }
            
                        if (isset($scheduledPost['attachments']['data'][0]['media']['video'])) {
                            $video = $scheduledPost['attachments']['data'][0]['media']['video']['src'];
                            $newPost->media_path = $video;
                        }
        
                        $newPost->access_token = $access_token;
                        $newPost->Programming_options = $Programming_options;
                        $newPost->save();
                    }
                }
                
                return response()->json(['message' => 'Posts programmés récupérés et enregistrés avec succès', 'data' => $scheduledPosts]);
            } else {
                return response()->json(['error' => 'Erreur lors de la récupération des posts programmés'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Exception lors de la récupération des posts programmés: ' . $e->getMessage()], 500);
        }
    }

    public function fetchScheduledPosts()
    {
        $pageId = "115449061452354";
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";

        // URL de l'API pour récupérer les publications programmées
        $graphUrl = "https://graph.facebook.com/v17.0/{$pageId}/scheduled_posts";
        $graphUrl .= "?access_token={$access_token}&fields=id,message,scheduled_publish_time,attachments{media}";

        try {
            $response = Http::get($graphUrl);

            if ($response->successful()) {
                $scheduledPosts = $response->json()['data'];

                foreach ($scheduledPosts as $scheduledPost) {
                    $socialId = $scheduledPost['id'];
                    $scheduledTime = $scheduledPost['scheduled_publish_time'];
                    
                    // Vérifier si le message existe dans $scheduledPost
                    $message = isset($scheduledPost['message']) ? $scheduledPost['message'] : '';
                    
                    // Récupérer le chemin du média
                    $mediaPath = null;

                    // Gestion des pièces jointes
                    if (!empty($scheduledPost['attachments']['data'])) {
                        $mediaItem = $scheduledPost['attachments']['data'][0]['media'];
                        $mediaPath = $mediaItem['image']['src'] ?? ($mediaItem['video']['src'] ?? null);
                    }

                    // Vérifier si le post programmé existe déjà dans la base de données
                    $existingPost = Post::where('social_id', $socialId)->first();

                    if ($existingPost) {
                        // Mettre à jour les champs du post existant
                        $existingPost->update([
                            'message' => $message,
                            'media_path' => $mediaPath,
                            'scheduled_date_time' => $scheduledTime,
                        ]);
                    } else {
                        // Créer un nouveau post programmé s'il n'existe pas encore
                        Post::create([
                            'social_id' => $socialId,
                            'page_id' => $pageId,
                            'message' => $message,
                            'media_path' => $mediaPath,
                            'scheduled_date_time' => $scheduledTime,
                            'access_token' => $access_token,
                            'programming_options' => 'Meta Business Suite_Programmer',
                        ]);
                    }
                }

                return response()->json([
                    'message' => 'Posts programmés récupérés et enregistrés avec succès',
                    'data' => $scheduledPosts
                ]);
            } else {
                // Afficher le message d'erreur complet pour le débogage
                $errorDetails = $response->json();
                return response()->json([
                    'error' => 'Erreur lors de la récupération des posts programmés',
                    'details' => $errorDetails
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception lors de la récupération des posts programmés: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchPostsFromMeta()
    {
        $pageId = "115449061452354";
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";

        $graphUrl = "https://graph.facebook.com/{$pageId}/published_posts";
        $graphUrl .= "?access_token={$access_token}&fields=id,message,created_time,attachments{media}";

        $response = Http::get($graphUrl);

        if ($response->successful()) {
            $posts = $response->json()['data'];

            foreach ($posts as $postData) {
                $socialId = $postData['id'];
                $message = isset($postData['message']) ? $postData['message'] : '';
                //$message = $postData['message'] ?? '';
                $mediaPath = null;

                if (!empty($postData['attachments']['data'])) {
                    $mediaItem = $postData['attachments']['data'][0]['media'];
                    $mediaPath = $mediaItem['image']['src'] ?? ($mediaItem['video']['src'] ?? null);
                }

                $existingPost = Post::where('social_id', $socialId)->first();

                if ($existingPost) {
                    $existingPost->update([
                        'message' => $message,
                        'media_path' => $mediaPath,
                        'created_at' => $postData['created_time'],
                    ]);
                } else {
                    Post::create([
                        'social_id' => $socialId,
                        'page_id' => $pageId,
                        'message' => $message,
                        'media_path' => $mediaPath,
                        'created_at' => $postData['created_time'],
                        'access_token' => $access_token,
                        'Programming_options' => 'Meta Business Suite',
                    ]);
                }
            }

            return response()->json([
                'message' => 'Posts fetched and saved successfully',
                'data' => $posts
            ]);
        }

        return response()->json(['error' => 'Failed to fetch posts from Meta Business Suite'], 500);
    }*/

    //private $pageId = "115449061452354"; 
    //private $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD"; 

    /*public function fetchPostsFromMeta()
    {   
        $pageId = "115449061452354";
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD"; 
        // Fetch both published and scheduled posts in a single request
        $url = "https://graph.facebook.com/v17.0/{$pageId}/feed?fields=id,message,created_time,scheduled_publish_time,attachments{media}&access_token={$access_token}&is_published=false";

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

                // Determine if the post is scheduled or published based on the existence of 'scheduled_publish_time'
                $isScheduled = isset($postData['scheduled_publish_time']);
                //dd($isScheduled);
                $dateField = $isScheduled ? 'scheduledDateTime' : 'created_at';
                $dateValue = $isScheduled ? Carbon::createFromTimestamp($postData['scheduled_publish_time']) : new Carbon($postData['created_time']);
                $programmingOptions = $isScheduled ? 'Meta Business Suite_Programmer' : 'Meta Business Suite';

                // Check for an existing post using 'social_id'
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
                        'page_name' => $pageName,
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


    }*/

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
