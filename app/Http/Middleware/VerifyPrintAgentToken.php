<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPrintAgentToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $agentToken = $request->header('X-Agent-Secret-Key') ?? $request->query('agent_key');
        $expectedKey = config('services.print_agent.secret_key', env('PRINT_AGENT_SECRET_KEY'));

        // If an agent secret key is configured in the environment, enforce it
        if ($expectedKey && (! $agentToken || ! hash_equals((string) $expectedKey, (string) $agentToken))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Print Agent Request. Valid X-Agent-Secret-Key header is required.',
            ], 401);
        }

        return $next($request);
    }
}
