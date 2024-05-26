<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use App\Models\PageSociauxModel;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Http;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PostModificationController extends Controller
{

    public function modifyPost(Request $request)
    {
        // Validate the request
        // $validator = Validator::make($request->all(), [
        //     'id' => 'required', // Local Post ID
        // ]);



        // Retrieve the post from the database
        $post = Post::find($request->input('id'));

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        if ($post->Programming_options === 'saved as draft') {
            // Process pageID modification
            $page = PageSociauxModel::find($request->idpage);
            if (!$page) {
                return response()->json(['error' => 'Page not found'], 404);
            }
            $pageId = $page->page_id;
            $access_token = $page->access_token;

            $post->page_id = $pageId;

            $pageName = $page->page_name;
            $post->page_name = $pageName;


                $post->message = $request->message;



                $post->media_path =$request->media_path;

                // Retrieve the current date and time
        $currentTime = Carbon::now();

        // Update the created_at field of the post with the current date and time
        $post->created_at = $currentTime;

     
            // Process media_path update if a file is provided
            if ($request->hasFile('media_path')) {
                $mediaFile = $request->file('media_path');
                $ext = $mediaFile->getClientOriginalExtension();
                $filename = time() . '.' . $ext;
                $mediaFile->move('uploads/', $filename);

                // Update the media path in the database
                $post->media_path = 'uploads/' . $filename;
            }

if ($request->has('media_pathsdelete')) {
    $mediaPaths = [];
    $mediaFiles = $request->input('media_pathsdelete'); // Utilisez input() pour obtenir les données

    if (is_array($mediaFiles)) {
        foreach ($mediaFiles as $media) {
            $mediaPaths[] = $media;
        }
    } else {
        $mediaPaths[] = $mediaFiles; // Au cas où il n'y aurait qu'un seul élément
    }

    // Assurez-vous que $mediaPaths contient les chemins des fichiers correctement enregistrés
    $post->media_paths = json_encode($mediaPaths);
    $post->save();
            }
   if ($request->hasFile('media_paths')) {
    $existingMediaPaths = json_decode($post->media_paths, true) ?? []; // Récupérer les chemins existants

    $mediaFiles = $request->file('media_paths');

    foreach ($mediaFiles as $media) {
        $extension = $media->getClientOriginalExtension();
        $uniqueId = uniqid(); // Generate a unique ID
        $filename = time() . '_' . $uniqueId . '.' . $extension;
        // $filename = time() . '.' . $extension;
        $media->move('uploads/', $filename);

        $uploadedFilePath = 'uploads/' . $filename;
        $mediaPaths[] = $uploadedFilePath;
    }

    // Assurez-vous que $mediaPaths contient les chemins des fichiers correctement enregistrés
    $post->media_paths = json_encode($mediaPaths);

    //return response()->json(['media_paths' => $mediaPaths]);
}

            $post->page_id = $pageId;

            $post->update();

            if ($request->input('Programming_options') === 'published') {
                $post->Programming_options = 'published';
                 $description = $request->message;
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
           
              
                if ($media!='null') {
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

                if ($media=='null' &&    $description) {
                    // Publication sur Facebook
                    $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/feed", [
                        'message' =>   $description,
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
                $post->idpage =$request->input('idpage');

                $post->save();

                // Retourner le social_id comme réponse JSON
                return response()->json(['social_id' =>"ok"]);

            }

            if ($request->input('Programming_options') === 'programmed') {
                $post->Programming_options = 'programmed';
                $description = $request->message;
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
                    if ($media!='null') {
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
    
                    if ($media=='null' &&    $description) {
                        // Publication sur Facebook
                        $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/feed", [
                            'message' =>   $description,
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
                    if ($request->hasFile('media_paths')) {
                        $mediaPaths = $request->file('media_paths');
                        $uploadedFilesPaths = [];

                        foreach ($mediaPaths as $media) {
                            $extension = $media->getClientOriginalExtension();
                            $uniqueId = uniqid(); // Generate a unique ID
                            $filename = time() . '_' . $uniqueId . '.' . $extension;
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
                    }

                 

                    // **Enregistrement du post dans la base de données**
                    $post->social_id = $socialId;
                    $post->scheduledDateTime = $scheduledDateTime;
                    $post->idpage =$request->input('idpage');
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
            return response()->json(['message' => 'Post  modified'], 200);

            // if ($request->input('Programming_options') === 'saved as draft') {
            //     return response()->json(['message' => 'Post successfully modified.']);
            // }
            // else {
            //     // Cas par défaut si aucune des valeurs attendues n'est fournie
            //     return response()->json(['error' => 'Invalid Programming_options value'], 400);
            // }



            /*return response()->json(['message' => 'Successfully edited post']);




            // Update the Programming_options and other fields accordingly
            return response()->json(['message' => 'Successfully published post']);




            // Update the Programming_options and other fields accordingly
            return response()->json(['message' => 'Successfully scheduled post']);*/
        }else {
            return response()->json(['error' => 'Post is not saved as a draft, cannot be modified'], 400);
        }






    }
}