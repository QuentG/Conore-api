<?php

namespace App\ApiConnector;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class FirebaseConnector
{
	private const API_SEND_URL = 'https://fcm.googleapis.com/fcm/send';

	/** @var Client|null $client */
	private $client;
	/** @var string $apiKey */
	private $apiKey;

	/**
	 * @param string $apiKey
	 */
	public function __construct(string $apiKey)
	{
		$this->apiKey = $apiKey;
	}

	/**
	 * @return Client
	 */
	private function getClient()
	{
		if ($this->client) {
			return $this->client;
		}

		$this->client = new Client([
			'headers' => [
				'Authorization' => 'key=' . $this->apiKey,
				'Content-Type' => 'application/json',
			],
			'http_errors' => false,
		]);

		return $this->client;
	}

	/**
	 * @param array $data
	 * @param bool $debug
	 *
	 * @return array
	 */
	public function send(array $data, $debug = false)
	{
		$tokens = $data['registration_ids'];
		/** @var Response|null $response */
		$response = null;
		$attemptCount = 0;

		// Retry call handling
		do {
			// After 3 attempts, we stop. Server is not available.
			if ($attemptCount > 3) {
				throw new \LogicException('Firebase Response HTTP code 5xx : ' . $response->getReasonPhrase());
				// Sleep before retrying. "Application servers must implement exponential back-off."
			} elseif ($attemptCount > 0) {
				$sleepTime = pow(2, ($attemptCount - 1));

				if ($response->hasHeader('Retry-After')) {
					$sleepTime = $response->getHeader('Retry-After');
				}
				sleep($sleepTime);
			}

			$response = $this->doSend($data);
			++$attemptCount;
		} while ($this->isTimeoutError($response));

		if (400 === $response->getStatusCode()) {
			throw new \LogicException('Firebase Response HTTP code 400 : ' . $response->getBody()->__toString());
		} elseif (401 === $response->getStatusCode()) {
			throw new \LogicException('Firebase Response HTTP code 401 : ' . $response->getReasonPhrase());
		}

		$decodedResponse = \GuzzleHttp\json_decode($response->getBody(), true);
		if ($debug) {
			return $decodedResponse;
		}

		if (!empty($decodedResponse['error'])) {
			throw new \LogicException('Firebase Response HTTP code 200, error not handled : ' . \GuzzleHttp\json_encode($decodedResponse['error']));
		}

		$result = [
			'batch_internal_id' => $decodedResponse['multicast_id'],
			'error' => [],
		];

		// Index in results are the same than $data['registration_ids'] sent in request.
		foreach ($decodedResponse['results'] as $index => $individualSent) {
			if (!empty($individualSent['error'])) {
				$result['error'][] = $tokens[$index];
				$indexError = count($result['error']) - 1;
				$result['original-response'][$indexError] = $individualSent;
			}
		}

		return $result;
	}

	/**
	 * @param ResponseInterface $response
	 *
	 * @return bool
	 */
	private function isTimeoutError(ResponseInterface $response)
	{
		if ('5' == substr((string)$response->getStatusCode(), 0, 1)) {
			return true;
		}

		if (200 === $response->getStatusCode()) {
			$result = \GuzzleHttp\json_decode($response->getBody(), true);
			if (!empty($result['error']) && in_array($result['error'], ['Unavailable', 'InternalServerError'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $data
	 *
	 * @return mixed|ResponseInterface
	 */
	private function doSend(array $data)
	{
		return $this->getClient()->request('POST', self::API_SEND_URL, [
			'json' => $data,
		]);

	}
}