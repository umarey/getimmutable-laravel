<?php

namespace GetImmutable\Middleware;

use Closure;
use GetImmutable\RequestContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAuditContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(RequestContext::class);

        $user = $request->user();

        if ($user) {
            $context->actorId = (string) $user->getAuthIdentifier();
            $context->actorName = $user->name ?? null;
            $context->actorType = method_exists($user, 'getAuditActorType')
                ? $user->getAuditActorType()
                : 'user';
        }

        $context->ipAddress = $request->ip();
        $context->userAgent = $request->userAgent();

        try {
            $context->sessionId = session()->getId();
        } catch (\Throwable) {
            // Session may not be available.
        }

        return $next($request);
    }
}
