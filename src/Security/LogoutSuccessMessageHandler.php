<?php

namespace App\Security;

use App\Utils\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessMessageHandler implements LogoutSuccessHandlerInterface
{
	/** @var Utils */
	private $utils;

	/**
	 * @param Utils $utils
	 */
	public function __construct(Utils $utils)
	{
		$this->utils = $utils;
	}

	/**
	 * Creates a Response object to send upon a successful logout.
	 *
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function onLogoutSuccess(Request $request)
	{
		return $this->utils->formatResponseApi(Response::HTTP_OK, 'success', 'logout_successfully');
	}
}