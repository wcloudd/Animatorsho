import type { AdminStatusTone } from '@/components/admin/admin-status-badge';

export type AdminDashboardSummaryTone = 'warning' | 'danger' | 'neutral';

export type AdminDashboardSummaryCard = {
    key: string;
    label: string;
    count: number;
    href: string | null;
    tone: AdminDashboardSummaryTone;
};

export type AdminDashboardQueueItem = {
    id: number;
    title: string;
    subtitle: string;
    meta: string;
    href: string;
    badge: { label: string; tone: AdminStatusTone } | null;
};

export type AdminDashboardQueue = {
    key: string;
    title: string;
    viewAllHref: string;
    items: AdminDashboardQueueItem[];
};

export type AdminDashboardPageProps = {
    activityMetrics: AdminDashboardSummaryCard[];
    loginMetricsNote: string;
    summary: AdminDashboardSummaryCard[];
    actionQueues: AdminDashboardQueue[];
    activityQueues: AdminDashboardQueue[];
    allActionQueuesEmpty: boolean;
};

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
    statusTone: AdminStatusTone;
    paymentType: string;
    amountToman: number;
    amountFormatted: string;
    finalAmountToman: number;
    finalAmountFormatted: string;
    latestPaymentStatus: string | null;
    latestPaymentStatusTone: AdminStatusTone | null;
    latestPaymentMethod: string | null;
    licenseStatus: string | null;
    licenseStatusTone: AdminStatusTone | null;
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
    statusTone: AdminStatusTone;
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
    installment: AdminPaymentInstallmentSnapshot | null;
    meta: string | null;
};

export type AdminPaymentInstallmentSnapshot = {
    cashPriceToman: number | null;
    installmentTotalToman: number | null;
    downPaymentToman: number | null;
    remainingToman: number | null;
    downPaymentPercent: number | null;
    months: number | null;
    downPaymentPaidAt: string | null;
    downPaymentRef: string | null;
    downPaymentCaptured: boolean;
};

export type AdminInstallmentListItem = {
    id: number;
    orderNumber: string;
    paymentId: number | null;
    userName: string;
    userEmail: string;
    customerName: string | null;
    customerMobile: string | null;
    packageTitle: string;
    orderStatus: string;
    orderStatusValue: string;
    orderStatusTone: AdminStatusTone;
    paymentStatus: string | null;
    paymentStatusValue: string | null | undefined;
    paymentStatusTone: AdminStatusTone | null;
    installmentRequestedTerm: string | null;
    installmentNote: string | null;
    rejectionNote: string | null;
    trackingCode: string | null;
    amountToman: number;
    amountFormatted: string;
    installment: AdminPaymentInstallmentSnapshot | null;
    canApprove: boolean;
    canReject: boolean;
    createdAt: string | null;
    paymentReviewHref: string | null;
    orderHref: string;
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
    orderStatusTone: AdminStatusTone | null;
    latestPaymentStatus: string | null;
    latestPaymentStatusTone: AdminStatusTone | null;
    status: string;
    statusValue: string;
    statusTone: AdminStatusTone;
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

export type AdminSiteSettings = {
    purchasesEnabled: boolean;
    maintenanceModeEnabled: boolean;
    maintenanceTitle: string;
    maintenanceMessage: string;
    purchasesDisabledMessage: string;
};

export type AdminCardToCardDisplay = {
    configured: boolean;
    source: string;
    cardNumber: string;
    cardOwnerName: string;
};

export type AdminIntegrationStatus = {
    zarinpalConfigured: boolean;
    farazSmsConfigured: boolean;
    spotPlayerConfigured: boolean;
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
    statusTone: AdminStatusTone;
    provider: string;
    messagePreview: string;
    message: string;
    meta: string | null;
    sentAt: string | null;
    createdAt: string | null;
};

export type AdminSecurityEventMetaItem = {
    key: string;
    label: string;
    value: string;
};

export type AdminSecurityEventItem = {
    id: number;
    event: string;
    eventValue: string;
    eventTone: AdminStatusTone;
    occurredAt: string | null;
    userId: number | null;
    userLabel: string;
    route: string | null;
    method: string | null;
    ip: string | null;
    userAgent: string | null;
    metaItems: AdminSecurityEventMetaItem[];
};

export type AdminSupportTicketListItem = {
    id: number;
    subject: string;
    status: string;
    statusValue: string;
    statusTone: AdminStatusTone;
    category: string;
    categoryValue: string;
    customerName: string;
    customerMobile: string | null;
    userName: string;
    userEmail: string;
    createdAt: string | null;
};

export type AdminConsultationListItem = {
    id: number;
    name: string;
    mobile: string;
    note: string | null;
    level: string | null;
    interest: string | null;
    age: string | null;
    status: string;
    statusValue: string;
    statusTone: AdminStatusTone;
    adminNote: string | null;
    createdAt: string | null;
    updatedAt: string | null;
};

export type AdminSupportTicketDetail = {
    id: number;
    subject: string;
    status: string;
    statusValue: string;
    statusTone: AdminStatusTone;
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
    statusTone: AdminStatusTone;
    packageTitle: string;
};

export type AdminSupportLicenseContext = {
    id: number;
    packageTitle: string;
    status: string;
    statusTone: AdminStatusTone;
};
