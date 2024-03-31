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
        $message = $request->input('message') ?? '';
        $Programming_options = 'Brouillons'; 
        
        $post = new Post();
        if ($request->hasFile('media_path')) {
            $mediaFile = $request->file('media_path');
            $ext = $mediaFile->getClientOriginalExtension();
            $filename = time() . '.' . $ext;
            $mediaFile->move('uploads/about/', $filename);
            $post->media_path = 'uploads/about/' . $filename;
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

        // Création et sauvegarde du post en tant que brouillon
        $post->page_id = $pageId;
        $post->message = $message;
        $post->Programming_options = $Programming_options;
        $post->save();

        return response()->json(['message' => 'Post sauvegardé en tant que brouillon']);
    }

}
