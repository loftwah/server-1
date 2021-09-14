<?php

/**
 * @copyright Copyright (c) 2021 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

use OCP\Profile\IActionManager;
use Psr\Container\ContainerInterface;

/**
 * @since 23
 */
class ActionManager implements IActionManager {

	/** @var ContainerInterface */
	private $container;

	public function __construct(
		ContainerInterface $container
	) {
		$this->container = $container;
	}

	/** @var array */
	protected $actions = [];

	/**
	 * @inheritDoc
	 */
	public function registerAction(string $action, string $value): void {
		$action = $this->container->get($action);
		$action->setValue($value);
		$this->actions[] = $action;
	}

	/**
	 * @inheritDoc
	 */
	public function getActions(): array {
		$actionsCopy = $this->actions;
		usort($actionsCopy, function ($a, $b) {
			return $a->getPriority() < $b->getPriority() ? -1 : 1;
		});
		return $actionsCopy;
	}
}
