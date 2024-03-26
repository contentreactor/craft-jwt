<?php

namespace contentreactor\jwt\traits;

use contentreactor\jwt\services\AuthService;
use contentreactor\jwt\services\TokenService;

trait Services
{
	public function getAuth(): AuthService
	{
		return $this->get('authService');
	}

	public function getToken(): TokenService
	{
		return $this->get('tokenService');
	}
}
