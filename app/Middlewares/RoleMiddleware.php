<?php
namespace App\Middlewares;

use App\Helpers\AuthHelper;
use Exception;

class RoleMiddleware
{
    private $allowedRoles;

    public function __construct($allowedRoles = [])
    {
        $this->allowedRoles = (array) $allowedRoles;
    }

    public function handle()
    {
        if (empty($this->allowedRoles)) {
            return;
        }

        AuthHelper::checkRole($this->allowedRoles);
    }
}
