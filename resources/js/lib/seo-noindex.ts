const NO_INDEX_PUBLIC_COMPONENT_PREFIXES = [
    'profile/',
    'support/',
    'checkout/result',
    'checkout/confirm',
] as const;

const NO_INDEX_AUTH_COMPONENTS = new Set<string>([
    'auth/register-verify',
    'auth/mobile-verify',
    'auth/forgot-password-verify',
    'auth/reset-password-mobile',
    'auth/reset-password',
    'auth/mobile',
    'auth/login-email',
]);

export function shouldNoIndexPublicTabComponent(component: string): boolean {
    return NO_INDEX_PUBLIC_COMPONENT_PREFIXES.some((prefix) =>
        component.startsWith(prefix),
    );
}

export function shouldNoIndexAuthComponent(component: string): boolean {
    return NO_INDEX_AUTH_COMPONENTS.has(component);
}

export function shouldNoIndexMaintenanceComponent(component: string): boolean {
    return component.startsWith('maintenance/');
}

export function shouldNoIndexSettingsComponent(component: string): boolean {
    return component.startsWith('settings/');
}

export function shouldNoIndexAdminComponent(component: string): boolean {
    return component.startsWith('admin/');
}
