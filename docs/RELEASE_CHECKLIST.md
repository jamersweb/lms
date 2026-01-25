# Release Checklist

## Pre-Release
- [ ] **Tests**: Run `php artisan test` (Must be 100% Green).
- [ ] **Build**: Run `npm run build` (Check for no errors).
- [ ] **Smoke Test**: Perform manual walk-through:
    - Register new user.
    - Enroll in course.
    - Complete Lesson (check points awarded).
    - Post Discussion Reply.
- [ ] **Backup**: snapshot production DB before deploying.

## Deployment
- [ ] Enable Maintenance Mode: `php artisan down`
- [ ] Git Pull (or Upload Zip).
- [ ] Update Dependencies: `composer install --no-dev`.
- [ ] Run Migrations: `php artisan migrate --force`.
    - *Note*: Ensure `add_performance_indexes` runs.
- [ ] Clear Caches:
    - `php artisan config:cache`
    - `php artisan route:cache`
    - `php artisan view:cache`
- [ ] Disable Maintenance Mode: `php artisan up`

## Rollback Plan
If critical error occurs:
1. Revert Code: `git reset --hard PREVIOUS_TAG` or Upload Previous Zip.
2. Restore DB: Import SQL dump captured in Pre-Release.
3. Flush Cache: `php artisan optimize:clear`.

## Operational Checks
- [ ] Check Logs (`storage/logs/laravel.log`) for immediate errors.
- [ ] Verify Storage: Can a new user upload avatar/video?
- [ ] Verify Email: Is mail sending? (Password reset check).
