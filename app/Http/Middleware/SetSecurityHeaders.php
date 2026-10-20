<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // A signed-in page must not outlive the session. Laravel's default
        // "no-cache" still lets the browser keep its own copy, so pressing
        // Back after logging out showed the last page again — dashboard,
        // participant lists and all — without asking the server. "no-store"
        // makes Back re-request the page, and the auth middleware answers
        // that with the login screen.
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        // Stops another site loading these pages in a hidden frame to trick
        // a click (clickjacking), stops the browser guessing a response's
        // type from its contents, and keeps full URLs out of the Referer
        // header sent to other sites.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
