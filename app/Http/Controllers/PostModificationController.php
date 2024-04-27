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

class PostModificationController extends Controller
{
    public function modifyPost(Request $request)
    {
        $pageId = $request->input('page_id');
        $access_token = "EAADutQr9i3MBO7pDQYZAcGyhfAaRyA3PHOVL4JP07vLKJa57CocgMWgKESNZB5vjuN1RksK7MZAf6b0l0JzrA9T45zpthhtjFgq1g3ZBWyS06lSbSjxrSp54YfDmbeTt0SJuGEVZAvByILMNio4mIEoIZCp0tuEUfrpUxubL2I5mQAZAxHZAorNE7wK7ZCIFlk54ZD";
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'id' => 'required', // Local Post ID
            'social_id' => 'required', // Facebook Post ID
            'message' => 'required', // New message content
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Retrieve the post from the database
        $post = Post::find($request->input('id'));

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Update the post on Facebook
        $response = Http::post("https://graph.facebook.com/v17.0/{$pageId}/{$request->input('social_id')}", [
            'message' => $request->input('message'),
            'access_token' => $post->access_token,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to update post on Facebook'], 500);
        }

        // Update the post in the local database
        $post->message = $request->input('message');
        $post->save();

        // Return the updated post's social_id
        return response()->json(['social_id' => $post->social_id]);
    }
}