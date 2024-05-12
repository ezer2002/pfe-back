<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (!Auth::User()) {
        //     // Si l'utilisateur n'est pas un administrateur, vous pouvez renvoyer une réponse non autorisée
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }

        // // Si l'utilisateur est un administrateur, passez à la prochaine étape de la requête
        return $next($request);
    }
}
