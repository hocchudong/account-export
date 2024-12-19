export const unlimitedQuota = {
	id: 'none',
	label: 'Unlimited',
};

export const defaultQuota = {
	id: 'default',
	label: 'Default quota',
};

/**
 * Return `true` if the logged in user does not have permissions to view the
 * data of `user`
 * @param user
 * @param user.id
 */
export const isObfuscated = (user) => {
	const keys = Object.keys(user);
	return keys.length === 1 && keys.at(0) === 'id';
};
