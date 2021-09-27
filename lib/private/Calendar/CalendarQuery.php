<?php
/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OC\Calendar;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarProvider;

class CalendarQuery implements \OCP\Calendar\ICalendarQuery {

	/** @var array */
	public $searchProperties;

	/** @var string */
	private $principalUri;

	/** @var string */
	private $pattern;
	private $offset;
	private $options;
	private $limit;

	public function __construct(string $principalUri = '', array $searchProperties = [], string $pattern = '') {
		$this->searchProperties = $searchProperties;
		$this->principalUri = $principalUri;
		$this->pattern = $pattern;
	}

	public function getPattern(): string {
		return $this->pattern;
	}

	public function setPattern(string $pattern): void {
		$this->pattern = $pattern;
	}

	public function getSearchProperties(): array {
		return $this->searchProperties;
	}

	public function setSearchProperty(string $value): void {
		$this->searchProperties[] = $value;
	}

	public function getOptions(): array {
		return $this->options;
	}

	public function getLimit(): int {
		return $this->limit;
	}

	public function getOffset() {
		return $this->offset;
	}

	public function searchAttendee(): void {
		$this->searchProperties[] = 'ATTENDEE';
	}

	public function searchOrganizer(): void {
		$this->searchProperties[] = 'ORGANIZER';

	}

	public function searchSummary(): void {
		$this->searchProperties[] = 'SUMMARY';
	}

	public function searchAppointment(): void {
		$this->searchProperties[] = 'X-NC-APPOINTMENT';
	}

	public function setTimerangeStart(\DateTime $startTime): void {
		$this->options['timerange']['start'] = $startTime;
	}

	public function setTimerangeEnd(\DateTime $endTime): void {
		$this->options['timerange']['end'] = $endTime;
	}
}
