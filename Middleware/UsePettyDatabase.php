<?php

namespace App\Modules\PettyCash\Middleware;

use App\Modules\PettyCash\Support\PettyDatabase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsePettyDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        PettyDatabase::activate();

        return $next($request);
    }
}

