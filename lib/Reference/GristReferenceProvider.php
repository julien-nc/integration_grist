<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IntegrationGrist\Reference;

use DateTime;
use Exception;
use OC\Collaboration\Reference\ReferenceManager;
use OCA\IntegrationGrist\AppInfo\Application;
use OCA\IntegrationGrist\Service\GristAPIService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\Config\IUserConfig;
use OCP\IL10N;

require_once __DIR__ . '/../../vendor/autoload.php';
use OCP\IURLGenerator;
use Throwable;

class GristReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_issue_pr';

	public function __construct(
		private GristAPIService $gristAPIService,
		private IUserConfig $userConfig,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ReferenceManager $referenceManager,
		private ?string $userId,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		error_log("GET ID");
		return 'grist-documents';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Grist documents');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedSearchProviderIds(): array {
		return ['grist-search-documents'];
	}


	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		$url = $this->userConfig->getValueString($this->userId, Application::APP_ID, 'url');
		error_log("MATCHING");
		error_log($referenceText);
		error_log('/^' . preg_quote($url, '/') . '[0-9a-zA-Z]+\/.+/');
		error_log(json_encode(preg_match('/^' . preg_quote($url, '/') . '[0-9a-zA-Z]+\/.+/', $referenceText) === 1));
		return preg_match('/^' . preg_quote($url, '/') . '[0-9a-zA-Z]+\/.+/', $referenceText) === 1;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		error_log("RESOLVING");
		if ($this->matchReference($referenceText)) {
			$reference = new Reference($referenceText);
			$reference->setTitle('ref title');
			return $reference;
		}
		return null;
	}

	/**
	 * We use the userId here because when connecting/disconnecting from the Grist account,
	 * we want to invalidate all the user cache and this is only possible with the cache prefix
	 * @inheritDoc
	 */
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	/**
	 * We don't use the userId here but rather a reference unique id
	 * @inheritDoc
	 */
	public function getCacheKey(string $referenceId): ?string {

		return $referenceId;
	}

	/**
	 * @param string $userId
	 * @return void
	 */
	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
