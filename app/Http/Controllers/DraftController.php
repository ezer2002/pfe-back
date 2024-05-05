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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DraftController extends Controller
{
    public function saveDraft(Request $request)
    {
        // Récupération des données de la requête
        $pageId = $request->input('page_id');
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        $message = $request->input('message') ?? '';
        $Programming_options = 'Brouillons'; 
        
        $post = new Post();
        if ($request->hasFile('media_path')) {
            $mediaFile = $request->file('media_path');
            $ext = $mediaFile->getClientOriginalExtension();
            $filename = time() . '.' . $ext;
            $mediaFile->move('uploads/', $filename);
            $post->media_path = 'uploads/' . $filename;
        }

        if ($request->hasFile('media_paths')) {
            $mediaPaths = [];
            $mediaFiles = $request->file('media_paths');
        
            foreach ($mediaFiles as $media) {
                $extension = $media->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $media->move('uploads/', $filename);
        
                $uploadedFilePath = 'uploads/' . $filename;
                $mediaPaths[] = $uploadedFilePath;
            }
        
            // Assurez-vous que $mediaPaths contient les chemins des fichiers correctement enregistrés
            $post->media_paths = json_encode($mediaPaths);
        
            return response()->json(['media_paths' => $mediaPaths]);
        }

        // Création et sauvegarde du post en tant que brouillon
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

        $post->access_token = $access_token;
        $post->message = $message;
        $post->Programming_options = $Programming_options;
        $post->save();

        return response()->json(['message' => 'Post sauvegardé en tant que brouillon']);
    }

}
