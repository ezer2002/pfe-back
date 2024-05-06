<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PostSchedulerController extends Controller
{
        //protected $providers = ["facebook"];

    public function schedulePost(Request $request)
    {
        $pageId = $request->input('page_id');
        $message = $request->input('message') ?? '';
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        $scheduledDateTime = $request->input('scheduled_datetime');

        // Validation des entrées
        $validator = Validator::make($request->all(), [
            'page_id' => 'required',
            'message' => 'nullable',
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
            // **Gestion des médias**
            $post = new Post();
            $mediaUploaded = false;

            if ($request->hasFile('media_path')) {
                $mediaFile = $request->file('media_path');

                $ext = $mediaFile->getClientOriginalExtension();
                $filename = time() . '.' . $ext;
                $mediaFile->move('uploads/', $filename);

                $post->media_path = 'uploads/' . $filename;
                $mediaUploaded = true;

                if ($mediaFile->getClientMimeType() == 'video/mp4') {
                    $response = Http::attach(
                        'source',
                        fopen('uploads/' . $filename, 'r'),
                        'file.' . $ext
                    )->post("https://graph.facebook.com/v17.0/{$pageId}/videos", [
                        'description' => $message,
                        'access_token' => $access_token,
                        'published' => false,
                        'scheduled_publish_time' => strtotime($scheduledDateTime),
                    ]);
                } else {
                    $response = Http::attach(
                        'source',
                        fopen('uploads/' . $filename, 'r'),
                        'file.' . $ext
                    )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                        'message' => $message,
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

            // Conversion de la date planifiée en timestamp
            $scheduledDateTime = Carbon::parse($scheduledDateTime);

            $now = Carbon::now();
            $nowTime=$now->copy()->addHour();


            if($scheduledDateTime ->diffInMinutes($nowTime)<10|| $scheduledDateTime->diffInDays($nowTime) < 30 )
            {
                // **Gestion des médias**
                $post = new Post();
                $mediaUploaded = false;

                if ($request->hasFile('media_path')) {
                    $mediaFile = $request->file('media_path');

                    $ext = $mediaFile->getClientOriginalExtension();
                    $filename = time() . '.' . $ext;
                    $mediaFile->move('uploads/', $filename);

                    $post->media_path = 'uploads/' . $filename;
                    $mediaUploaded = true;

                    if ($mediaFile->getClientMimeType() == 'video/mp4') {
                        $response = Http::attach(
                            'source',
                            fopen('uploads/' . $filename, 'r'),
                            'file.' . $ext
                        )->post("https://graph.facebook.com/v17.0/{$pageId}/videos", [
                            'description' => $message,
                            'access_token' => $access_token,
                            'published' => false,
                            'scheduled_publish_time' => strtotime($scheduledDateTime),
                        ]);
                    } else {
                        $response = Http::attach(
                            'source',
                            fopen('uploads/' . $filename, 'r'),
                            'file.' . $ext
                        )->post("https://graph.facebook.com/v17.0/{$pageId}/photos", [
                            'message' => $message,
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

                if ($request->hasFile('media_paths')) {
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
                }

                // **Publication de message uniquement**
                if (!$mediaUploaded && !empty($message)) {
                    // Publication sur Facebook
                    $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/feed", [
                        'message' => $message,
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
            $post->scheduledDateTime = $scheduledDateTime;
            $post->access_token = $access_token;
            $post->Programming_options = 'Programmée';
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
}
