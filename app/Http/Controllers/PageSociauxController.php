<?php

namespace App\Http\Controllers;

use App\Models\PageSociauxModel;
use Illuminate\Http\Request;

class PageSociauxController extends Controller
{
   // Méthode pour ajouter une nouvelle entrée
   public function store(Request $request)
   {

       $nouvelleEntree = new PageSociauxModel();
       $nouvelleEntree->page_name = $request->input('page_name');
       $nouvelleEntree->page_id = $request->input('page_id');
       $nouvelleEntree->access_token = $request->input('access_token');
       $nouvelleEntree->user_id = $request->input('user_id');

       $nouvelleEntree->save();


       return response()->json(['message' => 'done'], 200);
    }

public function getUserPages(Request $request)
{    $userId = $request->query('user_id'); // Récupérer l'ID de l'utilisateur depuis la requête GET

    $userPages = PageSociauxModel::where('user_id', $userId)->get();
    return response()->json($userPages);
}


public function destroy($id)
{
    $page = PageSociauxModel::findOrFail($id);
    $page->delete();

    return response()->json(['message' => 'Page deleted successfully'], 200);
}
}
