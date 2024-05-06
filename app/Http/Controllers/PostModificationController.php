<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
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
        $access_token = "EAAFHMS35xfQBOwVE5tY4kd3FBawj7FSqTlBXwNV5kb5Jnt4h8VnLaN9ZCw15sDATxTPc4jC8p7D2EZBSRZCrfuOItJUdxSKt0ZBKA7oLS5T8NirPhJwnuxsZAWWVg61BADZBSSB2DXE2mcnyNk9agGPo3qqnlMjohFhbz5qZCk8PaNkVwm0RtgeZCNH986AweZAJXlAjFpYCH4OZAyda0me2GttZCtpnrPcRSKpMMNKPaEZD";

        // Validate the request
        $validator = Validator::make($request->all(), [
            'id' => 'required', // Local Post ID
            'page_id' => 'required',
            'Programming_options' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Retrieve the post from the database
        $post = Post::find($request->input('id'));

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Check the Programming_options for different actions
        switch ($request->input('Programming_options')) {
            case 'Brouillon':
                // Process message modification
                if ($request->has('message')) {
                    $post->message = $request->input('message');
                    $post->save();
                }

                // Process media_path update if a file is provided
                if ($request->hasFile('media_path')) {
                    $mediaFile = $request->file('media_path');
                    $ext = $mediaFile->getClientOriginalExtension();
                    $filename = time() . '.' . $ext;
                    $mediaFile->move('uploads/', $filename);

                    // Update the media path in the database
                    $post->media_path = 'uploads/' . $filename;
                    $post->save();
                }

                $post->page_id = $request->input('page_id');
                $post->save();

                return response()->json(['message' => 'Successfully edited post']);
                break;

            case 'publier':

                // Update the Programming_options and other fields accordingly
                return response()->json(['message' => 'Successfully published post']);
                break;

            case 'programmée':

                // Update the Programming_options and other fields accordingly
                return response()->json(['message' => 'Successfully scheduled post']);
                break;

            default:
                return response()->json(['error' => 'Invalid request'], 400);
                break;
        }
    }
}
