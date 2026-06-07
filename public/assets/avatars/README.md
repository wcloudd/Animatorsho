# Animatorsho Avatar Pack

Local preset avatar assets for profile settings.

## Layout

Place ready-made avatar files under:

```
public/assets/avatars/{presetKey}/avatar.lottie
public/assets/avatars/{presetKey}/avatar.webp
public/assets/avatars/{presetKey}/avatar.json
```

## Preset keys

- keyframe_happy
- keyframe_sleepy
- keyframe_artist
- keyframe_teacher
- nimvajabee_smile
- nimvajabee_glasses
- animator_student
- robot_helper

## Notes

- The database stores only `users.avatar_preset` (the key).
- Frontend maps keys to assets in `resources/js/lib/avatar-presets.ts`.
- Until files exist, the UI shows placeholder emoji cards.
- No CDN or external URLs are used at runtime.
