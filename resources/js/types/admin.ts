import type { ProfileStatusTone } from '@/lib/profile-data';

export type AdminStatusOption = {
    value: string;
    label: string;
};

export type AdminPaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type AdminPaginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: AdminPaginationLink[];
};

export type AdminPackageListItem = {
    id: number;
    title: string;
    slug: string;
    priceToman: number;
    priceFormatted: string;
    isActive: boolean;
    displayOrder: number;
    ordersCount: number;
};

export type AdminPackageEdit = {
    id: number;
    title: string;
    slug: string;
    priceToman: number;
    isActive: boolean;
    displayOrder: number;
    ordersCount: number;
    spotplayerCourseIdsText: string;
};

export type AdminOrderListItem = {
    id: number;
    orderNumber: string;
    userName: string;
    userEmail: string;
    customerName: string | null;
    customerMobile: string | null;
    packageTitle: string;
    status: string;
    statusValue: string;
    statusTone: ProfileStatusTone;
    paymentType: string;
    amountToman: number;
    amountFormatted: string;
    finalAmountToman: number;
    finalAmountFormatted: string;
    latestPaymentStatus: string | null;
    latestPaymentStatusTone: ProfileStatusTone | null;
    latestPaymentMethod: string | null;
    licenseStatus: string | null;
    licenseStatusTone: ProfileStatusTone | null;
    createdAt: string | null;
    canMarkPaid: boolean;
    canCancel: boolean;
};

export type AdminPaymentListItem = {
    id: number;
    orderNumber: string;
    userName: string;
    userEmail: string;
    customerName: string | null;
    customerMobile: string | null;
    packageTitle: string;
    method: string;
    methodValue: string;
    status: string;
    statusValue: string;
    statusTone: ProfileStatusTone;
    amountToman: number;
    amountFormatted: string;
    trackingCode: string | null;
    paidAt: string | null;
    createdAt: string | null;
    receiptUrl: string | null;
    canApprove: boolean;
    canReject: boolean;
    rejectionNote: string | null;
    installmentRequestedTerm: string | null;
    installmentNote: string | null;
    meta: string | null;
};

export type AdminLicenseListItem = {
    id: number;
    userName: string;
    userEmail: string;
    orderCustomerName: string | null;
    orderCustomerMobile: string | null;
    packageTitle: string;
    orderNumber: string | null;
    orderStatus: string | null;
    orderStatusTone: ProfileStatusTone | null;
    latestPaymentStatus: string | null;
    latestPaymentStatusTone: ProfileStatusTone | null;
    status: string;
    statusValue: string;
    statusTone: ProfileStatusTone;
    licenseKey: string | null;
    activatedAt: string | null;
    canActivate: boolean;
    canRevoke: boolean;
    provisionedVia: string;
    provisionedViaLabel: string;
    apiFailureSummary: string | null;
    apiTechnicalDetails: {
        lastApiAttemptAt: string | null;
        lastApiError: string | null;
        lastApiHttpStatus: number | null;
        spotplayerLicenseId: string | null;
        spotplayerErrorMessage: string | null;
        spotplayerResponseKeys: string[];
        spotplayerResponsePreview: string | null;
    };
    canRetryProvision: boolean;
};

export type AdminSmsSettings = {
    enabled: boolean;
    adminNotificationsEnabled: boolean;
    adminMobile: string | null;
    driver: string;
    driverLabel: string;
    driverConfigured: boolean;
};

export type AdminSmsTemplate = {
    id: number;
    key: string;
    title: string;
    body: string;
    isEnabled: boolean;
    description: string | null;
};

export type AdminSmsLogItem = {
    id: number;
    mobile: string | null;
    type: string;
    typeValue: string | null;
    status: string;
    statusValue: string | null;
    statusTone: ProfileStatusTone;
    provider: string;
    messagePreview: string;
    message: string;
    meta: string | null;
    sentAt: string | null;
    createdAt: string | null;
};

export type AdminSupportTicketListItem = {
    id: number;
    subject: string;
    status: string;
    statusValue: string;
    statusTone: ProfileStatusTone;
    category: string;
    categoryValue: string;
    customerName: string;
    customerMobile: string | null;
    userName: string;
    userEmail: string;
    createdAt: string | null;
};

export type AdminSupportTicketDetail = {
    id: number;
    subject: string;
    status: string;
    statusValue: string;
    statusTone: ProfileStatusTone;
    category: string;
    categoryValue: string;
    customerName: string;
    customerMobile: string | null;
    userName: string;
    userEmail: string;
    createdAt: string | null;
    closedAt: string | null;
    isClosed: boolean;
};

export type AdminSupportTicketMessage = {
    id: number;
    body: string;
    senderType: string;
    senderLabel: string;
    createdAt: string | null;
    attachment?: {
        id: number;
        originalName: string;
        sizeBytes: number;
        mimeType: string;
        downloadUrl: string;
    } | null;
};

export type AdminSupportOrderContext = {
    id: number;
    orderNumber: string;
    status: string;
    statusTone: ProfileStatusTone;
    packageTitle: string;
};

export type AdminSupportLicenseContext = {
    id: number;
    packageTitle: string;
    status: string;
    statusTone: ProfileStatusTone;
};
