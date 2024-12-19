<template>
	<tr class="footer">
		<th scope="row">
			<span class="hidden-visually">
				{{ 'Total rows summary' }}
			</span>
		</th>
		<td class="footer__cell footer__cell--loading">
			<NcLoadingIcon
				v-if="getLoadingUsers"
				title="Loading accounts …"
				:size="32"
			/>
		</td>
		<td class="footer__cell footer__cell--count footer__cell--multiline">
			<span aria-describedby="user-count-desc">{{ userCount }}</span>
			<span id="user-count-desc" class="hidden-visually">
				{{ 'Scroll to load more rows' }}
			</span>
		</td>
	</tr>
</template>

<script>
import Vue from 'vue';
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js';

import { translatePlural as n } from '@nextcloud/l10n';

export default Vue.extend({
	name: 'UserListFooter',

	components: {
		NcLoadingIcon,
	},

	props: {
		filteredUsers: {
			type: Array,
			required: true,
		},
	},

	computed: {
		userCount() {
			if (this.loading) {
				return this.n(
					'settings',
					'{userCount} account …',
					'{userCount} accounts …',
					this.filteredUsers.length,
					{
						userCount: this.filteredUsers.length,
					}
				);
			}
			return this.n(
				'settings',
				'{userCount} account',
				'{userCount} accounts',
				this.filteredUsers.length,
				{
					userCount: this.filteredUsers.length,
				}
			);
		},
		getLoadingUsers() {
			return this.$store.getters.getLoadingUsers;
		}
	},

	methods: {
		n,
	},
});
</script>

<style lang="scss" scoped>
@import '../shared/styles.scss';

.footer {
	@include row;
	@include cell;

	&__cell {
		position: sticky;
		color: var(--color-text-maxcontrast);

		&--loading {
			left: 0;
			min-width: var(--avatar-cell-width);
			width: var(--avatar-cell-width);
			align-items: center;
			padding: 0;
		}

		&--count {
			left: var(--avatar-cell-width);
			min-width: var(--cell-width);
			width: var(--cell-width);
		}
	}
}
</style>
