<?php
/**
 * @copyright Copyright (c) 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);


namespace OC\Core\Controller;

use EmailAction;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\UserStatus\IManager;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountProperty;
use OCP\App\IAppManager;
use OCP\IUserManager;
use OCP\Profile\IActionManager;

class ProfileController extends \OCP\AppFramework\Controller {

	/** @var IL10N */
	private $l10n;

	/** @var IUserSession */
	private $userSession;

	/** @var IUserManager */
	private $userManager;

	/** @var IAccountManager */
	private $accountManager;

	/** @var IInitialState */
	private $initialStateService;

	/** @var IAppManager */
	private $appManager;

	/** @var IManager */
	private $userStatusManager;

	/** @var IActionManager */
	// private $actionManager;

	public function __construct(
		$appName,
		IRequest $request,
		IL10N $l10n,
		IUserSession $userSession,
		IUserManager $userManager,
		IAccountManager $accountManager,
		IInitialState $initialStateService,
		IAppManager $appManager,
		IManager $userStatusManager,
		// IActionManager $actionManager
	) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->accountManager = $accountManager;
		$this->initialStateService = $initialStateService;
		$this->appManager = $appManager;
		$this->userStatusManager = $userStatusManager;
		// $this->actionManager = $actionManager;
	}

	public const PROPERTY_ACTIONS = [
		IAccountManager::PROPERTY_EMAIL,
		IAccountManager::PROPERTY_PHONE,
		IAccountManager::PROPERTY_WEBSITE,
		IAccountManager::PROPERTY_TWITTER,
	];

	/**
	 * @NoCSRFRequired
	 * @UseSession
	 * FIXME Public page annotation blocks the user session somehow
	 */
	public function index(string $userId = null): TemplateResponse {
		$isLoggedIn = $this->userSession->isLoggedIn();
		$account = $this->accountManager->getAccount($this->userManager->get($userId));

		$profileEnabled = filter_var(
			$account->getProperty(IAccountManager::PROPERTY_PROFILE_ENABLED)->getValue(),
			FILTER_VALIDATE_BOOLEAN,
			FILTER_NULL_ON_FAILURE,
		);

		// TODO empty profile page
		if (!$profileEnabled) {
			return new TemplateResponse(
				'core',
				'404-page',
				[],
				TemplateResponse::RENDER_AS_GUEST,
			);
		}

		$status = $this->userStatusManager->getUserStatuses([$userId]);
		$status = array_pop($status);

		if ($status) {
			$this->initialStateService->provideInitialState('status', [
				'icon' => $status->getIcon(),
				'message' => $status->getMessage(),
			]);
		}

		$this->initialStateService->provideInitialState('profileParameters', $this->getProfileParams($account));

		\OCP\Util::addScript('core', 'dist/profile');

		return new TemplateResponse(
			'core',
			'profile',
			[],
			($isLoggedIn ? TemplateResponse::RENDER_AS_USER : TemplateResponse::RENDER_AS_PUBLIC)
		);
	}

	/**
	 * returns the profile parameters in an
	 * associative array
	 *
	 * TODO handle federation scope permissions
	 */
	private function getProfileParams(IAccount $account): array {
		// for scope, if:
		// 1) Published - visible to everybody
		// 2) Federated - visible to users on trusted servers
		// 3) Local - visible to users and guests on same server instance
		// 4) Private - visible to users matched through phone number integration on Talk app

		$additionalEmails = array_map(
			function (IAccountProperty $property) {
				return [
					'value' => $property->getValue(),
					'scope' => $property->getScope(),
				];
			},
			$account->getPropertyCollection(IAccountManager::COLLECTION_EMAIL)->getProperties()
		);

		$profileParameters = [
			'userId' => $account->getUser()->getUID(),
			'displayName' => $account->getProperty(IAccountManager::PROPERTY_DISPLAYNAME)->getValue(),
			// 'additionalEmails' => $additionalEmails,
			'address' => $account->getProperty(IAccountManager::PROPERTY_ADDRESS)->getValue(),
			// Ordered by precedence, order is preserved in PHP and modern JavaScript
			'actionParameters' => [
				'talkEnabled' => $this->appManager->isEnabledForUser('spreed', $account->getUser()),
				'email' => $account->getProperty(IAccountManager::PROPERTY_EMAIL)->getValue(),
				'phoneNumber' => $account->getProperty(IAccountManager::PROPERTY_PHONE)->getValue(),
				'website' => $account->getProperty(IAccountManager::PROPERTY_WEBSITE)->getValue(),
				'twitterUsername' => $account->getProperty(IAccountManager::PROPERTY_TWITTER)->getValue(),
			],
		];

		return $profileParameters;
	}

	protected function initActions(IAccount $account) {
		foreach(self::PROPERTY_ACTIONS as $property) {
			$scope = $account->getProperty($property)->getScope();
			$value = $account->getProperty($property)->getValue();

			// TODO: handle talk verification
			if ($scope === IAccountManager::SCOPE_PRIVATE) {
				return;
			}

			// User is not logged in, we don't display the action
			if ($scope === IAccountManager::SCOPE_LOCAL && !$this->userSession->isLoggedIn()) {
				return;
			}

			// TODO: handle federation verification
			if ($scope === IAccountManager::SCOPE_FEDERATED && false) {
				return;
			}

			switch ($property) {
				case IAccountManager::PROPERTY_EMAIL:
					// $this->actionManager->registerAction(new EmailAction($value));
					break;

				default:
					break;
			}
		}
	}
}
