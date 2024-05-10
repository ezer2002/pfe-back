<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;





class PostDeleteController extends Controller
{
    public function deletePost(Request $request)
    {
        // Validez la demande pour s'assurer que l'ID du post à supprimer est fourni
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Récupérez le post à supprimer
        $post = Post::find($request->input('id'));

        // Vérifiez si le post est un brouillon
        if ($post->Programming_options === 'saved as draft') {
            $post->delete();

            return response()->json(['message' => 'Post deleted successfully']);
        } else {
            return response()->json(['error' => 'Post is not a brouillon, cannot delete'], 400);
        }
    }
}
