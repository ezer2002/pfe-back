<?php

namespace App\Http\Controllers;
use Google\Cloud\Language\LanguageClient;
use Illuminate\Http\Request;
use \OpenAI;
use Illuminate\View\View;

class AiController extends Controller
{
    public function index(
        Request $request,
    ) {
        $openAIClient = OpenAI::client(config('openai.api-key'));
        $profileContent = $request->get('content');

        $profile = $openAIClient
            ->completions()
            ->create([
                "model" => "gpt-3.5-turbo-instruct",
                "temperature" => 0.7,
                "top_p" => 1,
                "frequency_penalty" => 0,
                "presence_penalty" => 0,
                'max_tokens' => 600,
                'prompt' => "regénérer le texte suivant {$profileContent}",
            ]);

        return  response()->json([

            'data' =>     trim($profile->choices[0]->text),
          ],200);


    }
}
//AIzaSyDbZfo6PXSwShSLC238n-nt0Hk0x-3svks
