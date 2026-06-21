<?php

namespace App\Services\Course;

use App\Enums\SpotPlayerLicenseStatus;
use App\Models\User;
use App\Services\AnimatorshoCatalogService;
use App\Services\StudentMedalService;
use App\Services\StudentNotificationService;
use App\Services\StudentXpService;
use App\Support\StudentPanel\StudentPanelMedia;
use Illuminate\Support\Collection;

class CourseAccessService
{
    /** @var Collection<int, int>|null */
    private ?Collection $animatorshoPackageIds = null;

    public function __construct(
        private readonly AnimatorshoCatalogService $catalog,
        private readonly CourseUpdateQueryService $courseUpdates,
        private readonly CourseResourceQueryService $courseResources,
        private readonly ExerciseSubmissionQueryService $exerciseSubmissions,
        private readonly StudentXpService $xpService,
        private readonly StudentMedalService $medalService,
        private readonly StudentNotificationService $notificationService,
    ) {}

    public function userHasActiveAccess(User $user): bool
    {
        $packageIds = $this->animatorshoPackageIds();

        if ($packageIds->isEmpty()) {
            return false;
        }

        return $user->spotPlayerLicenses()
            ->whereIn('course_package_id', $packageIds)
            ->where('status', SpotPlayerLicenseStatus::Active)
            ->exists();
    }

    /**
     * @return array{
     *     welcome: array{displayName: string, firstName: string},
     *     progress: array{totalXp: int, level: int, currentLevelXp: int, xpPerLevel: int, xpToNextLevel: int, progressPercent: int},
     *     onboarding: array{
     *         title: string,
     *         heading: string,
     *         description: string,
     *         imageUrl: ?string,
     *         imageAlt: string,
     *         videoUrl: ?string,
     *         videoPosterUrl: ?string,
     *         videoTitle: string,
     *         pdfUrl: ?string,
     *         pdfDownloadName: string,
     *         videoGuideLabel: string,
     *         pdfGuideLabel: string
     *     },
     *     preview: array{
     *         updates: list<array{
     *             id: string,
     *             title: string,
     *             summary: string,
     *             type: string,
     *             typeLabel: string,
     *             visualTheme: string,
     *             visualThemeLabel: string,
     *             publishedAt: ?string,
     *             publishedAtLabel: string,
     *             isPinned: bool,
     *             body: ?string,
     *             imageUrl: ?string,
     *             imageAlt: ?string
     *         }>,
     *         resources: list<array{
     *             id: string,
     *             title: string,
     *             description: string,
     *             type: string,
     *             typeLabel: string,
     *             categoryLabel: ?string,
     *             publishedAt: ?string,
     *             publishedAtLabel: string,
     *             actionUrl: ?string,
     *             actionLabel: string,
     *             isAvailable: bool,
     *             imageUrl: ?string,
     *             imageAlt: ?string
     *         }>,
     *         resourcesIndexUrl: string,
     *         notificationsUnread: int,
     *         exercisesSummary: array{
     *             total: int,
     *             pending: int,
     *             latest: ?array{
     *                 title: string,
     *                 status: string,
     *                 statusLabel: string,
     *                 statusTone: string
     *             },
     *             exercisesIndexUrl: string,
     *             createUrl: string
     *         },
     *         medals: array{
     *             earned: list<array{slug: string, title: string}>,
     *             locked: list<array{slug: string, title: string}>,
     *             totalAvailable: int
     *         },
     *         sectionVisuals: array{
     *             exercises: array{
     *                 imageUrl: ?string,
     *                 imageAlt: string,
     *                 placeholderTitle: string,
     *                 placeholderDescription: ?string
     *             },
     *             resources: array{
     *                 imageUrl: ?string,
     *                 imageAlt: string,
     *                 placeholderTitle: string,
     *                 placeholderDescription: ?string
     *             },
     *             medals: array{
     *                 imageUrl: ?string,
     *                 imageAlt: string,
     *                 placeholderTitle: string,
     *                 placeholderDescription: ?string
     *             },
     *             updates: array{
     *                 imageUrl: ?string,
     *                 imageAlt: string,
     *                 placeholderTitle: string,
     *                 placeholderDescription: ?string
     *             }
     *         }
     *     }
     * }
     */
    public function courseHomePropsForUser(User $user): array
    {
        return [
            'welcome' => [
                'displayName' => $user->name,
                'firstName' => $this->firstNameFromDisplayName($user->name),
            ],
            'progress' => $this->xpService->levelProgressForUser($user),
            'onboarding' => StudentPanelMedia::resolvedOnboarding(),
            'notifications' => $this->notificationService->notificationsForHome($user),
            'preview' => [
                'updates' => $this->courseUpdates->latestPublishedForHome(),
                'resources' => $this->courseResources->latestPublishedForHome(),
                'resourcesIndexUrl' => route('course.resources.index'),
                'notificationsUnread' => $this->notificationService->unreadCountForUser($user),
                'exercisesSummary' => $this->exerciseSubmissions->summaryForHome($user),
                'medals' => $this->medalService->medalsPreviewForUser($user),
                'sectionVisuals' => StudentPanelMedia::resolvedSectionVisuals(),
            ],
        ];
    }

    private function firstNameFromDisplayName(string $displayName): string
    {
        $parts = preg_split('/\s+/', trim($displayName), -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts) || $parts === []) {
            return $displayName;
        }

        return $parts[0];
    }

    /**
     * @return Collection<int, int>
     */
    private function animatorshoPackageIds(): Collection
    {
        if ($this->animatorshoPackageIds !== null) {
            return $this->animatorshoPackageIds;
        }

        $this->animatorshoPackageIds = $this->catalog
            ->activePackages()
            ->pluck('id');

        return $this->animatorshoPackageIds;
    }
}
