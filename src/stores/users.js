import axios from '@nextcloud/axios';
import { loadState } from '@nextcloud/initial-state';
import { generateOcsUrl } from '@nextcloud/router';
import api from './api.js';

const state = {
	serverData: loadState('accountexport', 'accountExportUsersSettings', {}),
	users: [],
	usersOffset: 0,
	usersLimit: 25,
	disabledUsersOffset: 0,
	disabledUsersLimit: 25,
	groups: [],
	isLoadingUser: false,
};

const mutations = {
	setServerData(state, data) {
		state.serverData = data;
	},
	appendUsers(state, usersObj) {
		const existingUsers = state.users.map(({ id }) => id);
		const newUsers = Object.values(usersObj).filter(
			({ id }) => !existingUsers.includes(id)
		);

		const users = state.users.concat(newUsers);
		state.usersOffset += state.usersLimit;
		state.users = users;
	},
	/**
	 * Reset users list
	 *
	 * @param {object} state the store state
	 */
	resetUsers(state) {
		state.users = [];
		state.usersOffset = 0;
		state.disabledUsersOffset = 0;
	},
	updateDisabledUsers(state, _usersObj) {
		state.disabledUsersOffset += state.disabledUsersLimit;
	},
};

const getters = {
	getServerData(state) {
		return state.serverData;
	},
	getIsAdmin(state) {
		return state.serverData.isAdmin;
	},
	getIsDelegatedAdmin(state) {
		return state.serverData.isDelegatedAdmin;
	},
	getListGroup(state) {
		return state.serverData.groups
			.filter(
				(group) =>
					group.id !== 'disabled' &&
					group.id !== '__nc_internal_recent' &&
					group.id !== 'admin'
			)
			.filter((group) => group !== null);
	},
	getAllAccountCount(state) {
		return state.serverData.userCount;
	},
	getUsers(state) {
		return state.users;
	},
	getUsersOffset(state) {
		return state.usersOffset;
	},
	getUsersLimit(state) {
		return state.usersLimit;
	},
	getDisabledUsersOffset(state) {
		return state.disabledUsersOffset;
	},
	getDisabledUsersLimit(state) {
		return state.disabledUsersLimit;
	},
	getGroups(state) {
		return state.serverData.groups;
	},
	getSortedGroups(state) {
		const groups = [...state.serverData['groups']];
		return groups.sort((a, b) => a.name.localeCompare(b.name));
	},
	getLoadingUsers(state) {
		return state.isLoadingUser;
	},
	getSubadminGroups(state) {
		// Can't be subadmin of admin, recent, or disabled
		const groups = [...state.serverData['groups']];
		return groups.filter(
			(group) =>
				group.id !== 'admin' &&
				group.id !== '__nc_internal_recent' &&
				group.id !== 'disabled'
		);
	},
};

const CancelToken = axios.CancelToken;
let searchRequestCancelSource = null;

const actions = {
	getUsers(context, { offset, limit, search, group }) {
		if (searchRequestCancelSource) {
			searchRequestCancelSource.cancel(
				'Operation canceled by another search request.'
			);
		}
		searchRequestCancelSource = CancelToken.source();
		search = typeof search === 'string' ? search : '';

		/**
		 * Adding filters in the search bar such as in:files, in:users, etc.
		 * collides with this particular search, so we need to remove them
		 * here and leave only the original search query
		 */
		search = search.replace(/in:[^\s]+/g, '').trim();

		group = typeof group === 'string' ? group : '';
		if (group !== '') {
			return api
				.get(
					generateOcsUrl(
						'cloud/groups/{group}/users/details?offset={offset}&limit={limit}&search={search}',
						{
							group: encodeURIComponent(group),
							offset,
							limit,
							search,
						}
					),
					{
						cancelToken: searchRequestCancelSource.token,
					}
				)
				.then((response) => {
					const usersCount = Object.keys(
						response.data.ocs.data.users
					).length;
					if (usersCount > 0) {
						context.commit(
							'appendUsers',
							response.data.ocs.data.users
						);
					}
					return usersCount;
				})
				.catch((error) => {
					if (!axios.isCancel(error)) {
						context.commit('API_FAILURE', error);
					}
				});
		}

		return api
			.get(
				generateOcsUrl(
					'cloud/users/details?offset={offset}&limit={limit}&search={search}',
					{ offset, limit, search }
				),
				{
					cancelToken: searchRequestCancelSource.token,
				}
			)
			.then((response) => {
				const usersCount = Object.keys(
					response.data.ocs.data.users
				).length;
				if (usersCount > 0) {
					context.commit('appendUsers', response.data.ocs.data.users);
				}
				return usersCount;
			})
			.catch((error) => {
				if (!axios.isCancel(error)) {
					context.commit('API_FAILURE', error);
				}
			});
	},
	getGroups(context, { offset, limit, search }) {
		search = typeof search === 'string' ? search : '';
		const limitParam = limit === -1 ? '' : `&limit=${limit}`;
		return api
			.get(
				generateOcsUrl('cloud/groups?offset={offset}&search={search}', {
					offset,
					search,
				}) + limitParam
			)
			.then((response) => {
				if (Object.keys(response.data.ocs.data.groups).length > 0) {
					response.data.ocs.data.groups.forEach(function (group) {
						context.commit('addGroup', {
							gid: group,
							displayName: group,
						});
					});
					return true;
				}
				return false;
			})
			.catch((error) => context.commit('API_FAILURE', error));
	},
	/**
	 * Get recent users with full details
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {number} options.offset List offset to request
	 * @param {number} options.limit List number to return from offset
	 * @param {string} options.search Search query
	 * @return {Promise<number>}
	 */
	async getRecentUsers(context, { offset, limit, search }) {
		const url = generateOcsUrl(
			'cloud/users/recent?offset={offset}&limit={limit}&search={search}',
			{ offset, limit, search }
		);
		try {
			const response = await api.get(url);
			const usersCount = Object.keys(response.data.ocs.data.users).length;
			if (usersCount > 0) {
				context.commit('appendUsers', response.data.ocs.data.users);
			}
			return usersCount;
		} catch (error) {
			context.commit('API_FAILURE', error);
		}
	},
	/**
	 * Get disabled users with full details
	 *
	 * @param {object} context store context
	 * @param {object} options destructuring object
	 * @param {number} options.offset List offset to request
	 * @param {number} options.limit List number to return from offset
	 * @param options.search
	 * @return {Promise<number>}
	 */
	async getDisabledUsers(context, { offset, limit, search }) {
		const url = generateOcsUrl(
			'cloud/users/disabled?offset={offset}&limit={limit}&search={search}',
			{ offset, limit, search }
		);
		try {
			const response = await api.get(url);
			const usersCount = Object.keys(response.data.ocs.data.users).length;
			if (usersCount > 0) {
				context.commit('appendUsers', response.data.ocs.data.users);
				context.commit(
					'updateDisabledUsers',
					response.data.ocs.data.users
				);
			}
			return usersCount;
		} catch (error) {
			context.commit('API_FAILURE', error);
		}
	},
};

export default { state, mutations, getters, actions };
