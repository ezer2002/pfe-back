<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use Illuminate\Http\Request;
//use Socialite;
use App\User;
use Illuminate\Support\Facades\Http;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PostModificationController extends Controller
{

    public function modifyPost(Request $request)
    {
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        $pageId = $request->input('page_id');

        // Validate the request
        $validator = Validator::make($request->all(), [
            'id' => 'required', // Local Post ID
            'page_id' => 'required',
            'Programming_options' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Retrieve the post from the database
        $post = Post::find($request->input('id'));

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Process pageID modification
        $post->page_id = $pageId;

        $response = Http::get("https://graph.facebook.com/v17.0/{$pageId}?fields=name&access_token={$access_token}");

        if ($response->failed()) {
            // Gérer l'erreur si la requête échoue
            $post->page_id = $request->input('page_id'); // Affecter l'ID de la Page en cas d'erreur
        } else {
            $pageData = $response->json();
            $pageName = $pageData['name'];
            $post->page_name = $pageName;
        }  
        
        // Process message and media_path update if requested
        if ($request->has('delete_message')) {
            // Supprimer le message du post
            $post->message = null;
        }

        if ($request->has('delete_media')) {
            // Supprimer le media_path du post
            $post->media_path = null;
        }

        // Process message modification
        if ($request->has('message')) {
            $post->message = $request->input('message');
        }

        // Process media_path update if a file is provided
        if ($request->hasFile('media_path')) {
            $mediaFile = $request->file('media_path');
            $ext = $mediaFile->getClientOriginalExtension();
            $filename = time() . '.' . $ext;
            $mediaFile->move('uploads/', $filename);

            // Update the media path in the database
            $post->media_path = 'uploads/' . $filename;
        }

        $post->page_id = $request->input('page_id');
        $post->save();

        if ($request->input('Programming_options') === 'Publier') {
            $post->Programming_options = 'Publier';
            $description = $post->message;
            $media = $post->media_path;

            // Check if media_path exists and publish post with media
            /*if (!empty($post->media_path) && file_exists(public_path($post->media_path))) {
                
                // Check if it's a video or photo and publish accordingly
                if (mime_content_type(public_path($post->media_path)) == 'video/mp4') {
                    $response = Http::attach(
                        'source',
                        fopen('uploads/' . $filename, 'r'),
                        $filename
                    )->post("https://graph.facebook.com/v17.0/{$post->page_id}/videos", [
                        'description' => $post->message, 
                        'access_token' => $access_token,
                    ]);
    
                    if ($response->failed()) {
                        return response()->json(['error' => 'Failed to publish video on Facebook'], 500);
                    }
    
                }else {
                    // Publication sur Facebook en tant qu'image
                    $response = Http::attach(
                        'source',
                        fopen('uploads/' . $filename, 'r'),
                        'file.' . $ext
                    )->post("https://graph.facebook.com/v17.0/{$post->page_id}/photos", [
                        'message' => $post->message,
                        'access_token' => $access_token,
                    ]); 
                    //return msg
    
                    if ($response->failed()) {
                        return response()->json(['error' => 'Failed to publish photo on Facebook'], 500);
                    }
                }
    
                // Extraction du social_id du post publié
                $postData = $response->json();
                $socialId = $postData['id'];
            }
            
            else {
                // Publish post with message only if media_path is missing
                if (!empty($post->message)) {
                    // Publication sur Facebook
                    $response = Http::post("https://graph.facebook.com/v17.0/{$post->page_id}/feed", [
                        'message' => $post->message,
                        'access_token' => $access_token,
                    ]);

                    // Vérification des erreurs de requête
                    if ($response->failed()) {
                        // Gérer l'erreur de requête
                        return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
                    }
                    // Extraction du social_id du post publié
                    $postData = $response->json();
                    $socialId = $postData['id'];
                }    
            }*/

            if (!empty($media)) {
                // Vérifier s'il s'agit d'une vidéo
                if (mime_content_type(public_path($media)) == 'video/mp4') {
                    // Handle video publication
                    $response = Http::attach(
                        'source',
                        fopen(public_path($media), 'r'),
                        basename($media)
                    )->post("https://graph.facebook.com/v17.0/{$pageId}/videos", [
                        'description' => $description,  // Use post message as description
                        'access_token' => $access_token,
                    ]);
                }else {
                    // Handle image publication
                    $response = Http::attach(
                        'source',
                        fopen(public_path($media), 'r'),
                        basename($media)
                    )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                        'message' => $description,  // Use post message as image caption
                        'access_token' => $access_token,
                    ]);

                    if ($response->failed()) {
                        return response()->json(['error' => 'Failed to publish photo on Facebook'], 500);
                    }
                }

                // Extraction du social_id du post publié
                $postData = $response->json();
                $socialId = $postData['id'];
            }

            if (empty($media) && !empty($description)) {
                // Publication sur Facebook
                $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/feed", [
                    'message' => $description,
                    'access_token' => $access_token,
                ]);

                // Vérification des erreurs de requête
                if ($response->failed()) {
                    // Gérer l'erreur de requête
                    return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
                }
                // Extraction du social_id du post publié
                $postData = $response->json();
                $socialId = $postData['id'];
            }

            

            $post->social_id = $socialId;


            $post->save();

            // Retourner le social_id comme réponse JSON
            return response()->json(['social_id' => $socialId]);

        } 

        if ($request->input('Programming_options') === 'Programmée') {
            $post->Programming_options = 'Programmée';
            $description = $post->message;
            $media = $post->media_path;

            $scheduledDateTime = $request->input('scheduled_datetime');
            
            $validator = Validator::make($request->all(), [
                'scheduled_datetime' =>  'required|date_format:Y-m-d H:i:s',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            // Conversion de la date planifiée en timestamp
            $scheduledDateTime = Carbon::parse($scheduledDateTime);

            $now = Carbon::now();
            $nowTime=$now->copy()->addHour();

            if($scheduledDateTime ->diffInMinutes($nowTime)<10|| $scheduledDateTime->diffInDays($nowTime) < 30 )
            {  
                if (!empty($media)) {

                    // Vérifier s'il s'agit d'une vidéo
                    if (mime_content_type(public_path($media)) == 'video/mp4') {
                        // Handle video publication
                        $response = Http::attach(
                            'source',
                            fopen(public_path($media), 'r'),
                            basename($media)
                        )->post("https://graph.facebook.com/v17.0/{$pageId}/videos", [
                            'description' => $description,
                            'access_token' => $access_token,
                            'published' => false,
                            'scheduled_publish_time' => strtotime($scheduledDateTime),
                        ]);
                    } else {
                        // Handle image publication
                        $response = Http::attach(
                            'source',
                            fopen(public_path($media), 'r'),
                            basename($media)
    
                        )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                            'message' => $description,
                            'access_token' => $access_token,
                            'published' => false,
                            'scheduled_publish_time' => strtotime($scheduledDateTime),
                        ]);
                    }

                    if ($response->failed()) {
                        return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
                    }
                    // Extraction du social_id du post publié
                    $postData = $response->json();
                    $socialId = $postData['id'];

                }

                /*if ($request->hasFile('media_paths')) {
                    $mediaPaths = $request->file('media_paths');
                    $uploadedFilesPaths = [];

                    foreach ($mediaPaths as $media) {
                        $extension = $media->getClientOriginalExtension();
                        $filename = time() . '_' . Str::random(5) . '.' . $extension;
                        $media->move('uploads/', $filename);

                        $uploadedFilesPaths[] = 'uploads/' . $filename;

                        // Publication de chaque média sur Facebook
                        $response = Http::attach(
                            'source',
                            fopen('uploads/' . $filename, 'r'),
                            $filename
                        )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                            'message' => $message,
                            'access_token' => $access_token,
                            'published' => false,
                            'scheduled_publish_time' => $scheduledDateTime,
                        ]);

                        // Vérification des erreurs de requête pour chaque média
                        if ($response->failed()) {
                            return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
                        }
                    }

                    $post->media_paths = json_encode($uploadedFilesPaths);
                    $mediaUploaded = true;
                }*/

                // **Publication de message uniquement**
                if (empty($media) && !empty($description)) {
                    // Publication sur Facebook
                    $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/feed", [
                        'message' => $description,
                        'access_token' => $access_token,
                        'published' => false,
                        'scheduled_publish_time' => $scheduledDateTime,
                    ]);

                    // Vérification des erreurs de requête
                    if ($response->failed()) {
                        // Gérer l'erreur de requête
                        return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
                    }
                    // Extraction du social_id du post publié
                    $postData = $response->json();
                    $socialId = $postData['id'];

                }

                // **Enregistrement du post dans la base de données**
                $post->social_id = $socialId;
                $post->scheduledDateTime = $scheduledDateTime;
                $post->save();
                
                /*$msg = "Publication programmée avec succès pour la date $scheduledDateTime";
                return response()->json(['message' =>   $msg ]);*/

                // Retourner le social_id comme réponse JSON
                return response()->json(['social_id' => $socialId]);
            }
            else{
                return response()->json(['error' => 'La date de publication doit être comprise entre 10 minutes et 30 jours après la date actuelle'], 400);
            }       

        }

        if ($request->input('Programming_options') === 'Brouillon') {
            return response()->json(['message' => 'Successfully scheduled post']);
        }
        else {
            // Cas par défaut si aucune des valeurs attendues n'est fournie
            return response()->json(['error' => 'Invalid Programming_options value'], 400);
        }
        
        

        /*return response()->json(['message' => 'Successfully edited post']);
      

    
        
        // Update the Programming_options and other fields accordingly
        return response()->json(['message' => 'Successfully published post']);


  
        
        // Update the Programming_options and other fields accordingly
        return response()->json(['message' => 'Successfully scheduled post']);*/        
                

           
        
    }
}