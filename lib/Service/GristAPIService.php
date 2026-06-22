<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IntegrationGrist\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\IntegrationGrist\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Config\IUserConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service to make requests to Grist API
 */
class GristAPIService {

	private IClient $client;

	public function __construct(
		private UtilsService $utilsService,
		private LoggerInterface $logger,
		private IL10N $l10n,
		private IUserConfig $userConfig,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
		private IManager $notificationManager,
		IClientService $clientService,
	) {
		$this->client = $clientService->newClient();
	}



	public function listOrgs(string $userId) {
		$orgs = $this->request($userId, 'orgs');
		return $orgs;
	}

	public function listWorkspacesPerOrg(string $userId, int $orgId) {
		$workspaces = $this->request($userId, 'orgs/' . $orgId . '/workspaces');
		return $workspaces;

	}


	/**
	 * Make an authenticated HTTP request to Grist
	 * @param string|null $userId
	 * @param string $endPoint The API path to reach
	 * @param array $params Query parameters (key/val pairs)
	 * @param string $method HTTP query method
	 * @param int $timeout
	 * @return array decoded request result or error
	 */
	public function request(?string $userId, string $endPoint, array $params = [], string $method = 'GET',
		int $timeout = 30): array {
		try {
			$url = $this->userConfig->getValueString($userId, Application::APP_ID, 'url') . 'api/' . $endPoint;
			$options = [
				'timeout' => $timeout,
				'headers' => [
					'User-Agent' => 'Nextcloud Grist integration',
				],
			];

			$accessToken = $this->utilsService->getEncryptedUserValueString($userId, 'token');
			if ($accessToken !== '') {
				$options['headers']['Authorization'] = 'Bearer ' . $accessToken;
			}

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['body'] = json_encode($params);
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} elseif ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} elseif ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true) ?: [];
			}
		} catch (ClientException|ServerException $e) {
			$responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			if ($e->getResponse()->getStatusCode() === 404) {
				// Only log inaccessible grist links as debug
				$this->logger->debug('Grist API client or server error', ['response_body' => $responseBody, 'exception' => $e]);
			} else {
				$this->logger->warning('Grist API client or server error', ['response_body' => $responseBody, 'exception' => $e]);
			}
			return [
				'error' => $e->getMessage(),
				'body' => $parsedResponseBody,
			];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Grist API request error', ['exception' => $e]);
			return ['error' => $e->getMessage()];
		}
	}
}
