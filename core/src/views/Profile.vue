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
				<div class="profile__header__container__placeholder" />
				<h2 class="profile__header__container__displayname">
					{{ displayName }}
					<button v-if="isCurrentUser"
						class="primary profile__header__container__button"
						@click="openSettings">
						{{ t('core', 'Edit profile') }}
					</button>
				</h2>
				<div v-if="status.icon || status.message"
					class="profile__header__container__status"
					@click.prevent.stop="openStatusModal">
					{{ status.icon }} {{ status.message }}
				</div>
			</div>
		</div>

		<div class="profile__content">
			<div class="profile__sidebar">
				<Avatar
					class="avatar"
					:user="userId"
					:size="180"
					:show-user-status="true"
					:show-user-status-compact="false"
					:disable-menu="true"
					:disable-tooltip="true"
					@click.native.prevent.stop="openStatusModal" />

				<div class="user-actions">
					<PrimaryActionButton v-if="primaryAction"
						class="user-actions__primary"
						:href="primaryAction.target"
						:icon="primaryAction.icon">
						{{ primaryAction.title }}
					</PrimaryActionButton>
					<div class="user-actions__other">
						<Actions v-for="action in allActions.slice(1, 4)"
							:key="action.icon"
							:default-icon="action.icon"
							:menu-title="action.title">
							<ActionLink
								:close-after-click="true"
								:icon="action.icon"
								:href="action.target"
								:target="action.name === 'phone' ? '_self' :'_blank'"
								:title="action.title">
								{{ action.title }}
							</ActionLink>
						</Actions>
						<template v-if="otherActions">
							<Actions v-for="action in otherActions"
								:key="action.icon"
								:force-menu="true">
								<ActionLink
									:close-after-click="true"
									:href="action.target"
									:target="action.name === 'phone' ? '_self' :'_blank'"
									:icon="action.icon">
									{{ action.title }}
								</ActionLink>
							</Actions>
						</template>
					</div>
				</div>
			</div>

			<div class="profile__blocks">
				<div class="profile__blocks-details">
					<div v-if="company || jobTitle" class="detail">
						<p>{{ company }} {{ (company && jobTitle) && '•' }} {{ jobTitle }}</p>
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
				<template v-if="headline || biography">
					<div class="profile__blocks-headline">
						<h3>{{ headline || t('core', 'Your headline will appear here') }}</h3>
					</div>
					<div class="profile__blocks-biography">
						<p>{{ biography || t('core', 'Your biography will appear here') }}</p>
					</div>
				</template>
				<template v-else>
					<div class="profile__blocks-empty-info">
						<AccountIcon
							decorative
							title=""
							fill-color="#4D4D4D"
							:size="60" />
						<h3>{{ displayName }} {{ t('core', 'hasn\'t added any info yet') }}</h3>
						<p>Headline and biography will show up here</p>
					</div>
				</template>
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
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
// import Modal from '@nextcloud/vue/dist/Components/Modal'
// import ProfileSettings from './ProfileSettings'
// import PhoneIcon from 'vue-material-design-icons/Phone'
import MapMarkerIcon from 'vue-material-design-icons/MapMarker'
import AccountIcon from 'vue-material-design-icons/Account'
import { showError } from '@nextcloud/dialogs'

import PrimaryActionButton from '../components/Profile/PrimaryActionButton'

const { actionParameters, userId, biography, company, headline, jobTitle, displayName, address } = loadState('core', 'profileParameters', {})
const status = loadState('core', 'status', {})

// const actionParameters = []

export default {
	name: 'Profile',

	components: {
		Avatar,
		Actions,
		ActionLink,
		AccountIcon,
		// PhoneIcon,
		MapMarkerIcon,
		PrimaryActionButton,
	},

	data() {
		return {
			userId,
			displayName,
			address,
			status,
			company,
			jobTitle,
			headline,
			biography,
		}
	},

	computed: {
		isCurrentUser() {
			return getCurrentUser()?.uid === this.userId
		},

		actionsDisabled() {
			return this.isCurrentUser
		},

		primaryAction() {
			if (this.allActions.length) {
				return this.allActions[0]
			}
			return null
		},

		allActions() {
			return actionParameters
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
			} else {
				showError(t('core', 'Error opening the user status modal, try hard refreshing the page'))
			}
		},

		openSettings() {
			location.href = generateUrl('/settings/user')
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

// Add twitter icon
.icon-twitter {
	background-image: url("data:image/svg+xml,%3Csvg width='32' height='32' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M21.61 4.62h.16c1.77 0 3.37.75 4.5 1.94 1.4-.27 2.71-.79 3.9-1.5a6.17 6.17 0 01-2.7 3.41C28.7 8.32 29.9 8 31 7.5a12.47 12.47 0 01-3.07 3.19l.02.8c0 8.13-6.2 17.51-17.52 17.51-3.47 0-6.7-1.02-9.43-2.77a12.46 12.46 0 009.11-2.54 6.16 6.16 0 01-5.75-4.28 6.1 6.1 0 002.78-.1 6.16 6.16 0 01-4.93-6.04v-.08c.83.46 1.78.74 2.78.77a6.15 6.15 0 01-1.9-8.22 17.48 17.48 0 0012.69 6.44 6.16 6.16 0 015.83-7.56z'/%3E%3C/svg%3E");
	background-size: 16px;
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

		&__container {
			align-self: flex-end;
			width: 100%;
			max-width: $profile-max-width;
			margin: 0 auto;
			display: grid;
			grid-template-rows: max-content max-content;
			grid-template-columns: 240px 1fr;
			justify-content: center;

			&__placeholder {
				grid-row: 1 / 3;
			}

			&__displayname, &__status {
				color: var(--color-primary-text);
			}

			&__displayname {
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

			&__button {
				border: none;
				box-shadow: 0 0 0 1px var(--color-primary-text);
				margin-left: 12px;
				margin-top: 8px;
			}

			&__status {
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
		margin: -150px 20px 0 0;

		// Specificity hack is needed to override Avatar component styles
		&::v-deep .avatar.avatardiv, h2 {
			text-align: center;
			margin: auto;
			display: block;
			padding: 8px;
		}

		&::v-deep .avatar.avatardiv:not(.avatardiv--unknown) {
			background-color: var(--color-main-background) !important;
			box-shadow: none;
		}

		&::v-deep .avatar.avatardiv {
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
		width: $content-max-width;

		&-details {
			display: flex;
			flex-direction: column;
			gap: 2px 0;

			.detail {
				display: inline-block;
				color: var(--color-text-maxcontrast);

				& span {
					display: inline-block;
					vertical-align: middle;
				}
			}
		}

		&-headline {
			margin-top: 10px;

			h3 {
				font-weight: bold;
				font-size: 20px;
				margin: 0;
			}
		}

		h3, p {
			cursor: text;
		}

		&-empty-info {
			margin-top: 80px;
			margin-right: 100px;
			display: flex;
			flex-direction: column;
			text-align: center;

			h3 {
				font-weight: bold;
				font-size: 18px;
				margin: 8px 0;
			}
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
	gap: 5px 0;
	margin-top: 20px;

	&__other {
		display: flex;
		justify-content: center;
		gap: 0 5px;
	}
}
</style>
