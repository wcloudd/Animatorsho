export type ProfileStatusTone = 'success' | 'warning' | 'neutral';

export type ProfileUser = {
    displayName: string;
    email: string | null;
    avatarPreset: string | null;
    maskedMobile: string | null;
    settingsUrl: string;
};

export type ProfileAccessState =
    | 'installment_reviewing'
    | 'payment_reviewing'
    | 'payment_pending'
    | 'paid_license_pending'
    | 'access_active'
    | 'license_revoked'
    | 'payment_failed'
    | 'cancelled';

export type ProfileAccessNextAction = {
    label: string;
    href: string;
    external: boolean;
};

export type ProfileAccessItem = {
    id: string;
    packageId: number;
    orderId: number | null;
    licenseId: number | null;
    title: string;
    accessState: ProfileAccessState;
    statusLabel: string;
    statusTone: ProfileStatusTone;
    description: string;
    paymentMethod: string | null;
    amountToman: number | null;
    licenseKey: string | null;
    rejectionReason?: string | null;
    nextAction: ProfileAccessNextAction | null;
};

export type ProfileOrderHistoryItem = {
    id: number;
    orderNumber: string;
    title: string;
    status: string;
    statusTone: ProfileStatusTone;
    paymentType: string;
    paymentMethod: string | null;
    amountToman: number;
    createdAt: string | null;
};

export type ProfilePageProps = {
    user: ProfileUser;
    accessItems: ProfileAccessItem[];
    orderHistory: ProfileOrderHistoryItem[];
    hasOrderHistory: boolean;
};
