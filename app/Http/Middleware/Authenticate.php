<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Pour les requêtes API (JSON), ne pas rediriger vers une route web inexistante
        if ($request->expectsJson() || $request->is('api/*')) {
            return null; // renverra un 401 JSON
        }
        return null; // Pas de route web 'login' dans ce projet API-only
    }
}
