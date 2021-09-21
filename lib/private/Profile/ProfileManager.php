<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

namespace OC\Profile;

use function Safe\usort;
use InvalidArgumentException;
use OC\KnownUser\KnownUserService;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\App\IAppManager;
use OCP\IUser;
use OCP\Profile\IAction;
use OCP\Profile\IProfileManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * @inheritDoc
 */
class ProfileManager implements IProfileManager {

	/** @var IAccountManager */
	private $accountManager;

	/** @var IAppManager */
	private $appManager;

	/** @var ContainerInterface */
	private $container;

	/** @var KnownUserService */
	private $knownUserService;

	/** @var LoggerInterface */
	private $logger;

	/** @var IAction[] */
	private $actions = [];

	/** @var string[] */
	private $appActionQueue = [];

	public function __construct(
		IAccountManager $accountManager,
		IAppManager $appManager,
		ContainerInterface $container,
		KnownUserService $knownUserService,
		LoggerInterface $logger
	) {
		$this->accountManager = $accountManager;
		$this->appManager = $appManager;
		$this->container = $container;
		$this->knownUserService = $knownUserService;
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function queueAction(string $actionClass): void {
		$this->appActionQueue[] = $actionClass;
	}

	/**
	 * Register an action for the user
	 */
	private function registerAction(IUser $user, IAction $action): void {
		if ($action->getAppId() !== 'core' && !$this->appManager->isEnabledForUser($action->getAppId(), $user)) {
			$this->logger->error('App: ' . $action->getAppId() . ' is not enabled for the user: ' . $user->getUID());
		}

		if (array_key_exists($action->getId(), $this->actions)) {
			throw new InvalidArgumentException('Profile action with this id has already been registered: ' . $action->getId());
		}

		$action->preload($user);
		// Add action to associative array of actions with action ID as the key
		$this->actions[$action->getId()] = $action;
	}

	/**
	 * Load user actons
	 */
	private function loadActions(IUser $user, ?IUser $visitingUser): void {
		$allActionQueue = array_merge(IProfileManager::ACCOUNT_PROPERTY_ACTION_QUEUE, $this->appActionQueue);

		foreach ($allActionQueue as $actionClass) {
			try {
				/** @var IAction $action */
				$action = $this->container->get($actionClass);

				// Run checks if the action is an account property action
				if (in_array($action::class, IProfileManager::ACCOUNT_PROPERTY_ACTION_QUEUE, true)) {
					$account = $this->accountManager->getAccount($user);
					$property = $action->getId();
					$value = $account->getProperty($property)->getValue();

					// Only register action if property is set and visible to visiting user
					if (!empty($value) && $this->isPropertyVisible($user, $visitingUser, $property)) {
						try {
							$this->registerAction($user, $action);
						} catch (TypeError $e) {
							$this->logger->error(
								"$actionClass is not an IAction instance",
								[
									'exception' => $e,
								]
							);
						}
					}
				}

				try {
					$this->registerAction($user, $action);
				} catch (TypeError $e) {
					$this->logger->error(
						"$actionClass is not an IAction instance",
						[
							'exception' => $e,
						]
					);
				}
			} catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
				$this->logger->error(
					"Could not find profile action class: $actionClass",
					[
						'exception' => $e,
					]
				);
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getActions(IUser $user, ?IUser $visitingUser): array {
		$this->loadActions($user, $visitingUser);

		$actionsClone = $this->actions;
		// Sort associative array into indexed array in ascending order of priority
		usort($actionsClone, function (IAction $a, IAction $b) {
			return $a->getPriority() === $b->getPriority() ? 0 : ($a->getPriority() < $b->getPriority() ? -1 : 1);
		});
		return $actionsClone;
	}

	/**
	 * Returns whether the user account property is visible to the visiting user
	 * based on it's scope
	 */
	private function isPropertyVisible(IUser $user, ?IUser $visitingUser, string $property): bool {
		try {
			$account = $this->accountManager->getAccount($user);
			$scope = $account->getProperty($property)->getScope();

			// Users, guests, and public access (non-logged in) visitors may only view profiles on the same server
			// Handle scope so that properties are only visible to visiting users who are permitted
			// 1) Private   - hidden from public access and from unknown users
			// 2) Local     - hidden from nobody
			// 3) Federated - hidden from nobody
			// 4) Published - hidden from nobody
			switch ($scope) {
				case IAccountManager::SCOPE_PRIVATE:
					return $visitingUser !== null && $this->knownUserService->isKnownToUser($user->getUID(), $visitingUser->getUID());
				case IAccountManager::SCOPE_LOCAL:
					return true;
				case IAccountManager::SCOPE_FEDERATED:
					return true;
				case IAccountManager::SCOPE_PUBLISHED:
					return true;
				default:
					return false;
			}
		} catch (PropertyDoesNotExistException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getProfileParams(IUser $user, ?IUser $visitingUser): array {
		$account = $this->accountManager->getAccount($user);
		// Initialize associative array of profile parameters
		$profileParameters = [
			'userId' => $account->getUser()->getUID(),
		];

		// TODO add additional emails
		$additionalEmails = array_map(
			function (IAccountProperty $property) {
				return $property->getValue();
			},
			$account->getPropertyCollection(IAccountManager::COLLECTION_EMAIL)->getProperties(),
		);

		// Add account property parameters
		foreach (IProfileManager::PROFILE_PROPERTIES as $property) {
			$profileParameters[IProfileManager::PROFILE_PROPERTY_MAP[$property]] =
				$this->isPropertyVisible($user, $visitingUser, $property)
				// If empty string explicitly set to null
				? $account->getProperty($property)->getValue() || null
				: null;
		}

		// Add avatar visibility parameter
		$profileParameters['isUserAvatarVisible'] = $this->isPropertyVisible($user, $visitingUser, IAccountManager::PROPERTY_AVATAR);

		// Add actions paraemer
		$profileParameters['actions'] = array_map(
			function (IAction $action) {
				return [
					'id' => $action->getId(),
					'icon' => $action->getIcon(),
					'title' => $action->getTitle(),
					'label' => $action->getLabel(),
					'target' => $action->getTarget(),
				];
			},
			$this->getActions($user, $visitingUser),
		);

		return $profileParameters;
	}
}
