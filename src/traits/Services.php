<?php

namespace contentreactor\jwt\traits;

use contentreactor\jwt\services\AuthService;
use contentreactor\jwt\services\TokenService;

trait Services
{
	/**
     * Returns the AuthService instance.
     *
     * @return AuthService The AuthService instance.
     */
	public function getAuth(): AuthService
	{
		return $this->get('authService');
	}

	/**
     * Returns the TokenService instance.
     *
     * @return TokenService The TokenService instance.
     */
	public function getToken(): TokenService
	{
		return $this->get('tokenService');
	}
}
