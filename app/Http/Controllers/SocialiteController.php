<?php

namespace App\Http\Controllers;

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

class SocialiteController extends Controller
{
    public function handleGraphInteraction(Request $request)
    {
        // Récupération des données de la requête
        $pageId = $request->input('page_id');
        $message = $request->input('message')  ??'';
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

            // Extraction du social_id du post publié
            $postData = $response->json();
            $socialId = $postData['id'];
        }







        if ($request->hasFile('media_paths')) {
            $mediaPaths = $request->file('media_paths');

            $uploadedFilesPaths = [];

            foreach ($mediaPaths as $media) {
                $extension = $media->getClientOriginalExtension();
                $filename = time() . '_' . Str::random(5) . '.' . $extension;
                $media->move('uploads/', $filename);

                $uploadedFilesPaths[] = 'uploads/' . $filename;
            }

            $post->media_paths = json_encode($uploadedFilesPaths);
        }

        // if ($request->hasFile('media_paths')) {
        //     // Traitement des médias pour l'album
        //     $mediaPaths = [];
        //     foreach ($request->file('media_paths') as $mediaFile) {
        //         $ext = $mediaFile->getClientOriginalExtension();
        //         $filename = time() . '_' . Str::random(5) . '.' . $ext;
        //         $mediaFile->move('uploads/', $filename);
        //         $mediaPaths[] = 'uploads/' . $filename;
        //     }

        //     // Création de l'album sur Facebook
        //     // $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/albums", [
        //     //     'message' => $message, // Ajoutez un message si nécessaire
        //     //     'access_token' => $access_token,
        //     // ]);

        //     // Vérification des erreurs de requête
        //     // if ($response->failed()) {
        //     //     // Extraire les détails de l'erreur
        //     //     $errorDetails = $response->body();

        //     //     // Journaliser l'erreur
        //     //     Log::error('Erreur lors de la publication de l\'album sur Facebook: ' . $errorDetails);

        //     //     // Retourner un message d'erreur détaillé
        //     //     return response()->json(['error' => 'Échec de la publication de l\'album sur Facebook. Détails : ' . $errorDetails], 500);
        //     // }

        //     // Extraction du social_id du post publié
        //     $albumData = $response->json();
        //     $socialId = $albumData['id'];

        //     // Publication des photos dans l'album
        //     // foreach ($mediaPaths as $mediaPath) {
        //     //     $response = Http::attach(
        //     //         'source',
        //     //         fopen($mediaPath, 'r'),
        //     //         'file.' . $ext
        //     //     )->post("https://graph.facebook.com/v17.0/{$socialId}/photos", [
        //     //         'message' => '', // Ajoutez un message par photo si nécessaire
        //     //         'access_token' => $access_token,
        //     //     ]);

        //     //     // Vérification des erreurs de requête
        //     //     if ($response->failed()) {
        //     //         // Gestion de l'erreur de publication des photos dans l'album
        //     //         return response()->json(['error' => 'Erreur lors de la publication des photos dans l\'album sur Facebook'], 500);
        //     //     }
        //     // }

        //     // Enregistrement des médias et du message dans la base de données
        //     $post->media_paths = json_encode($mediaPaths);

        //     /*
        //     //khidma li9dima
        //     $mediaPaths = $request->file('media_paths');
        //     $uploadedFilesUrls = []; // Tableau d'URL des photos hébergées en ligne
        //     $albumCreated = false;
        //     $albumId = null;

        //     foreach ($mediaPaths as $index => $media) {
        //         $extension = $media->getClientOriginalExtension();
        //         $filename = time() . '_' . Str::random(5) . '.' . $extension;
        //         $media->storePubliclyAs('uploads/', $filename); // Stockage public des photos
        //         $uploadedFilesUrls[] = url('storage/uploads/' . $filename); // Construire l'URL publique

        //         // Si c'est la première photo, nous créons l'album
        //         if ($index === 0) {
        //             $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/albums", [
        //             'access_token' => $access_token,
        //             'name' => $message, // Facultatif : nom de l'album
        //             'message' => $message, // Facultatif : description de l'album
        //             ]);

        //             if ($response->failed()) {
        //                 Log::error("Failed to create Facebook album: {$response->body()}");
        //                 return response()->json(['error' => 'Échec de la création de l\'album sur Facebook'], 500);
        //             }

        //             $albumId = $response->json()['id']; // Récupérer l'ID de l'album créé
        //             $albumCreated = true;
        //         }

        //         // Publication de la photo dans l'album
        //         if ($albumCreated) {
        //             $response = Http::post("https://graph.facebook.com/v17.0/{$albumId}/photos", [
        //             'access_token' => $access_token,
        //             'url' => $uploadedFilesUrls[$index],
        //             ]);

        //             if ($response->failed()) {
        //                 Log::error("Failed to publish photo to album: {$response->body()}");
        //                 return response()->json(['error' => 'Échec de la publication de la photo dans l\'album'], 500);
        //             }
        //         }
        //     }

        //     $post->media_paths = json_encode($uploadedFilesUrls);*/
        // }

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

        $response = Http::get("https://graph.facebook.com/v17.0/{$pageId}?fields=name&access_token={$access_token}");

        if ($response->failed()) {
            // Gérer l'erreur si la requête échoue
            $post->page_id = $pageId; // Affecter l'ID de la Page en cas d'erreur
        } else {
            $pageData = $response->json();
            $pageName = $pageData['name'];
            $post->page_name = $pageName;
        }
        $post->message = $message;
        $post->access_token = $access_token;
        $post->Programming_options = $Programming_options;
        $post->save();
        // Réponse JSON
        //return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données',]);

        // Retourner le social_id comme réponse JSON
        return response()->json(['social_id' => $socialId]);
        // Réponse JSON
        //return response()->json(['message' => 'Album publié sur la page Facebook et enregistré dans la base de données']);
    }
}

