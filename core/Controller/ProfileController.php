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

use \OCP\AppFramework\Controller;
use OC\KnownUser\KnownUserService;
use OC\Profile\Actions\EmailAction;
use OC\Profile\Actions\PhoneAction;
use OC\Profile\Actions\TalkAction;
use OC\Profile\Actions\TwitterAction;
use OC\Profile\Actions\WebsiteAction;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Profile\IActionManager;
use OCP\Profile\IProfileAction;
use OCP\UserStatus\IManager as IUserStatusManager;

class ProfileController extends Controller {

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

	/** @var IUserStatusManager */
	private $userStatusManager;

	/** @var IActionManager */
	private $actionManager;

	/** @var KnownUserService */
	private $knownUserService;

	public function __construct(
		$appName,
		IRequest $request,
		IAccountManager $accountManager,
		IActionManager $actionManager,
		IAppManager $appManager,
		IInitialState $initialStateService,
		IUserManager $userManager,
		IUserSession $userSession,
		IUserStatusManager $userStatusManager,
		KnownUserService $knownUserService
	) {
		parent::__construct($appName, $request);
		$this->accountManager = $accountManager;
		$this->actionManager = $actionManager;
		$this->appManager = $appManager;
		$this->initialStateService = $initialStateService;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->userStatusManager = $userStatusManager;
		$this->knownUserService = $knownUserService;
	}

	public const PROFILE_DISPLAY_PROPERTIES = [
		IAccountManager::PROPERTY_DISPLAYNAME,
		IAccountManager::PROPERTY_ADDRESS,
		IAccountManager::PROPERTY_ORGANISATION,
		IAccountManager::PROPERTY_ROLE,
		IAccountManager::PROPERTY_HEADLINE,
		IAccountManager::PROPERTY_BIOGRAPHY,
	];

	/**
	 * Map of account properties to camelCase variants
	 */
	public const PROFILE_DISPLAY_PROPERTY_JSON_MAP = [
		IAccountManager::PROPERTY_DISPLAYNAME => 'displayName',
		IAccountManager::PROPERTY_ADDRESS => 'address',
		IAccountManager::PROPERTY_ORGANISATION => 'organisation',
		IAccountManager::PROPERTY_ROLE => 'role',
		IAccountManager::PROPERTY_HEADLINE => 'headline',
		IAccountManager::PROPERTY_BIOGRAPHY => 'biography',
	];

	public const PROFILE_ACTION_PROPERTIES = [
		IAccountManager::PROPERTY_EMAIL,
		IAccountManager::PROPERTY_PHONE,
		IAccountManager::PROPERTY_WEBSITE,
		IAccountManager::PROPERTY_TWITTER,
	];

	/**
	 * @PublicPage
	 * @UseSession
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 */
	public function index(string $userId): TemplateResponse {
		if (!$this->userManager->userExists($userId)) {
			return new TemplateResponse(
				'core',
				'404-page',
				[],
				TemplateResponse::RENDER_AS_GUEST,
			);
		}

		$user = $this->userManager->get($userId);
		$account = $this->accountManager->getAccount($user);
		$profileEnabled = filter_var(
			$account->getProperty(IAccountManager::PROPERTY_PROFILE_ENABLED)->getValue(),
			FILTER_VALIDATE_BOOLEAN,
			FILTER_NULL_ON_FAILURE,
		);

		if (!$profileEnabled) {
			return new TemplateResponse(
				'core',
				'404-page',
				[],
				TemplateResponse::RENDER_AS_GUEST,
			);
		}

		$status = array_shift($this->userStatusManager->getUserStatuses([$userId]));
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
			$this->userSession->isLoggedIn() ? TemplateResponse::RENDER_AS_USER : TemplateResponse::RENDER_AS_PUBLIC
		);
	}

	/**
	 * returns the profile parameters in an
	 * associative array
	 */
	private function getProfileParams(IAccount $account): array {
		$visitingUser = $this->userSession->getUser();
		$user = $account->getUser();
		$isLoggedIn = $this->userSession->isLoggedIn();

		$additionalEmails = array_map(
			function (IAccountProperty $property) {
				return $property->getValue();
			},
			$account->getPropertyCollection(IAccountManager::COLLECTION_EMAIL)->getProperties()
		);

		$profileParameters = [
			'userId' => $account->getUser()->getUID(),
		];

		// Only show property info to permitted visiting users
		// 1) Private   - hidden from public access and from unknown users
		// 2) Local     - hidden from nobody
		// 3) Federated - hidden from nobody
		// 4) Published - hidden from nobody
		foreach (self::PROFILE_DISPLAY_PROPERTIES as $property) {
			$scope = $account->getProperty($property)->getScope();

			switch ($scope) {
				case IAccountManager::SCOPE_PRIVATE:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] =
						($isLoggedIn && $visitingUser !== null && $this->knownUserService->isKnownToUser($user->getUID(), $visitingUser->getUID()))
						? $account->getProperty($property)->getValue()
						: null;
					break;
				case IAccountManager::SCOPE_LOCAL:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] = $account->getProperty($property)->getValue();
					break;
				case IAccountManager::SCOPE_FEDERATED:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] = $account->getProperty($property)->getValue();
					break;
				case IAccountManager::SCOPE_PUBLISHED:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] = $account->getProperty($property)->getValue();
					break;
				default:
					$profileParameters[self::PROFILE_DISPLAY_PROPERTY_JSON_MAP[$property]] = null;
					break;
			}
		}

		$avatarScope = $account->getProperty(IAccountManager::PROPERTY_AVATAR)->getScope();

		switch ($avatarScope) {
			case IAccountManager::SCOPE_PRIVATE:
				$profileParameters['isAvatarDisplayed'] = ($isLoggedIn && $visitingUser !== null && $this->knownUserService->isKnownToUser($user->getUID(), $visitingUser->getUID()));
				break;
			case IAccountManager::SCOPE_LOCAL:
				$profileParameters['isAvatarDisplayed'] = true;
				break;
			case IAccountManager::SCOPE_FEDERATED:
				$profileParameters['isAvatarDisplayed'] = true;
				break;
			case IAccountManager::SCOPE_PUBLISHED:
				$profileParameters['isAvatarDisplayed'] = true;
				break;
			default:
				$profileParameters['isAvatarDisplayed'] = false;
				break;
		}

		$profileParameters['actionParameters'] = $this->initActions($account);

		return $profileParameters;
	}

	protected function initActions(IAccount $account) {
		$isLoggedIn = $this->userSession->isLoggedIn();
		$userId = $account->getUser()->getUID();
		$talkEnabled = $this->appManager->isEnabledForUser('spreed', $account->getUser());
		// $talkEnabled = false;

		if ($talkEnabled) {
			$this->actionManager->registerAction(TalkAction::class, $userId);
		}

		foreach (self::PROFILE_ACTION_PROPERTIES as $property) {
			$scope = $account->getProperty($property)->getScope();
			$value = $account->getProperty($property)->getValue();

			// The other less strict scopes all allow public link access
			if ($scope === IAccountManager::SCOPE_PRIVATE && !$isLoggedIn) {
				continue;
			}

			if (!empty($value)) {
				switch ($property) {
					case IAccountManager::PROPERTY_EMAIL:
						$this->actionManager->registerAction(EmailAction::class, $value);
						break;
					case IAccountManager::PROPERTY_PHONE:
						$this->actionManager->registerAction(PhoneAction::class, $value);
						break;
					case IAccountManager::PROPERTY_WEBSITE:
						$this->actionManager->registerAction(WebsiteAction::class, $value);
						break;
					case IAccountManager::PROPERTY_TWITTER:
						$this->actionManager->registerAction(TwitterAction::class, $value);
						break;
					default:
						break;
				}
			}
		}

		return array_map(
			function (IProfileAction $action) {
				return [
					'name' => $action->getName(),
					'icon' => $action->getIcon(),
					'title' => $action->getTitle(),
					'label' => $action->getLabel(),
					'target' => $action->getTarget(),
				];
			},
			$this->actionManager->getActions()
		);
	}
}
