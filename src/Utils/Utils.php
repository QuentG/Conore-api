<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\JsonResponse;

class Utils
{
	/**
	 * @param string $httpCode
	 * @param string $status
	 * @param string $message
	 * @param array $data
	 *
	 * @return JsonResponse
	 */
	public function formatResponseApi(string $httpCode, string $status, string $message, $data = [])
	{
		$response = ['status' => $status, 'message' => $message, 'data' => $data];

		return new JsonResponse($response, $httpCode);
	}

	/**
	 * @param $email
	 *
	 * @return bool
	 */
	public function validate($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * @param $timestamp
	 *
	 * @return bool
	 */
	public function checkDate($timestamp)
	{
		$now = (new \DateTime())->getTimestamp();

		return $timestamp > $now;
	}

}