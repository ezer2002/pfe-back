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
        $message = $request->input('message');
        $access_token = "EAADutQr9i3MBO3NZB2FMK04Nk6m6vNysT9boZBvHZABKLqd5H4StLY9YsNfZBCtHzPos2FbDOyZASbhChaonDEOnzVIZBdy1xgwwaLiZCT4n0hebAcZAjBQ6nKZBmZBRg1ZACXeqTxUZCgKnu69xxrE5ZCb7gPJojz4l7lH4kOAqbQx7I0HPYxF7AtGaGNgPLjaxCyq0ZD";
        //$mediaPath = $request->input('media_path');
        

        if($request->hasFile('media_path')){
            $file=$request->file('media_path');
            $ext=$file->getClientOriginalExtension();
            $filename=time().'.'.$ext;
            $file->move('uploads/about/',$filename);

               $post = new Post();
         $post->page_id = $pageId;
         $post->message = $message;
         $post->media_path = 'uploads/about/'.$filename;

         $post->access_token = $access_token;
         //$post->post_id = $data['id'];
         $post->save();
         $response = Http::attach(
            'source',
            file_get_contents('uploads/about/'.$filename),
            'file.jpg'
        )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
            'message' => $message,
            'access_token' => $access_token,
        ]);
 if ($response->failed()) {
            // Gérer l'erreur de requête
            return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
        }

          }
            return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données',]);

          

       // Publication sur Facebook
        // $response = Http::attach(
        //     'source',
        //     file_get_contents($mediaPath),
        //     'file.jpg'
        // )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
        //     'message' => $message,
        //     'access_token' => $access_token,
        // ]);

        // Vérification des erreurs de requête
        // if ($response->failed()) {
        //     // Gérer l'erreur de requête
        //     return response()->json(['error' => 'Échec de la publication sur la page Facebook'], 500);
        // }

        //  // Traitement de la réponse de Facebook
        //  $data = $response->json();

        //  // Enregistrement dans la base de données
        //  $post = new Post();
        //  $post->page_id = $pageId;
        //  $post->message = $message;
        // // $post->media_path = $mediaPath;

        //  $post->access_token = $access_token;
        //  $post->post_id = $data['id'];
        //  $post->save();


        // Réponse JSON
       // return response()->json(['message' => 'Publié sur la page Facebook et enregistré dans la base de données', 'post_id' => $data['id']]);

   
    }

  


}

