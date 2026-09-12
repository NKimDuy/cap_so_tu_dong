<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Cho phép truy cập nếu người dùng có một trong các vai trò được liệt kê.
     * Dùng: ->middleware('role:quan_ly,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Tài khoản của bạn không tồn tại hoặc đã bị khóa.');
        }

        $userRole = $user->role instanceof UserRole ? $user->role->value : $user->role;

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
