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

class SocialiteController extends Controller
{
    // Les tableaux des providers autorisés
    protected $providers = ["facebook"];

    public function handleGraphInteraction(Request $request)
    {
        // Récupération des données de la requête
        $pageId = $request->input('page_id');
        $message = $request->input('message') ?? '';
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        $Programming_options = 'Publier';
        
        $post = new Post();
        if ($request->hasFile('media_path')) {
            $mediaFile = $request->file('media_path');
            $ext = $mediaFile->getClientOriginalExtension();
            $filename = time() . '.' . $ext;
            $mediaFile->move('uploads/', $filename);
            $post->media_path = 'uploads/' . $filename;
            
            // Vérifier s'il s'agit d'une vidéo
            if ($mediaFile->getClientMimeType() == 'video/mp4') {
                $response = Http::attach(
                    'source',
                    fopen('uploads/' . $filename, 'r'),
                    $filename
                )->post("https://graph.facebook.com/v17.0/{$pageId}/videos", [
                    'description' => $message, 
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
                )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                    'message' => $message,
                    'access_token' => $access_token,
                ]); 
                //return msg

                if ($response->failed()) {
                    return response()->json(['error' => 'Failed to publish photo on Facebook'], 500);
                }
            }


        }

        if ($request->hasFile('media_paths')) {
            $mediaPaths = $request->file('media_paths');
            $uploadedFilesUrls = []; // Tableau d'URL des photos hébergées en ligne
            $albumCreated = false;
            $albumId = null;

            foreach ($mediaPaths as $index => $media) {
                $extension = $media->getClientOriginalExtension();
                $filename = time() . '_' . Str::random(5) . '.' . $extension;
                $media->storePubliclyAs('uploads/', $filename); // Stockage public des photos
                $uploadedFilesUrls[] = url('storage/uploads/' . $filename); // Construire l'URL publique

                // Si c'est la première photo, nous créons l'album
                if ($index === 0) {
                    $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/albums", [
                    'access_token' => $access_token,
                    'name' => $message, // Facultatif : nom de l'album
                    'message' => $message, // Facultatif : description de l'album
                    ]);

                    if ($response->failed()) {
                        Log::error("Failed to create Facebook album: {$response->body()}");
                        return response()->json(['error' => 'Échec de la création de l\'album sur Facebook'], 500);
                    }

                    $albumId = $response->json()['id']; // Récupérer l'ID de l'album créé
                    $albumCreated = true;
                }

                // Publication de la photo dans l'album
                if ($albumCreated) {
                    $response = Http::post("https://graph.facebook.com/v17.0/{$albumId}/photos", [
                    'access_token' => $access_token,
                    'url' => $uploadedFilesUrls[$index],
                    ]);

                    if ($response->failed()) {
                        Log::error("Failed to publish photo to album: {$response->body()}");
                        return response()->json(['error' => 'Échec de la publication de la photo dans l\'album'], 500);
                    }
                }
            }

            $post->media_paths = json_encode($uploadedFilesUrls);
        }

        // Publication de message seulement si aucun média n'est présent
        if (!$request->hasFile('media_path') && !$request->hasFile('media_paths') && !empty($message)) {
            // Publication sur Facebook
            $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/feed", [
                'message' => $message,
                'access_token' => $access_token,
            ]);

            // Vérification des erreurs de requête
            if ($response->failed()) {
                // Gérer l'erreur de requête
                return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
            }
        }
        
        // Extraction du social_id du post publié
        $postData = $response->json();
        $socialId = $postData['id'];

        $post->social_id = $socialId;
        //$post->post_id = "4";
        $post->page_id = $pageId;
        $post->message = $message;
        $post->access_token = $access_token;
        $post->Programming_options = $Programming_options;
        $post->save();
        // Réponse JSON
        //return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données',]);
       
        // Retourner le social_id comme réponse JSON
        return response()->json(['social_id' => $socialId]);
    }
}    


/* 

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

class SocialiteController extends Controller
{
    // Les tableaux des providers autorisés
    protected $providers = ["facebook"];

    public function handleGraphInteraction(Request $request)
    {
        // Récupération des données de la requête
        $pageId = $request->input('page_id');
        $message = $request->input('message') ?? '';
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        $Programming_options = 'Publier';

        // Initialiser les variables de réponse et le Post
        $albumId = null;
        $response = null;
        $post = new Post();
        
        if ($request->hasFile('media_path')) {
            $mediaFile = $request->file('media_path');
            $ext = $mediaFile->getClientOriginalExtension();
            $filename = time() . '.' . $ext;
            $mediaFile->move('uploads/about/', $filename);
            $post->media_path = 'uploads/about/' . $filename;
            
            // Vérifier s'il s'agit d'une vidéo
            if ($mediaFile->getClientMimeType() == 'video/mp4') {
                $response = Http::attach(
                    'source',
                    fopen('uploads/about/' . $filename, 'r'),
                    $filename
                )->post("https://graph.facebook.com/v17.0/{$pageId}/videos", [
                    'description' => $message, 
                    'access_token' => $access_token,
                ]);

                if ($response->failed()) {
                    return response()->json(['error' => 'Failed to publish video on Facebook'], 500);
                }

            }else {
                // Publication sur Facebook en tant qu'image
                $response = Http::attach(
                    'source',
                    fopen('uploads/about/' . $filename, 'r'),
                    'file.' . $ext
                )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                    'message' => $message,
                    'access_token' => $access_token,
                ]); 
                //return msg

                if ($response->failed()) {
                    return response()->json(['error' => 'Failed to publish photo on Facebook'], 500);
                }
            }


        }

        /*if ($request->hasFile('media_paths')) {
            $mediaPaths = $request->file('media_paths');
            $uploadedFilesUrls = []; // Tableau d'URL des photos hébergées en ligne
            $albumCreated = false;
            $albumId = null;

            foreach ($mediaPaths as $index => $media) {
                $extension = $media->getClientOriginalExtension();
                $filename = time() . '_' . Str::random(5) . '.' . $extension;
                $media->storePubliclyAs('uploads/about/', $filename); // Stockage public des photos
                $uploadedFilesUrls[] = url('storage/uploads/about/' . $filename); // Construire l'URL publique

                // Si c'est la première photo, nous créons l'album
                if ($index === 0) {
                    $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/albums", [
                    'access_token' => $access_token,
                    'name' => $message, //  nom de l'album
                    'message' => $message, //  description de l'album
                    ]);

                    if ($response->failed()) {
                        Log::error("Failed to create Facebook album: {$response->body()}");
                        return response()->json(['error' => 'Échec de la création de l\'album sur Facebook'], 500);
                    }

                    $albumId = $response->json()['id']; // Récupérer l'ID de l'album créé
                    $albumCreated = true;
                }

                // Publication de la photo dans l'album
                if ($albumCreated) {
                    $response = Http::post("https://graph.facebook.com/v17.0/{$albumId}/photos", [
                    'access_token' => $access_token,
                    'url' => $uploadedFilesUrls[$index],
                    ]);

                    if ($response->failed()) {
                        Log::error("Failed to publish photo to album: {$response->body()}");
                        return response()->json(['error' => 'Échec de la publication de la photo dans l\'album'], 500);
                    }
                }
            }

            $post->media_paths = json_encode($uploadedFilesUrls);
        }

        if ($request->hasFile('media_paths')) {
            //$mediaPaths = $request->file('media_paths');
            $albumId = $this->createAlbum($pageId, $message, $access_token);
 
            if (!$albumId) {
                return response()->json(['error' => 'Échec de la création de l\'album sur Facebook'], 500);
            }
 
            // Boucle sur les médias pour les ajouter à l'album
            foreach ($request->file('media_paths') as $media) {
                $response = $this->uploadPhotoToAlbum($media, $albumId, $access_token);
                
                if (!$response) {
                    return response()->json(['error' => 'Échec de l\'envoi de la photo à Facebook'], 500);
                }
            }
         }
 
         // Publication de message seulement si aucun média n'est présent
         if (!$request->hasFile('media_path') && !$request->hasFile('media_paths') && !empty($message)) {
             // Publication sur Facebook
             $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/feed", [
                 'message' => $message,
                 'access_token' => $access_token,
             ]);
 
             // Vérification des erreurs de requête
             if ($response->failed()) {
                 // Gérer l'erreur de requête
                 return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
             }
         }
         
         
         // Enregistrer les détails de la publication une fois terminée
         $this->savePostDetails($post, $pageId, $message, $access_token, $Programming_options, $albumId, $response);
 
         // Retourner une réponse JSON avec l'ID de l'album ou de la publication
         return response()->json(['social_id' => $albumId ?? $response['id']]);
         }
 
         // Cette méthode crée un album sur Facebook et retourne l'ID de l'album
         private function createAlbum($pageId, $message, $access_token)
         {
             $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/albums", [
                 'name' => $message,
                 'message' => $message,
                 'access_token' => $access_token,
             ]);
 
             if ($response->successful()) {
                 return $response->json()['id'];
             } else {
                 Log::error("Échec de la création de l'album sur Facebook: {$response->body()}");
                 return null;
             }
         }
 
         // Cette méthode télécharge une photo dans l'album spécifié et retourne l'ID de la photo
         private function uploadPhotoToAlbum($media, $albumId, $access_token)
         {
             $filename = $media->getClientOriginalName();
             $media->storePubliclyAs('uploads/about/', $filename);
             $filePath = public_path('uploads/about/' . $filename);
 
             $response = Http::attach(
                 'source',
                 file_get_contents($filePath),
                 $filename
             )->post("https://graph.facebook.com/v17.0/{$albumId}/photos", [
                 'access_token' => $access_token,
             ]);
 
             if ($response->successful()) {
                 return $response->json()['id'];
             } else {
                 Log::error("Échec de l'envoi de la photo à l'album Facebook: {$response->body()}");
                 return null;
             }
             return $response;
         }
 
         // Cette méthode enregistre les détails de la publication après sa réalisation
         private function savePostDetails($post, $pageId, $message, $access_token, $Programming_options, $albumId, $response)
         {
             // Extraction du social_id du post publié
             $postData = $response->json();
             $socialId = $postData['id'];
 
             $post->social_id = $socialId;
             $post->page_id = $pageId;
             $post->message = $message;
             $post->access_token = $access_token;
             $post->Programming_options = $Programming_options;
             $post->save();
             // Réponse JSON
             return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données',]);
 
             // Retourner le social_id comme réponse JSON
             //return response()->json(['social_id' => $socialId]);
         }
 } 
 */