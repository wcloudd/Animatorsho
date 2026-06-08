import type { ProfileStatusTone } from '@/lib/profile-data';

export type SupportCategoryOption = {
    value: string;
    label: string;
};

export type SupportTicketListItem = {
    id: number;
    subject: string;
    status: string;
    statusValue: string;
    statusTone: ProfileStatusTone;
    category: string;
    categoryValue: string;
    createdAt: string | null;
};

export type SupportTicketDetail = SupportTicketListItem & {
    canReply: boolean;
};

export type SupportTicketMessageAttachment = {
    id: number;
    originalName: string;
    sizeBytes: number;
    mimeType: string;
    downloadUrl: string;
};

export type SupportTicketMessage = {
    id: number;
    body: string;
    senderType: string;
    senderLabel: string;
    createdAt: string | null;
    attachment?: SupportTicketMessageAttachment | null;
};

export type SupportHelpNote = {
    title: string;
    text: string;
    ctaLabel: string;
    ctaHref: string;
};

export type SupportIndexProps = {
    tickets: SupportTicketListItem[];
    categoryOptions: SupportCategoryOption[];
    helpNote: SupportHelpNote;
};

export type SupportShowProps = {
    ticket: SupportTicketDetail;
    messages: SupportTicketMessage[];
};
