# Phase 1 Regression Checklist

This document ensures Phase 1 content gating features remain stable as new features are added.

## ✅ Must-Pass Tests

Run the full test suite before deploying:

```bash
php artisan test
```

### Critical Test Suites

1. **User Segmentation Tests** (`UserSegmentationTest`)
   - Student can update own gender + whatsapp fields
   - Student cannot update has_bayah or level
   - Admin can update bay'ah and level
   - last_active_at updates on authenticated request

2. **EligibilityService Tests** (`EligibilityServiceTest`)
   - No rules → allowed
   - Gender restrictions work correctly
   - Bay'ah requirements work correctly
   - Level requirements work correctly
   - Rule inheritance (additive AND behavior)
   - Conflicting gender rules detected
   - Multiple reasons returned correctly

3. **Content Gating Integration Tests** (`ContentGatingIntegrationTest`)
   - Locked lesson direct access denied
   - Course index returns lock metadata
   - Course show marks lesson locked
   - Config hide/show locked courses works

4. **Admin Content Rule Tests** (`AdminContentRuleTest`)
   - Admin can upsert/delete rules
   - Non-admin forbidden
   - Validation works
   - Update doesn't create duplicates

5. **Lock Message Tests** (`LockMessageTest`)
   - Conflicting rules message
   - Multiple reasons ordering
   - Individual reason messages

---

## 🔍 Manual QA Checklist

### User Segmentation

- [ ] **Student Profile Update**
  - Login as student → `/profile`
  - Update gender (Male/Female)
  - Add WhatsApp number
  - Check WhatsApp opt-in
  - Save → see success message
  - Verify DB updated

- [ ] **Student Cannot Update Restricted Fields**
  - As student, try to update `has_bayah` or `level` via form
  - Verify: Fields ignored/not updated in DB

- [ ] **Admin Segmentation Update**
  - Login as admin → `/admin/users/{id}`
  - Update bay'ah status
  - Update level
  - Save → verify DB updated

- [ ] **Last Active Timestamp**
  - Login as any user
  - Note `last_active_at` in DB
  - Refresh any authenticated page
  - Verify: `last_active_at` updated

### Content Gating (Student View)

- [ ] **Course Index - Show Locked**
  - Set `config('lms.show_locked_courses', true)`
  - Login as beginner/no bay'ah
  - Visit `/courses`
  - Verify: See locked courses with lock badges
  - Verify: Lock messages displayed

- [ ] **Course Index - Hide Locked**
  - Set `config('lms.show_locked_courses', false)`
  - Login as beginner/no bay'ah
  - Visit `/courses`
  - Verify: Locked courses hidden

- [ ] **Course Show Page**
  - Login as beginner/no bay'ah
  - Visit locked course → `/courses/{id}`
  - Verify: Course page loads (overview visible)
  - Verify: Course lock message displayed
  - Verify: Lessons show lock badges
  - Verify: Lesson links disabled
  - Verify: Lock messages on lessons correct

- [ ] **Lesson Direct Access Blocked**
  - Login as beginner/no bay'ah
  - Try direct URL: `/courses/{id}/lessons/{locked-lesson-id}`
  - Verify: Redirected to course show page
  - Verify: Flash message shows lock reason

### Admin Rule Management

- [ ] **Course Rule Management**
  - Login as admin → `/admin/courses/{id}/edit`
  - Scroll to "Access Rule" section
  - Set min_level, gender, requires_bayah
  - Save → verify success message
  - Refresh page → verify rule persisted
  - Remove rule → verify deleted

- [ ] **Module Rule Management**
  - Login as admin → `/admin/modules/{id}/edit`
  - Set module rule
  - Save → verify persisted

- [ ] **Lesson Rule Management**
  - Login as admin → `/admin/lessons/{id}/edit`
  - Set lesson rule
  - Save → verify persisted

- [ ] **Non-Admin Forbidden**
  - Login as non-admin
  - Try to access admin rule routes
  - Verify: 403 Forbidden

---

## ⚠️ Known Constraints

### Rule Inheritance (Additive AND)

- Rules are **additive** (AND behavior):
  - Course requires bay'ah + Module requires expert → both required
  - Course male + Module female → **conflicting**, access denied
  - Course beginner + Module intermediate + Lesson expert → **Expert** required (highest)

### Database Constraints

- **One rule per entity**: Unique constraint on `(ruleable_type, ruleable_id)`
- Cannot have multiple rules for same course/module/lesson

### User Defaults

- User `level` null → treated as `beginner`
- User `gender` null + content requires gender → **denied** (gender_mismatch)

### Evaluation Order

For lessons, rules evaluated in order:
1. Course rule
2. Module rule  
3. Lesson rule

All rules combined with AND logic.

---

## 🚨 Breaking Changes Warning

If modifying any of these, Phase 1 features may break:

- `users` table structure (gender, has_bayah, level, whatsapp fields)
- `content_rules` table structure
- `EligibilityService` evaluation logic
- `EligibilityResult` structure
- `LockMessage` formatting
- Admin middleware (`IsAdmin`)

---

## 📋 Phase 1 Verification Script

Quick verification steps:

```bash
# 1. Fresh migration
php artisan migrate:fresh

# 2. Seed demo data
php artisan db:seed --class=LmsDemoSeeder

# 3. Run tests
php artisan test

# 4. Manual verification
# Login as beginner_male@demo.com (password: password)
# - Should see locked courses (bay'ah, expert, female-only)
# - Should access free course
# 
# Login as expert_female@demo.com (password: password)
# - Should access all courses except male-only
# 
# Login as admin@demo.com (password: password)
# - Should access admin panel
# - Should be able to set/edit rules
```

---

## 📊 Demo Users Access Matrix

| User | Free Course | Bay'ah Course | Expert Course | Female Course | Male Course |
|------|-------------|---------------|---------------|---------------|-------------|
| beginner_male@demo.com | ✅ | ❌ (no bay'ah) | ❌ (beginner) | ❌ (male) | ✅ |
| expert_female@demo.com | ✅ | ✅ | ✅ | ✅ | ❌ (female) |
| admin@demo.com | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 🔧 Troubleshooting

### Tests Failing

1. Check migrations: `php artisan migrate:fresh`
2. Check factories: Ensure ContentRuleFactory exists
3. Check config: `config('lms.show_locked_courses')` may affect tests

### Lock Messages Not Showing

1. Verify `EligibilityService` returns correct `EligibilityResult`
2. Check `LockMessage::fromEligibility()` is called
3. Verify Vue component receives `lock_message` prop

### Rules Not Applying

1. Verify rule exists in DB: `content_rules` table
2. Check `EligibilityService` is being used (not old `ContentGatingService`)
3. Verify user has correct attributes (gender, level, has_bayah)

---

## 📝 Notes

- Admin detection uses `is_admin` boolean column (not roles/permissions)
- Rules are evaluated server-side (security requirement)
- Student UI shows locks but doesn't prevent direct URL access (controller blocks)
- Config `lms.show_locked_courses` defaults to `true`
