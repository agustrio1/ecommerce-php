<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Auth\Application\Services\CurrentUserService;

class DashboardController
{
    public function index(Request $request): Response
    {
        $user = CurrentUserService::user();

        if (! $user) {
            return Response::redirect('/login');
        }

        // Customer diarahkan ke storefront
        if (CurrentUserService::roleSlug() === 'customer') {
            return Response::redirect('/');
        }

        return Response::make(view('dashboard', [
            'title' => 'Dashboard',
        ]));
    }
}