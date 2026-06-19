<?php

namespace OCA\IntegrationGrist\Settings;

use OCA\IntegrationGrist\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Config\IUserConfig;

use OCP\Security\ICrypto;
use OCP\Settings\ISettings;

class Personal implements ISettings {

	public function __construct(
		private IUserConfig $userConfig,
		private IInitialState $initialStateService,
		private ICrypto $crypto,
		private ?string $userId,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {

		$token = $this->userConfig->getValueString($this->userId, Application::APP_ID, 'token');
		$url = $this->userConfig->getValueString($this->userId, Application::APP_ID, 'url');

		$initialUserConfig = [
			// don't expose the token to the user
			'token' => $token === '' ? '' : 'dummyToken',
			'url' => $url,
		];
		$this->initialStateService->provideInitialState('user-config', $initialUserConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
