<?php

namespace App\Http\Controllers;

use \OpenAI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class SpeechToTextController extends Controller
{
  public function transcribe(Request $request)
  {
      // Validate the request
      $validator = Validator::make($request->all(), [
          'audio' => 'required|file|mimes:audio/mpeg,mpga,mp3,wav,aac',
      ]);

      if ($validator->fails()) {
          return response()->json(['errors' => $validator->errors()], 422);
      }

      // Save the audio file
      $audioFile = $request->file('audio');
      $storedFilename = time() . '_' . $audioFile->getClientOriginalName();
      $storagePath = Storage::disk('local')->putFileAs('audio', $audioFile, $storedFilename);

      /// Instantiate Guzzle HTTP client
      $client = new Client();

      // OpenAI API key
      $apiKey = config('openai.api-key');

      // Read the audio file
      $audioContent = base64_encode(file_get_contents(Storage::path($storagePath)));

      // Prepare the request headers and payload
      $headers = [
          'Authorization' => "Bearer {$apiKey}",
          'Content-Type' => 'application/json',
      ];

      $payload = [
          'model' => 'whisper-1',
          'audio' => $audioContent,
      ];

      $openAiApiUrl = 'https://api.openai.com/v1/whisper';
      // Make the API request and handle the response
      try {
        // Post request to OpenAI API
        $response = $client->post($openAiApiUrl, [
          'headers' => $headers,
          'json' => $payload,
      ]);

        // Decode the response
        $transcriptionResult = json_decode($response->getBody()->getContents(), true);

        // Check if the transcription was successful
        if (isset($transcriptionResult['data']['text'])) {
          $transcribedText = $transcriptionResult['data']['text'];

          // Delete the temporary file
          Storage::disk('local')->delete($storagePath);

          // Return the transcription
          return response()->json([
              'transcription' => $transcribedText
          ], 200);
        } else {
          // Handle cases where the 'text' field is not set in the response
          \Log::error('Transcription text not found in the response');
          return response()->json(['error' => 'Transcription text not found'], 500);
        }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
          // Handle Guzzle-specific exceptions
          \Log::error($e->getMessage());
          return response()->json(['error' => 'Transcription failed with GuzzleException'], 500);
        } catch (\Exception $e) {
            // Handle general exceptions
            \Log::error($e->getMessage());
            return response()->json(['error' => 'Transcription failed'], 500);
        } finally {
            // Ensure the temporary file is deleted even if there's an exception
            Storage::disk('local')->delete($storagePath);
        }
  }
  /*public function audioTranscribe(Request $request, $type = null){
        
    $audioFile = $request->file('audio_file');
    $filename = $audioFile->store('audio_files');
    $filepath = Storage::path($filename);
    
    if(!$type){
        $result = OpenAI::client(config('openai.api-key'))->files()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($filepath, 'r'),
            'response_format' => 'text', // verbose-json, srt, text
        ]);
    }else{
        $result = OpenAI::client(config('openai.api-key'))->files()->translate([
            'model' => 'whisper-1',
            'file' => fopen($filepath, 'r'),
        ]);
    }

    echo 'TRANSCRIBE : ' . $result->text . PHP_EOL;
    
    return response()->json([
        'transcription' => $result->text
    ], 200);
}*/


}
