<?php

declare(strict_types=1);

namespace OCA\IntegrationGrist\Service;

use Exception;
use OCA\IntegrationGrist\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\Security\ICrypto;

class UtilsService {
	/**
	 * Service providing storage, circles and tags tools
	 */
	public function __construct(
		string $appName,
		private IUserConfig $userConfig,
		private ICrypto $crypto,
	) {
	}

	/**
	 * Get decrypted user value
	 *
	 * @param string $userId
	 * @param string $key
	 * @return string
	 * @throws Exception
	 */
	public function getEncryptedUserValueString(string $userId, string $key): string {
		$storedValue = $this->userConfig->getValueString($userId, Application::APP_ID, $key);
		if ($storedValue === '') {
			return '';
		}
		return $this->crypto->decrypt($storedValue);
	}

	/**
	 * Store encrypted user secret
	 *
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function setEncryptedUserValueString(string $userId, string $key, string $value): void {
		if ($value === '') {
			$this->userConfig->setValueString($userId, Application::APP_ID, $key, '');
		} else {
			$encryptedUserSecret = $this->crypto->encrypt($value);
			$this->userConfig->setValueString($userId, Application::APP_ID, $key, $encryptedUserSecret);
		}
	}
}
