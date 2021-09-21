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

namespace OCP\Profile;

use OC\Profile\Actions\EmailAction;
use OC\Profile\Actions\PhoneAction;
use OC\Profile\Actions\TwitterAction;
use OC\Profile\Actions\WebsiteAction;
use OCP\Accounts\IAccountManager;
use OCP\IUser;

/**
 * @since 23.0.0
 */
interface IProfileManager {

	/**
	 * Array of account properties displayed on the profile
	 *
	 * @since 23.0.0
	 */
	public const PROFILE_PROPERTIES = [
		IAccountManager::PROPERTY_DISPLAYNAME,
		IAccountManager::PROPERTY_ADDRESS,
		IAccountManager::PROPERTY_ORGANISATION,
		IAccountManager::PROPERTY_ROLE,
		IAccountManager::PROPERTY_HEADLINE,
		IAccountManager::PROPERTY_BIOGRAPHY,
	];

	/**
	 * Map of account properties to camelCase variants
	 *
	 * @since 23.0.0
	 */
	public const PROFILE_PROPERTY_MAP = [
		IAccountManager::PROPERTY_DISPLAYNAME => 'displayName',
		IAccountManager::PROPERTY_ADDRESS => 'address',
		IAccountManager::PROPERTY_ORGANISATION => 'organisation',
		IAccountManager::PROPERTY_ROLE => 'role',
		IAccountManager::PROPERTY_HEADLINE => 'headline',
		IAccountManager::PROPERTY_BIOGRAPHY => 'biography',
	];

	/**
	 * Array of account property action classes
	 *
	 * @since 23.0.0
	 */
	public const ACCOUNT_PROPERTY_ACTION_QUEUE = [
		EmailAction::class,
		PhoneAction::class,
		WebsiteAction::class,
		TwitterAction::class,
	];

	/**
	 * Queue an action for registration
	 *
	 * @since 23.0.0
	 */
	public function queueAction(string $actionClass): void;

	/**
	 * Returns an array of registered profile actions for the user
	 *
	 * @return IAction[]
	 *
	 * @since 23.0.0
	 */
	public function getActions(IUser $user, ?IUser $visitingUser): array;

	/**
	 * Returns the profile parameters in an
	 * associative array
	 *
	 * @since 23.0.0
	 */
	public function getProfileParams(IUser $user, ?IUser $visitingUser): array;
}
