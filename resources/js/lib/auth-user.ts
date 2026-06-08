type AuthUserMobileFields = {
    mobile?: unknown;
    mobile_verified_at?: unknown;
};

export function userHasVerifiedMobile(user: AuthUserMobileFields): boolean {
    return (
        typeof user.mobile === 'string' &&
        user.mobile !== '' &&
        user.mobile_verified_at !== null &&
        user.mobile_verified_at !== undefined
    );
}
