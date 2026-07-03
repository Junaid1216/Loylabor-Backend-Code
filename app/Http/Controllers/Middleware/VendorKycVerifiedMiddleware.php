<?php

namespace App\Http\Middleware;

use App\Exceptions\AccessPermissionDeniedException;
use Closure;
use Illuminate\Http\Request;
use Modules\KnowYourClient\app\Enums\KYCStatusEnum;
use Symfony\Component\HttpFoundation\Response;

class VendorKycVerifiedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            auth('web')->check()
            && optional(auth()->user())->seller
            && optional(auth()->user()->seller)->kyc
            && optional(auth()->user()->seller->kyc)->status == KYCStatusEnum::APPROVED
        ) {
            return $next($request);
        }

        throw new AccessPermissionDeniedException();
    }
}
