<?php

namespace App\Http\Controllers;

use App\Models\PageSociauxModel;
use Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use Illuminate\Http\Request;
//use Socialite;
use App\User;
use Illuminate\Support\Facades\Http;
//use Facebook\Facebook;
//use Facebook\Exceptions\FacebookResponseException;
//use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SocialiteController extends Controller
{
    public function handleGraphInteraction(Request $request)
    {



        $page = PageSociauxModel::find($request->idpage);
        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $pageId = $page->page_id; // Accès à la propriété page_id de l'objet $page
        $message = $request->input('message') ;
        $access_token = $page->access_token; // Accès à la propriété access_token de l'objet $page
        $Programming_options = 'published';


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

            // Extraction du social_id du post publié
            $postData = $response->json();
            $socialId = $postData['id'];
        }

        if ($request->hasFile('media_paths')) {

            $mediaPaths = [];
            /*$albumCreated = false;
            $socialId = null;*/

            // Parcourir et enregistrer les médias pour l'album
            foreach ($request->file('media_paths') as $index => $media) {
                $extension = $media->getClientOriginalExtension();
                $uniqueId = uniqid(); // Generate a unique ID
                $filename = time() . '_' . $uniqueId . '.' . $extension;
                 $media->move('uploads/', $filename); 

                $storedPath = 'uploads/' . $filename;
                $mediaPaths[] = $storedPath; // Ajoutez le chemin stocké au tableau

                /*if ($media->move('uploads/', $filename)) {
                    $mediaPaths[] = $storedPath;
                } else {
                    // Gérer les échecs de déplacement de fichiers
                    return response()->json(['error' => 'Failed to store media file'], 500);
                }*/
            }

            // Assurez-vous que $mediaPaths contient les chemins des fichiers correctement enregistrés
           // return response()->json(['media_paths' => $mediaPaths]);


            // Créer l'album sur Facebook
            $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/albums", [
                'access_token' => $access_token,
                'name' => $message, // Nom de l'album
                'message' => $message, // Description de l'album
            ]);

            // Vérification des erreurs de requête
            if ($response->failed()) {
                $errorDetails = $response->body();

                // Journaliser l'erreur
                Log::error('Erreur lors de la publication de l\'album sur Facebook: ' . $errorDetails);

                // Retourner un message d'erreur détaillé
                return response()->json(['error' => 'Échec de la publication de l\'album sur Facebook. Détails : ' . $errorDetails], 500);
            }

            $socialId = $response->json()['id']; // Récupérer l'ID de l'album créé

            // Publier les médias dans l'album (photos ou vidéos)
            foreach ($mediaPaths as $mediaPath) {
                $mediaFile = $mediaPath; // Choisir le média à publier

                // Vérifier s'il s'agit d'une vidéo
                if (pathinfo($mediaFile, PATHINFO_EXTENSION) == 'mp4') {
                    $response = Http::attach(
                        'source',
                        fopen($mediaFile, 'r'),
                        basename($mediaFile)
                    )->post("https://graph.facebook.com/v17.0/{$pageId}/videos", [
                        'description' => $message,
                        'access_token' => $access_token,
                    ]);
                } else {
                    // Publier l'image sur Facebook
                    $response = Http::attach(
                        'source',
                        fopen($mediaFile, 'r'),
                        basename($mediaFile)
                    )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                        'message' => '', // Ajouter un message par média si nécessaire
                        'access_token' => $access_token,
                    ]);
                }

                // Vérifier si la publication du média a échoué
                if ($response->failed()) {
                    Log::error("Failed to publish media to album: {$response->body()}");
                    return response()->json(['error' => 'Échec de la publication des médias dans l\'album sur Facebook'], 500);
                }
            }

            // Enregistrement des médias et du message dans la base de données
            $post->media_paths = json_encode($mediaPaths);
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
            // Extraction du social_id du post publié
            $postData = $response->json();
            $socialId = $postData['id'];
        }

        // **Enregistrement du post dans la base de données**
        $post->social_id = $socialId;
        $post->page_id = $pageId;

        $pageName = $page->page_name;
        $post->page_name = $pageName;
        
        $post->message = $message;
        $post->access_token = $access_token;
        $post->Programming_options = $Programming_options;
        $post->social_id =$socialId;
        $post->idpage =$request->input('idpage');

        $post->save();
        // Réponse JSON
        //return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données',]);

        // Retourner le social_id comme réponse JSON
        return response()->json(['social_id' => $socialId]);
        // Réponse JSON
        //return response()->json(['message' => 'Album publié sur la page Facebook et enregistré dans la base de données']);
    }
}
