<?php

namespace Aanugerah\WeddingPro\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MidtransCspMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        \Illuminate\Support\Facades\Log::info('[CSP] Applying Midtrans CSP Header to: ' . $request->fullUrl());
        $response = $next($request);

        // Aggressively clear ALL possible CSP header variations
        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('X-Content-Security-Policy');
        $response->headers->remove('X-WebKit-CSP');
        $response->headers->remove('content-security-policy');
        $response->headers->remove('x-content-security-policy');

        // Refined CSP: THE NUCLEAR OPTION (Extremely broad for debugging)
        $csp = "default-src * 'unsafe-inline' 'unsafe-eval' data: blob: https:; ";
        $csp .= "script-src * 'unsafe-inline' 'unsafe-eval' 'unsafe-hashes' data: blob: https:; ";
        $csp .= "script-src-elem * 'unsafe-inline' 'unsafe-eval' data: blob: https:; ";
        $csp .= "script-src-attr * 'unsafe-inline' 'unsafe-eval' data: blob: https:; ";
        $csp .= "connect-src * https:; ";
        $csp .= "img-src * data: blob: https:; ";
        $csp .= "style-src * 'unsafe-inline' https:; ";
        $csp .= "font-src * data: https:; ";
        $csp .= "frame-src * https:; ";
        $csp .= "child-src * https:; ";
        $csp .= "worker-src * blob:; ";
        $csp .= "object-src 'none'; ";

        // Force set the header
        $response->headers->set('Content-Security-Policy', $csp, true);
        
        // Log ALL final headers to verify what the browser actually sees
        \Illuminate\Support\Facades\Log::info('[CSP] FINAL RESPONSE HEADERS: ', $response->headers->all());

        return $response;
    }
}
