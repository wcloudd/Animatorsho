<?php

namespace App\Services\Course;

use App\Enums\SpotPlayerLicenseStatus;
use App\Models\User;
use App\Services\AnimatorshoCatalogService;
use App\Support\StudentPanel\StudentPanelMedia;
use Illuminate\Support\Collection;

class CourseAccessService
{
    /** @var Collection<int, int>|null */
    private ?Collection $animatorshoPackageIds = null;

    public function __construct(
        private readonly AnimatorshoCatalogService $catalog,
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
     *     progress: array{level: int, totalXp: int, progressPercent: int, xpToNextLevel: int},
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
     *             publishedAtLabel: string,
     *             imageUrl: ?string,
     *             imageAlt: ?string
     *         }>,
     *         resources: list<array{
     *             id: string,
     *             title: string,
     *             description: string,
     *             type: string,
     *             typeLabel: string,
     *             imageUrl: ?string,
     *             imageAlt: ?string
     *         }>,
     *         notificationsUnread: int,
     *         exercisesSummary: array{total: int, pending: int},
     *         mentorSummary: array{hasThread: bool, status: ?string},
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
     *             mentor: array{
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
            'progress' => [
                'level' => 1,
                'totalXp' => 0,
                'progressPercent' => 0,
                'xpToNextLevel' => 500,
            ],
            'onboarding' => StudentPanelMedia::resolvedOnboarding(),
            'preview' => [
                'updates' => config('student_panel.preview.updates'),
                'resources' => config('student_panel.preview.resources'),
                'notificationsUnread' => 0,
                'exercisesSummary' => [
                    'total' => 0,
                    'pending' => 0,
                ],
                'mentorSummary' => [
                    'hasThread' => false,
                    'status' => null,
                ],
                'medals' => config('student_panel.preview.medals'),
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
