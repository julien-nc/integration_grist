<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\IntegrationGrist\Controller;

use OCA\IntegrationGrist\AppInfo\Application;
use OCA\IntegrationGrist\Service\UtilsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Config\IUserConfig;
use OCP\IRequest;

class GristController extends Controller {

	public function __construct(
		string $AppName,
		IRequest $request,
		private IUserConfig $userConfig,
		private UtilsService $utilsService,
		private ?string $userId,
	) {
		parent::__construct($AppName, $request);
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/info')]
	public function getGristInfo(): DataResponse {
		if ($this->userId === null) {
			return new DataResponse(['error' => 'no user in context'], Http::STATUS_UNAUTHORIZED);
		}
		$token = $this->utilsService->getEncryptedUserValueString($this->userId, 'token');
		$isConnected = ($token !== '');
		return new DataResponse([
			'connected' => $isConnected,
		]);
	}

	/**
	 * Set config values
	 *
	 * @param array<string, string> $values
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/config')]
	public function setConfig(array $values): DataResponse {
		if ($this->userId === null) {
			return new DataResponse(['error' => 'no user in context'], Http::STATUS_UNAUTHORIZED);
		}
		foreach ($values as $key => $value) {
			if ($key === 'token' && $value !== '') {
				$this->utilsService->setEncryptedUserValueString($this->userId, $key, trim($value));
			} 
			else {
				$this->userConfig->setValueString($this->userId, Application::APP_ID, $key, trim($value, " /\n\r\t\v\x00") . '/');
			}
		}

		return new DataResponse([]);
	}
}
