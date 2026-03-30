<?php

namespace App\Http\Middleware;

use App\Support\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $roles = collect($roles)
            ->flatMap(function (string $role) {
                return $role === 'employee' ? Role::employeeRoles() : [$role];
            })
            ->unique()
            ->values()
            ->all();

        if (! $user->hasRole($roles)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
