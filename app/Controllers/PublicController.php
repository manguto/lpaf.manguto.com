<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class PublicController extends Controller
{
    public function home(Request $request): void
    {
        $this->view('public/home');
    }
    public function app(Request $request): void
    {
        $this->view('app/dashboard', ['user' => $this->user()]);
    }
}
