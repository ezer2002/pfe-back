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
        $access_token = "EAADutQr9i3MBOxZCzeFhofvhPEB26xkspYAItSlZC6IqMZBC9KsDYj2yhInXIeCbHG9WQfSDQ230hYipyq2ivLRk61I31E33xLfgfbl3StpZB0ZCRrMeMSctKkUXPjmCClF3qW0aZC1oZBU1ORiAAz8ZAKYZBXtr00Y5VikiJ8SBSS83eeZA4T8j0wWq4BQcTxd4oZD";
        $Programming_options = 'Publier';
        
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
        
        $post->page_id = $pageId;
        $post->message = $message;
        $post->access_token = $access_token;
        $post->Programming_options = $Programming_options;
        $post->save();
        // Réponse JSON
        return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données',]);
    }
}    
    