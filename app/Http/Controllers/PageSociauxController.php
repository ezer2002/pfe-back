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
    public function getAll()
{
    // Récupérer tous les enregistrements de votre modèle
    $records = PageSociauxModel::all();

    // Retourner les enregistrements récupérés
    return response()->json($records);
}



public function destroy($id)
{
    $page = PageSociauxModel::findOrFail($id);
    $page->delete();

    return response()->json(['message' => 'Page deleted successfully'], 200);
}
}
