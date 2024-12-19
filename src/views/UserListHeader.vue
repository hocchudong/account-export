<template>
	<tr class="header">
		<th
			class="header__cell header__cell--avatar"
			data-cy-user-list-header-avatar
			scope="col"
		>
			<span class="hidden-visually">
				{{ 'Avatar' }}
			</span>
		</th>
		<th
			class="header__cell header__cell--displayname"
			data-cy-user-list-header-displayname
			scope="col"
		>
			<strong>
				{{ 'Display name' }}
			</strong>
		</th>
		<th
			class="header__cell header__cell--username"
			data-cy-user-list-header-username
			scope="col"
		>
			<span>
				{{ 'Account name' }}
			</span>
		</th>
		<th
			class="header__cell header__cell--obfuscated"
			data-cy-user-list-header-password
			scope="col"
		>
			<span>{{ passwordLabel }}</span>
		</th>
		<th class="header__cell" data-cy-user-list-header-email scope="col">
			<span>{{ 'Email' }}</span>
		</th>
		<th
			class="header__cell header__cell--large"
			data-cy-user-list-header-groups
			scope="col"
		>
			<span>{{ 'Groups' }}</span>
		</th>
		<th
			class="header__cell header__cell--large"
			data-cy-user-list-header-subadmins
			scope="col"
		>
			<span>{{ 'Group admin for' }}</span>
		</th>
		<th class="header__cell" data-cy-user-list-header-quota scope="col">
			<span>{{ 'Quota' }}</span>
		</th>
		<th
			class="header__cell header__cell--large"
			data-cy-user-list-header-languages
			scope="col"
		>
			<span>{{ 'Language' }}</span>
		</th>
		<th
			class="header__cell header__cell--large"
			data-cy-user-list-header-storage-location
			scope="col"
		>
			<span>
				{{ 'Account backend' }}
			</span>
			<span class="header__subtitle">
				{{ 'Storage location' }}
			</span>
		</th>
		<th
			class="header__cell"
			data-cy-user-list-header-last-login
			scope="col"
		>
			<span>{{ 'Last login' }}</span>
		</th>
		<th
			class="header__cell header__cell--large header__cell--fill"
			data-cy-user-list-header-manager
			scope="col"
		>
			<!-- TRANSLATORS This string describes a manager in the context of an organization -->
			<span>{{ 'Manager' }}</span>
		</th>
		
	</tr>
</template>

<script>
import Vue from 'vue';

export default Vue.extend({
	name: 'UserListHeader',

	props: {},

	computed: {
		passwordLabel() {
			return 'Password';
		},
	},

	methods: {
		t,
	},
});
</script>

<style lang="scss" scoped>
@import '../shared/styles.scss';

.header {
	@include row;
	@include cell;

	border-bottom: 1px solid var(--color-border);
}
</style>
