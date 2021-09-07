<!--
  - @copyright Copyright (c) 2021 Christopher Ng <chrng8@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="profile">
		<div class="profile__header">
			<div class="profile__header__container">
				<div class="profile__header__container-placeholder" />
				<h2 class="profile__header__container-displayname">
					{{ displayName }}
					<button v-if="isCurrentUser"
						class="primary profile__header__container-button"
						@click="openSettings">
						{{ t('core', 'Edit profile') }}
					</button>
				</h2>
				<div v-if="status.icon || status.message"
					class="profile__header__container-status"
					@click.prevent.stop="openStatusModal">
					{{ status.icon }} {{ status.message }}
				</div>
			</div>
		</div>

		<div class="profile__content">
			<div class="profile__sidebar">
				<Avatar
					:user="userId"
					:size="180"
					:show-user-status="true"
					:show-user-status-compact="false"
					:disable-menu="true"
					:disable-tooltip="true"
					@click.native.prevent.stop="openStatusModal" />

				<div class="user-actions">
					<Actions v-if="primaryAction"
						:key="primaryAction.icon"
						class="user-actions__primary"
						:primary="true"
						:default-icon="primaryAction.icon"
						:menu-title="primaryAction.label"
						:force-menu="false">
						<ActionButton
							:close-after-click="true"
							:icon="primaryAction.icon"
							:title="primaryAction.label"
							@click="primaryAction.cb">
							{{ primaryAction.label }}
						</ActionButton>
					</Actions>
					<div class="user-actions__other">
						<Actions v-for="action in allActions.slice(1, 4)"
							:key="action.icon"
							:default-icon="action.icon"
							:menu-title="action.label">
							<ActionButton
								:close-after-click="true"
								:icon="action.icon"
								:title="action.label"
								@click="action.cb">
								{{ action.label }}
							</ActionButton>
						</Actions>
						<template v-if="otherActions">
							<Actions v-for="action in otherActions"
								:key="action.icon"
								:force-menu="true">
								<ActionButton
									:close-after-click="true"
									:icon="action.icon"
									@click="action.cb">
									{{ action.label }}
								</ActionButton>
							</Actions>
						</template>
					</div>
				</div>
			</div>

			<div class="profile__blocks">
				<div class="profile__blocks-details">
					<div v-if="company || jobTitle" class="detail">
						<p>{{ company && `${company} •` }} {{ jobTitle }}</p>
					</div>
					<div v-if="address" class="detail">
						<p>
							<MapMarkerIcon
								decorative
								title=""
								:size="16" />
							{{ address }}
						</p>
					</div>
				</div>
				<div class="profile__blocks-headline">
					<h3>{{ headline || 'Your headline here!' }}</h3>
				</div>
				<div class="profile__blocks-biography">
					<p>{{ biography || 'Add your biography!' }}</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'

import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
// import Modal from '@nextcloud/vue/dist/Components/Modal'
// import ProfileSettings from './ProfileSettings'
// import PhoneIcon from 'vue-material-design-icons/Phone'
import MapMarkerIcon from 'vue-material-design-icons/MapMarker'

const { userId, displayName, address, actionParameters } = loadState('core', 'profileParameters', {})
const status = loadState('core', 'status', {})

export default {
	name: 'Profile',

	components: {
		Avatar,
		Actions,
		ActionButton,
		// PhoneIcon,
		MapMarkerIcon,
	},

	data() {
		return {
			userId,
			displayName,
			address,
			status,
			company: 'Dunder Mifflin Paper Company',
			jobTitle: 'Sales Representative',
			headline: 'This is my headline!',
			biography: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
		}
	},

	computed: {
		isCurrentUser() {
			return getCurrentUser().uid === this.userId
		},

		primaryAction() {
			if (this.allActions.length) {
				return this.allActions[0]
			}
			return null
		},

		allActions() {
			const actionMap = {
				talkEnabled: {
					icon: 'icon-talk',
					label: `Talk to ${this.displayName}`,
					cb: this.openTalk,
				},
				email: {
					icon: 'icon-mail',
					label: `Email ${actionParameters.email}`,
					cb: this.sendEmail,
				},
				phoneNumber: {
					icon: 'icon-phone',
					label: `Call phone number (${actionParameters.phoneNumber})`,
					cb: this.callPhone,
				},
				website: {
					icon: 'icon-timezone',
					label: `Visit website (${actionParameters.website})`,
					cb: this.openWebsite,
				},
				twitterUsername: {
					icon: 'icon-twitter',
					label: `View Twitter profile ${actionParameters.twitterUsername[0] === '@' ? actionParameters.twitterUsername : `@${actionParameters.twitterUsername}`}`,
					cb: this.openTwitterProfile,
				},
			}

			return Object.entries(actionParameters)
				.filter(([_, value]) => value)
				.map(([key, _]) => actionMap[key])
		},

		otherActions() {
			if (this.allActions.slice(4).length) {
				return this.allActions.slice(4)
			}
			return null
		},
	},

	mounted() {
		subscribe('user_status:status.updated', this.handleStatusUpdate)
	},

	beforeDestroy() {
		unsubscribe('user_status:status.updated', this.handleStatusUpdate)
	},

	methods: {
		handleStatusUpdate(status) {
			this.status = status
		},

		openStatusModal() {
			const statusMenuItem = document.querySelector('.user-status-menu-item__toggle')
			if (statusMenuItem) {
				statusMenuItem.click()
			}
		},

		openSettings() {
			location.href = generateUrl('/settings/user')
		},

		sendEmail() {
			location.href = `mailto:${actionParameters.email}`
		},

		openWebsite() {
			window.open(actionParameters.website, '_blank')
		},

		openTalk() {
			location.href = generateUrl('apps/spreed?callUser={userId}', { userId })
		},

		callPhone() {
			location.href = `tel:${actionParameters.phoneNumber}`
		},

		openTwitterProfile() {
			window.open(`https://twitter.com/${actionParameters.twitterUsername}`, '_blank')
		},
	},
}
</script>

<style lang="scss">
// Override header styles
#header {
	background-color: transparent !important;
	background-image: none !important;
}

#content {
	padding-top: 0px;
}
</style>

<style lang="scss" scoped>
$profile-max-width: 1024px;
$content-max-width: 640px;

.profile {
	width: 100%;

	&__header {
		position: sticky;
		height: 190px;
		top: -40px;
		background-image: linear-gradient(40deg, var(--color-primary-gradient-dark) 0%, var(--color-primary-gradient-light) 100%);

		&__container {
			align-self: flex-end;
			width: 100%;
			max-width: $profile-max-width;
			margin: 0 auto;
			display: grid;
			grid-template-rows: max-content max-content;
			grid-template-columns: 230px 1fr;
			justify-content: center;

			&-placeholder {
				grid-row: 1 / 3;
			}

			&-displayname, &-status {
				color: var(--color-primary-text);
			}

			&-displayname {
				width: $content-max-width;
				margin-top: 132px;
				// Overrides the global style declaration
				margin-bottom: 0;
				font-size: 26px;
				display: flex;
				align-items: center;
				cursor: text;

				&:not(:last-child) {
					margin-top: 100px;
				}
			}

			&-button {
				border: 1px solid var(--color-primary-text);
				margin-left: 10px;
				margin-top: 10px;
			}

			&-status {
				width: max-content;
				max-width: $content-max-width;
				cursor: pointer;
				padding: 5px 10px;
				margin-left: -14px;

				&:hover {
					background-color: var(--color-main-background);
					color: var(--color-primary);
					border-radius: var(--border-radius-pill);
					font-weight: bold;
					box-shadow: 0 3px 6px var(--color-box-shadow);
				}
			}
		}
	}

	&__sidebar {
		position: sticky;
		top: var(--header-height);
		align-self: flex-start;
		padding-top: 20px;
		min-width: 220px;
		margin: -150px 10px 0 0;

		&::v-deep .avatardiv, h2 {
			text-align: center;
			margin: auto;
			display: block;
			padding: 8px;
		}

		// TODO remove specificity hack
		&::v-deep .avatardiv:not(.avatardiv--unknown) {
			background-color: var(--color-main-background) !important;
			box-shadow: none;
		}

		&::v-deep .avatardiv {
			.avatardiv__user-status {
				right: 14px;
				bottom: 14px;
				width: 34px;
				height: 34px;
				background-size: 28px;
				border: none;
				cursor: pointer;
				// Styles when custom status icon and status text are set
				background-color: var(--color-main-background);
				line-height: 34px;
				font-size: 20px;

				&:hover {
					box-shadow: 0 3px 6px var(--color-box-shadow);
				}
			}
		}
	}

	&__content {
		max-width: $profile-max-width;
		margin: 0 auto;
		display: flex;
		width: 100%;
	}

	&__blocks {
		margin: 10px 0 80px 0;
		display: grid;
		gap: 16px 0;
		max-width: $content-max-width;

		&-details {
			display: flex;
			flex-direction: column;
			gap: 2px 0;

			.detail {
				display: inline-block;
				color: #555;

				& span {
					display: inline-block;
					vertical-align: middle;
				}
			}
		}

		&-headline {
			margin-top: 8px;

			h3 {
				font-weight: bold;
				font-size: 20px;
				margin: 0;
			}
		}

		h3, p {
			cursor: text;
		}
	}
}

.user-info {
	li {
		display: flex;
		background-position: left;
		margin-left: 0;
		padding: 3px;
		padding-left: 30px;
		opacity: .8;
		span {
			flex-grow: 1;
			padding-top: 11px;
		}
	}
}

.user-actions {
	display: flex;
	flex-direction: column;

	&__other {
		display: flex;
		justify-content: center;
		gap: 0 5px;
	}
}

.icon-twitter {
	background-image: url("data:image/svg+xml,%3Csvg width='32' height='32' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M21.61 4.62h.16c1.77 0 3.37.75 4.5 1.94 1.4-.27 2.71-.79 3.9-1.5a6.17 6.17 0 01-2.7 3.41C28.7 8.32 29.9 8 31 7.5a12.47 12.47 0 01-3.07 3.19l.02.8c0 8.13-6.2 17.51-17.52 17.51-3.47 0-6.7-1.02-9.43-2.77a12.46 12.46 0 009.11-2.54 6.16 6.16 0 01-5.75-4.28 6.1 6.1 0 002.78-.1 6.16 6.16 0 01-4.93-6.04v-.08c.83.46 1.78.74 2.78.77a6.15 6.15 0 01-1.9-8.22 17.48 17.48 0 0012.69 6.44 6.16 6.16 0 015.83-7.56z'/%3E%3C/svg%3E");
	background-size: 16px;
}

.content-block {
	.header {
		font-weight: bold;
		color: var(--color-primary-element-light);
	}
}

.photo-grid {
	width: 100%;
	display: grid;
	grid-template-columns: 200px 200px 200px;
	grid-template-rows: 200px 200px;
	gap: 5px;

	div {
		width: 200px;
		height: 200px;
		background-color: hsl(201, 67%, 94%);
		border-radius: 8px;
	}
}
</style>
