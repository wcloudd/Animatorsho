import { Head } from '@inertiajs/react';
import { ProfileAccessCard } from '@/components/profile/profile-access-card';
import { ProfileOrderHistorySection } from '@/components/profile/profile-order-history-section';
import { ProfileLogoutButton } from '@/components/profile/profile-logout-button';
import { ProfileSupportCard } from '@/components/profile/profile-support-card';
import { ProfileWelcomeCard } from '@/components/profile/profile-welcome-card';
import { PageContainer } from '@/components/page-container';
import type { ProfilePageProps } from '@/lib/profile-data';

export default function ProfileIndex({
    user,
    accessItems,
    orderHistory,
    hasOrderHistory,
}: ProfilePageProps) {
    return (
        <>
            <Head title="پروفایل من" />
            <PageContainer>
                <div className="flex flex-col gap-6">
                    <ProfileWelcomeCard user={user} />
                    <ProfileAccessCard accessItems={accessItems} />
                    <ProfileOrderHistorySection
                        orderHistory={orderHistory}
                        hasOrderHistory={hasOrderHistory}
                    />
                    <ProfileSupportCard />
                    <ProfileLogoutButton />
                </div>
            </PageContainer>
        </>
    );
}
