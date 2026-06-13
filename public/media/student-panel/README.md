# Student Panel Media

Place optional media files for the `/course` student panel home here.

No config edit is required for the default local filenames below. The app auto-detects files at request time using `public_path()` and exposes relative public URLs to the frontend.

## Expected filenames

| File | Activates |
|------|-----------|
| `onboarding-banner.png` | Onboarding card header image |
| `exercises-header.png` | تمرین‌های من card header |
| `mentor-header.png` | گفتگو با استاد card header |
| `resources-header.png` | کتابخانه تمرین card header |
| `medals-header.png` | مدال‌ها card header |
| `updates-header.png` | آخرین آپدیت‌ها card header |
| `start-guide.mp4` | ویدئو راهنما modal player |
| `start-guide-poster.png` | Video poster in the guide modal |
| `start-guide.pdf` | دانلود راهنما link |

## Public URLs

When a file exists, the panel uses paths like:

- `/media/student-panel/onboarding-banner.png`
- `/media/student-panel/start-guide.mp4`

If a file is missing, the UI keeps the current placeholder or «به‌زودی» toast behavior.

## Manual overrides (optional)

You can still set explicit URLs in `config/student_panel.php` under `onboarding` or `sectionVisuals`. A non-empty configured URL takes priority over auto-detection (useful for CDN/external hosting).

## Cache note

File detection is based on disk existence, not Laravel config cache. You usually do **not** need `php artisan optimize:clear` after adding files. Run it only if you changed `config/student_panel.php` and values look stale.
