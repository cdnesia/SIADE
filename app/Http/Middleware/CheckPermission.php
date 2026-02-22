<?php

namespace App\Http\Middleware;

use App\Services\DataService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    protected $service;

    public function __construct(DataService $service)
    {
        $this->service = $service;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role = null): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $routeName = Route::currentRouteName();

        if (!$user->can($routeName)) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        return $next($request);
    }
}
