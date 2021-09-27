<?php
/**
 * @copyright 2021 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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
namespace OCA\DAV\CalDAV;

use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarProvider;
use OCP\Constants;

class CalendarProvider implements ICalendarProvider {

	/** @var CalDavBackend */
	private $calDavBackend;

	/**
	 * @param CalDavBackend $calDavBackend
	 */
	public function __construct(CalDavBackend $calDavBackend) {
		$this->calDavBackend = $calDavBackend;
	}

	public function getCalendars(string $principalUri): array {
		return $this->calDavBackend->getCalendarsForUser($principalUri); // this is wrong I think - again with the user context?! What does UserManager do?
	}
}
