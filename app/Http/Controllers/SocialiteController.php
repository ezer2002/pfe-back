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

        if ($request->hasFile('media_path')) {
            $file = $request->file('media_path');
            if ($file->getClientMimeType() == 'video/mp4') {
                $filename = Str::random(32) . "." . $file->getClientOriginalExtension();
                $file->move('uploads/videos/', $filename);

                $response = Http::attach(
                    'source',
                    fopen(public_path('uploads/videos/' . $filename), 'r'),
                    $filename
                )->post("https://graph.facebook.com/v17.0/{$pageId}/videos", [
                    'message' => $message,
                    'access_token' => $access_token,
                ]);
            } else {
                $ext = $file->getClientOriginalExtension();
                $filename = time() . '.' . $ext;
                $file->move('uploads/', $filename);

                $response = Http::attach(
                    'source',
                    fopen(public_path('uploads/' . $filename), 'r'),
                    $filename
                )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                    'message' => $message,
                    'access_token' => $access_token,
                ]);
            }

            if ($response->failed()) {
                return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
            }

            $post = new Post();
            $post->page_id = $pageId;
            $post->message = $message;
            $post->media_path = $file->getClientMimeType() == 'video/mp4' ? 'uploads/videos/' . $filename : 'uploads/' . $filename;
            $post->access_token = $access_token;
            $post->save();
        }else {
            // Publication de message seulement si aucun média n'est présent
            if ($message != '') {
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

                // Enregistrement dans la base de données
                $post = new Post();
                $post->page_id = $pageId;
                $post->message = $message;
                $post->access_token = $access_token;
                $post->save();
            }
        }

        // Réponse JSON
        return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données',]);
    }
}

/*
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use Illuminate\Http\Request;
use Socialite;
use App\User;
use Illuminate\Support\Facades\Http;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class SocialiteController extends Controller
{
    // Les tableaux des providers autorisés
    protected $providers = ["facebook"];

    public function handleGraphInteraction(Request $request)
    {
        // Récupération des données de la requête
        $pageId = $request->input('page_id');
        $message = $request->input('message') ?? ''; // Valeur par défaut vide si le message n'est pas fourni
        $access_token = "EAADutQr9i3MBOxZCzeFhofvhPEB26xkspYAItSlZC6IqMZBC9KsDYj2yhInXIeCbHG9WQfSDQ230hYipyq2ivLRk61I31E33xLfgfbl3StpZB0ZCRrMeMSctKkUXPjmCClF3qW0aZC1oZBU1ORiAAz8ZAKYZBXtr00Y5VikiJ8SBSS83eeZA4T8j0wWq4BQcTxd4oZD";
        
        if ($request->hasFile('media_path')) {
            $mediaFiles = $request->file('media_path');
        
            foreach ($mediaFiles as $file) {
                $ext = $file->getClientOriginalExtension();
                $filename = time() . '.' . $ext;
                $file->move('uploads/about/', $filename);
        
                // Publication sur Facebook
                $response = Http::attach(
                    'source',
                    fopen('uploads/about/' . $filename, 'r'),
                    'file.' . $ext
                )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                    'message' => $message,
                    'access_token' => $access_token,
                ]);
        
                // Vérification des erreurs de requête
                if ($response->failed()) {
                    return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
                }
        
                // Enregistrement dans la base de données
                $post = new Post();
                $post->page_id = $pageId;
                $post->message = $message;
                $post->media_path = 'uploads/about/' . $filename;
                $post->access_token = $access_token;
                $post->save();
            }
        }else {
            // Publication de message seulement si aucun média n'est présent
            if ($message != '') {
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

                // Enregistrement dans la base de données
                $post = new Post();
                $post->page_id = $pageId;
                $post->message = $message;
                $post->access_token = $access_token;
                $post->save();
            }
        }

        // Réponse JSON
        return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données',]);
    }
}
*/