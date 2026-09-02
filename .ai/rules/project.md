# Project Overview

> Read first. Tells you what this project is, the stack, and how the user wants to work.

## What

LED Manager — Vietnamese-language operations platform for an LED display rental/event business.
Manages: assets (cabinets, panels, accessories), warehouses, check-in/out/return batches, repair logs, customers, quotations, orders, contracts, payments, event logistics, and a mobile PDA app for warehouse staff.

## Stack

- **Backend**: Laravel 12 / PHP 8.4
- **Admin panel**: Filament v5.7.6
- **Testing**: Pest 3.x, SQLite in-memory
- **Database**: SQLite (file `database/database.sqlite` for dev, `:memory:` for tests)
- **Authorization**: Spatie Laravel-Permission + Filament Shield
- **Mobile**: Expo SDK 57, React Native 0.86.3, React 19.2.3
- **Mobile libs**: `expo-camera` 57.0.4, `expo-haptics`, `axios`, `@react-native-async-storage/async-storage`, `lucide-react-native`

## Branches & working mode

- Branch: `main` (single-developer)
- User works in **big batches**, not split phases (`làm tất cả đi chứ, sao cứ phải tách ra làm gì`)
- User will **test UI + logic together** after each batch — no need to split backend/mobile/front

## Common commands

```bash
# Backend tests
php artisan test --compact                   # 61 tests, 432 assertions, ~14s

# One-shot bootstrap (dev re-init)
php artisan app:bootstrap                    # fresh DB + Shield + seed + admin user

# Mobile build
cd mobile && npx expo export -p web         # sanity-check web bundle

# Lint
vendor/bin/pint --dirty --format agent
```

## User preferences

- 🇻🇳 Vietnamese, informal (`tôi`), direct and concise
- Prefers **idempotent**, **one-command** workflows
- Hates ceremony: don't add migrations for existing schemas; don't add `--force` flags unless needed; don't ask "do you want me to also..."
- Feedback is short and direct — one or two sentences is enough
- Cares about: data integrity, role-permission correctness, mobile UX consistency

## Conventions (high-level)

- **DB migrations**: edit in-place + `migrate:fresh --seed`. **Never add a new migration to alter an existing table.**
- **Enums**: PHP 8.1+ TitleCase backed enums, implement `HasLabel`, `HasColor`, `HasIcon` (Filament)
- **Code style**: PHP 8.4 (typed properties, constructor promotion, explicit return types); Pint formatted
- **Mobile nav**: switch-based in `App.tsx` with `ScreenName` union (NO React Navigation)
- **Mobile scanner**: `CameraView` + `useCameraPermissions` from `expo-camera` (NOT `BarCodeScanner`, deprecated)
- **API JSON**: `{success: bool, data: ..., pagination?: {...}, message?: string}`
