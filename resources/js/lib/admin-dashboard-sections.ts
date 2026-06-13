import type {
    AdminDashboardPageProps,
    AdminDashboardQueue,
    AdminDashboardSummaryCard,
} from '@/types/admin';

export const adminDashboardSectionTitles = {
    actionRequired: 'نیازمند اقدام',
    finance: 'مالی',
    learners: 'هنرجوها و دوره',
    communications: 'ارتباطات',
    security: 'امنیت و سیستم',
} as const;

export const adminDashboardSectionTitleList = Object.values(
    adminDashboardSectionTitles,
);

const actionRequiredSummaryKeys = [
    'pending_card_to_card',
    'pending_installment',
    'pending_licenses',
    'license_api_failures',
    'open_support_tickets',
] as const;

const communicationsSummaryKeys = [
    'new_consultations',
    'follow_up_consultations',
    'support_waiting_user',
] as const;

const securitySummaryKeys = ['sms_issues'] as const;

const actionRequiredQueueKeys = [
    'pending_card_to_card',
    'pending_installment',
    'pending_licenses',
    'license_api_failures',
    'open_support_tickets',
] as const;

const communicationsQueueKeys = [
    'new_consultations',
    'follow_up_consultations',
] as const;

const financeQueueKeys = ['recent_orders', 'recent_payments'] as const;

const securityQueueKeys = ['recent_sms_issues'] as const;

function pickSummaryCards(
    summary: AdminDashboardSummaryCard[],
    keys: readonly string[],
): AdminDashboardSummaryCard[] {
    return keys
        .map((key) => summary.find((card) => card.key === key))
        .filter((card): card is AdminDashboardSummaryCard => card !== undefined);
}

function pickQueues(
    queues: AdminDashboardQueue[],
    keys: readonly string[],
): AdminDashboardQueue[] {
    return queues.filter((queue) =>
        (keys as readonly string[]).includes(queue.key),
    );
}

export function partitionAdminDashboardProps({
    summary,
    actionQueues,
    activityQueues,
    activityMetrics,
}: Pick<
    AdminDashboardPageProps,
    'summary' | 'actionQueues' | 'activityQueues' | 'activityMetrics'
>) {
    const actionRequiredSummary = pickSummaryCards(
        summary,
        actionRequiredSummaryKeys,
    );
    const actionRequiredQueues = pickQueues(
        actionQueues,
        actionRequiredQueueKeys,
    );

    const communicationsSummary = pickSummaryCards(
        summary,
        communicationsSummaryKeys,
    );
    const communicationsQueues = pickQueues(
        actionQueues,
        communicationsQueueKeys,
    );

    const securitySummary = pickSummaryCards(summary, securitySummaryKeys);
    const securityQueues = pickQueues(activityQueues, securityQueueKeys);

    const financeQueues = pickQueues(activityQueues, financeQueueKeys);

    const hasUrgentAction =
        actionRequiredQueues.length > 0 ||
        actionRequiredSummary.some((card) => card.count > 0);

    return {
        actionRequired: {
            summary: actionRequiredSummary,
            queues: actionRequiredQueues,
            hasUrgentAction,
        },
        finance: {
            queues: financeQueues,
        },
        learners: {
            metrics: activityMetrics,
        },
        communications: {
            summary: communicationsSummary,
            queues: communicationsQueues,
        },
        security: {
            summary: securitySummary,
            queues: securityQueues,
        },
    };
}
