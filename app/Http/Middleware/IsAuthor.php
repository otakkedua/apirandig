<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAuthor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $author = auth('admin')->user();

        if (!$author || $author->role !== 'author') {
            return response()->json(['message' => 'Access denied: Author only'], 403);
        }

        return $next($request);
    }
}
