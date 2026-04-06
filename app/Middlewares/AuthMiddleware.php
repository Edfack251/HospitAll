<?php
namespace App\Middlewares;

use App\Helpers\AuthHelper;

class AuthMiddleware
{
    public function handle()
    {
        AuthHelper::requireLogin();
    }
}
