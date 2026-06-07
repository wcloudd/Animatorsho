import { ProfileStatusBadge } from '@/components/profile/profile-status-badge';
import type { ProfileStatusTone } from '@/lib/profile-data';

type AdminStatusBadgeProps = {
    tone: ProfileStatusTone;
    children: string;
};

export function AdminStatusBadge({ tone, children }: AdminStatusBadgeProps) {
    return <ProfileStatusBadge tone={tone}>{children}</ProfileStatusBadge>;
}
