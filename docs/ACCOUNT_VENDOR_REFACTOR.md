# Account and vendor ownership refactor

Implementation notes for `hotfix/migrate-db-structure` (2026-10-23).
These notes describe the current account flow; older account descriptions in
`PROJECT_CONTEXT.md` predate this refactor.

## Responsibilities

- `ProfileController`: render account data and save validated personal details.
- `UpdateProfileRequest`: validate username, email, and phone number.
- `CategoryQuery`: retrieve navigation categories.
- `VendorQuery`: retrieve a user's stores with product counts, or resolve one owned store.
- `AccountVendorController`: handle HTTP requests and JSON/redirect responses for stores.
- `VendorRegistrationRequest`: validate new applications and derive the form identifier.
- `UpdateVendorRequest`: resolve ownership before validating edits or resubmissions.
- `VendorManagementService`: create stores, update approved/active stores, and resubmit rejected applications.
- `ChangePasswordController` and `ChangePasswordRequest`: own the authenticated password-change flow.

Store edits and resubmissions resolve the store through the authenticated user's
`vendors()` relation. Unknown and other users' store IDs return 404. The request
uses the resolved model for the unique-name exception, never a submitted ignore
ID. The service re-reads and locks the owned store inside a transaction before
checking its current status and writing changes. Submitted role, owner, and
status fields are not used.

## HTTP endpoints

All of these endpoints require authentication.

| Method | URL | Route name | Handler |
| --- | --- | --- | --- |
| GET | `/profile` | `profile` | `ProfileController::show` |
| PUT | `/profile` | `profile.update` | `ProfileController::update` |
| POST | `/account/vendors` | `profile.vendor.register` | `AccountVendorController::store` |
| PUT | `/account/vendors/{vendor}` | `profile.vendor.update` | `AccountVendorController::update` |
| POST | `/account/vendors/{vendor}/resubmit` | `profile.vendor.resubmit` | `AccountVendorController::resubmit` |
| PUT | `/account/password` | `password.change` | `ChangePasswordController::update` |

The old `/profile/vendor`, `/profile/vendor/register`, and `/profile/password`
URLs were replaced; there are no compatibility aliases. Existing vendor route
names were retained where applicable. First-time registration returns JSON 201;
updates and resubmissions return JSON 200. Browser submissions redirect to the
profile with status messages. Successful store submissions select the affected
store; validation failures restore the submitted form. Token-based password
recovery remains separate.

## Data and UI

A user can own multiple stores, independently of the account role. Account roles
are `customer` and `admin`. Admin approval, rejection, suspension, and reinstatement
change only the selected store's status, never its owner's role.

The original migration files were edited directly, as requested:

- `vendors.user_id` is a non-unique foreign key.
- `vendors.store_name` is unique, matching the existing registration requirement.
- `users.role` is restricted to `customer` and `admin`.

Seeders use customer accounts for store owners. `DatabaseSeeder` includes a
second pending store for the VoltTech owner. The obsolete `UserFactory::vendor()`
role state was removed; tests create stores through the ownership relation or
explicit vendor fixtures.

The hero card and navigation now lead to the store list. Individual store panes
show their own product count and status. Active/approved stores can be edited,
pending stores show application details, rejected stores offer resubmission, and
other statuses are read-only. New applications remain available to existing
store owners. Password inputs are never repopulated from old input.

## Verification status

PHP syntax checks and formatting completed. No PHPUnit tests, browser checks,
migrations, or seeders were run during this implementation, at the user's request.
The live database has not been updated by these source edits. Functional and
security verification remains pending, including the previously observed SQLite
incompatibility in the product-review CHECK-constraint migration (outside this change).

Tests were updated/split into profile, account-vendor, and password concerns.
Additional cases cover multiple stores, ownership isolation, rejected application
resubmission, status restrictions, and preservation of owner roles during admin
moderation. These tests have not been executed.

## Files changed in the backend integration step

- [app/Http/Controllers/AccountVendorController.php](../app/Http/Controllers/AccountVendorController.php)
- [app/Http/Controllers/Admin/AdminVendorController.php](../app/Http/Controllers/Admin/AdminVendorController.php)
- [app/Http/Controllers/Auth/ChangePasswordController.php](../app/Http/Controllers/Auth/ChangePasswordController.php)
- [app/Http/Controllers/ProfileController.php](../app/Http/Controllers/ProfileController.php)
- [app/Http/Requests/Auth/ChangePasswordRequest.php](../app/Http/Requests/Auth/ChangePasswordRequest.php)
- [app/Http/Requests/UpdateProfileRequest.php](../app/Http/Requests/UpdateProfileRequest.php)
- [app/Http/Requests/UpdateVendorRequest.php](../app/Http/Requests/UpdateVendorRequest.php)
- [app/Http/Requests/VendorRegistrationRequest.php](../app/Http/Requests/VendorRegistrationRequest.php)
- [app/Models/User.php](../app/Models/User.php)
- [app/Queries/Categories/CategoryQuery.php](../app/Queries/Categories/CategoryQuery.php)
- [app/Queries/Vendors/VendorQuery.php](../app/Queries/Vendors/VendorQuery.php)
- [app/Services/Vendors/VendorManagementService.php](../app/Services/Vendors/VendorManagementService.php)
- [database/factories/UserFactory.php](../database/factories/UserFactory.php)
- [database/migrations/0001_01_01_000000_create_users_table.php](../database/migrations/0001_01_01_000000_create_users_table.php)
- [database/migrations/2026_10_11_074002_create_vendors_table.php](../database/migrations/2026_10_11_074002_create_vendors_table.php)
- [database/seeders/DatabaseSeeder.php](../database/seeders/DatabaseSeeder.php)
- [database/seeders/UserSeeder.php](../database/seeders/UserSeeder.php)
- [docs/ACCOUNT_VENDOR_REFACTOR.md](../docs/ACCOUNT_VENDOR_REFACTOR.md)
- [resources/views/components/nav-bar.blade.php](../resources/views/components/nav-bar.blade.php)
- [resources/views/components/profile/hero-card.blade.php](../resources/views/components/profile/hero-card.blade.php)
- [resources/views/components/profile/profile-tab.blade.php](../resources/views/components/profile/profile-tab.blade.php)
- [resources/views/components/profile/security-tab.blade.php](../resources/views/components/profile/security-tab.blade.php)
- [resources/views/components/profile/vendor-registration-form.blade.php](../resources/views/components/profile/vendor-registration-form.blade.php)
- [resources/views/components/profile/vendor-store-pane.blade.php](../resources/views/components/profile/vendor-store-pane.blade.php)
- [resources/views/components/profile/vendor-tab.blade.php](../resources/views/components/profile/vendor-tab.blade.php)
- [resources/views/profile/pages/show.blade.php](../resources/views/profile/pages/show.blade.php)
- [routes/web.php](../routes/web.php)
- [tests/Feature/AccountVendorTest.php](../tests/Feature/AccountVendorTest.php)
- [tests/Feature/AdminVendorTest.php](../tests/Feature/AdminVendorTest.php)
- [tests/Feature/CategoryBrowseTest.php](../tests/Feature/CategoryBrowseTest.php)
- [tests/Feature/ChangePasswordTest.php](../tests/Feature/ChangePasswordTest.php)
- [tests/Feature/HomepageTest.php](../tests/Feature/HomepageTest.php)
- [tests/Feature/ProductDetailTest.php](../tests/Feature/ProductDetailTest.php)
- [tests/Feature/ProductFilterSortTest.php](../tests/Feature/ProductFilterSortTest.php)
- [tests/Feature/ProductSearchTest.php](../tests/Feature/ProductSearchTest.php)
- [tests/Feature/ProfileTest.php](../tests/Feature/ProfileTest.php)
