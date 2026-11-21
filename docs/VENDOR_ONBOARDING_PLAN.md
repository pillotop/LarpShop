# Vendor onboarding plan

## Stage 1: architecture and scope

Status: stages 1–5 completed for the agreed onboarding scope. The previously
recorded checkout-test and starter-view compilation issues are now resolved.
Branch inspected: `feature/vendor-onboarding`.

The first release replaces the inline new-store registration form with a dedicated,
authenticated onboarding page. It supports saving a draft, resuming it, submitting
for manual review, and correcting a rejected application. An account can own
multiple stores; onboarding does not change the account's customer/admin role.

### Verified integration points

- `routes/web.php` currently registers authenticated create/resubmit actions under
  `/account/vendors`. They use `AccountVendorController`, `VendorRegistrationRequest`,
  `UpdateVendorRequest`, and `VendorManagementService`.
- Registration immediately creates a `vendors` row with status `pending`.
- `VendorQuery::forUser()` includes pending and rejected stores in profile tabs.
- `Vendor::active()` accepts both `approved` and `active`. `EnsureActiveVendor`
  depends on this scope to authorize vendor management pages.
- `vendors.store_name` is required and unique at the database level. The user
  foreign key is not unique, so multiple stores per account already work.
- `AdminVendorController` currently writes statuses directly, without checking
  the previous status. The new application workflow needs explicit transition
  guards and synchronized updates, including the admin edit endpoint.
- `Address::snapshot()` already returns a suitable international address shape.
  `Vendor::profile_image_url` already reads `brand_path` from the public disk.

### User journey

1. Profile > Stores > **Create new store** opens the onboarding page.
2. The owner enters seller, store, and shipping details, with Back/Next navigation.
3. **Save and exit** persists a draft without submitting it for review.
4. Profile > Stores lists saved applications separately from actual vendor tabs,
   with a **Continue application** action.
5. **Review and submit** validates the complete persisted application. A successful
   first submission creates one pending vendor and links it to the application.
6. Submitted applications are read-only to the owner while awaiting review.
7. Rejection shows a reason and permits correction and resubmission for the same
   application/vendor. Approval enables the existing store-management flow.

The creation page is a GET with no database writes. The first explicit save
creates the draft. Multiple drafts are allowed for an account's different proposed
stores; continuing an existing draft uses its ID, not a newly created record.

### Steps and fields

The following requirements apply at final submission. Draft saves accept missing
fields but validate the types, lengths and allowed values of supplied fields.

| Step | Field | Submission requirement |
| --- | --- | --- |
| Seller information | `seller_type` | Required: `individual` or `business` |
| Seller information | `seller_country_code` | Required country of residence for an individual, or business registration country |
| Seller information | `business_name` | Required for business sellers; excluded for individuals |
| Seller information | `contact_name` | Required, maximum 150 characters; use a full-name field |
| Seller information | `contact_email` | Required valid email, maximum 100 characters |
| Seller information | `contact_phone` | Required, maximum 30 characters; accept international formatting, no OTP or claim of verification |
| Store details | `store_name` | Required, 2–100 characters, unique among vendors at submission |
| Store details | `description` | Optional, maximum 1000 characters |
| Store details | `brand_image` | Optional JPG/PNG/WebP, at most 2 MB and 4096 × 4096 pixels |
| Shipping information | Pickup recipient and phone | Required, maximum 100/30 characters respectively |
| Shipping information | Pickup country, address line 1, city | Required, maximum 255/100 characters for address line 1/city |
| Shipping information | Pickup address line 2, region, postal code | Optional, maximum 255/100/30 characters respectively |
| Shipping information | `delivery_mode` | Required explicit selection: `domestic`, `selected_countries`, or `worldwide` |
| Shipping information | Destination country codes | Required nonempty selection only for `selected_countries`; deduplicated and excluded for other modes |
| Review and submit | Confirmation | Required acknowledgement that the submitted information is accurate |

Country codes are uppercase ISO alpha-2 values checked against a shared country
catalog, not just arbitrary two-letter strings. Labels are localized. Region and
postal-code fields remain optional because address structures vary by country.
No country-specific province/district lookup is required for the first release.

Seller country, pickup country and destination countries are distinct concepts.
`domestic` means the pickup country. Destination declarations are informative for
admin review in this release: they do not enable/disable checkout, calculate fees,
or choose carriers. Any later enforcement must use the selected shipping address,
not browser location, language, IP address, or application domain.

Contact values may be prefilled from the account and the pickup address may be
copied from an address owned by that account. Copy only the allowlisted snapshot;
do not retain a live reference or modify the customer's saved address. Explicit
seller contact changes do not update the User model.

Brand-ownership questionnaires, trademark questions, UPC, tax details, identity
documents, citizenship, birthplace, credit cards, subscriptions and SMS verification
are outside this release. They have no implemented decision or verification flow
to support them. Date of birth and gender remain optional profile information,
not new onboarding requirements.

### Data boundaries

Use a separate `vendor_applications` table. Do not put `draft` into `vendors.status`
or make existing vendor columns nullable for unfinished applications.

Proposed `vendor_applications` columns:

- `vendor_application_id`: primary key.
- `user_id`: required owner, references `users.user_id`.
- `vendor_id`: nullable unique reference to `vendors.vendor_id`, assigned at first
  submission. Later submissions reuse this link.
- `status`: required, default `draft`; allowed values below.
- `current_step`: a navigation hint from an allowlist, default `seller`. It is not
  proof of completion; the server determines completeness from persisted fields.
- Nullable draft fields: seller type/country, business name, contact fields,
  store name/description, image `brand_path`, pickup fields prefixed `pickup_`,
  and delivery mode. Use typed columns, not an opaque form-payload JSON blob.
- `submitted_at`, `reviewed_at`, nullable `reviewed_by` referencing `users.user_id`,
  and `rejection_reason` (maximum 2000 characters).
- Standard timestamps.

Use `vendor_application_destinations` for selected countries:

- `vendor_application_destination_id`: primary key.
- `vendor_application_id`: foreign key to the application.
- `country_code`: validated two-character code.
- Unique pair `(vendor_application_id, country_code)`.

Application data is an onboarding/review record, not the live storefront settings.
On submission, copy the approved submission fields needed by the existing vendor
model: name, description and image path, with status `pending`. On approval,
change status; do not copy account roles or expose private contact/address data.
Later store name/image/description updates retain their existing endpoints and do
not rewrite the historical approved application.

An uploaded logo uses the existing public disk and a generated filename under an
application-owned directory. An approved vendor may reference that same logo.
Replacement/cleanup must account for that reference and never delete another
application's file or a logo still used by a vendor. Failed database saves must
clean up newly written files. No identity-document upload is introduced.

### State transitions and consistency

| Action | Application before → after | Vendor effect |
| --- | --- | --- |
| Save draft | `draft` → `draft` | No vendor created |
| First submission | `draft` → `submitted` | Create exactly one `pending` vendor |
| Approve | `submitted` → `approved` | Linked `pending` vendor → `approved` |
| Reject, with reason | `submitted` → `rejected` | Linked `pending` vendor → `rejected` |
| Correct rejected application | `rejected` → `rejected` | Do not activate or publish edits |
| Resubmit | `rejected` → `submitted` | Same linked vendor → `pending`; update submission fields |
| Suspend/reinstate approved store | Application remains `approved` | Existing store lifecycle, guarded separately |

Use transactions and row locks for submission and review. Repeated submission of
an already submitted application returns the existing result without creating a
second vendor. A name collision discovered at the unique constraint returns a
field validation error and leaves the application editable. Drafts do not reserve
store names, and uniqueness ignores only the application's verified linked vendor.

Pending or approved applications cannot be edited through draft endpoints. Review
actions require admin authorization and the expected current state; admin generic
vendor updates must not bypass the same rules. Creation, submission and approval
never change `users.role`.

The first release keeps the latest review metadata, not a full revision/audit
system. On resubmission, preserve the previous rejection reason as previous-review
feedback until a new decision replaces it; `submitted_at` records the new submission.
Approval clears the current rejection reason. Owner-visible application responses
must not reveal other applicants' private data.

### Compatibility and route design

- Additive migrations only. Preserve existing vendors, products, orders, user roles,
  and both existing active-store values (`active`, `approved`). No fresh migration
  or fabricated onboarding data for existing sellers.
- A vendor without an application is a legacy vendor. Existing pending applications
  remain reviewable, clearly labeled as legacy. Existing approved/active vendors
  continue operating without retroactive mandatory onboarding.
- Reapplying for a legacy rejected vendor creates/links an application from its
  existing name/description and requires the new submission fields before sending.
  It never creates a second vendor for that reapplication.
- New registration cannot keep a two-field POST path that bypasses onboarding.
  Adapt or retire that handler at rollout; update its consumers and tests explicitly.
  Keep normal approved-store editing separate from application submission validation.
- Onboarding routes use `auth` and ownership checks, not `vendor` middleware:
  first-time applicants do not have an active store yet.
- Proposed route family: `/account/vendor-applications/create` (GET),
  `/account/vendor-applications` (POST draft),
  `/account/vendor-applications/{application}` (GET/PATCH), and
  `/account/vendor-applications/{application}/submit` (POST).
- Bind by the custom application key and scope access to the authenticated owner.
  GET routes do not save, submit, approve, reject, or create drafts.
- Replace the profile's `tab-vendor-new` button/inline pane with a real link. Leave
  existing store tabs intact; show draft/continue actions separately. Old
  `#pane-vendor-new` links should open Stores and expose the new creation action.

### Responsibilities and affected areas

Planned new classes:

- `VendorApplication`, `VendorApplicationDestination`: persistence and relations.
- Application-status, seller-type, step, and delivery-mode enums: allowlists.
- `VendorApplicationQuery`: owner/admin application retrieval.
- `VendorOnboardingService`: draft persistence, final submission and resubmission.
- `VendorApplicationReviewService`: synchronized admin decisions and transition guards.
- Onboarding Form Requests: save/advance validation and full submit validation,
  sharing field rules rather than duplicating them between steps.
- `VendorOnboardingController`: page/action orchestration only.
- A shared country catalog: valid codes and localized display names.

Existing integration areas:

- `routes/web.php` / a dedicated onboarding routes file.
- `AccountVendorController`, `VendorRegistrationRequest`, `UpdateVendorRequest`,
  and `VendorManagementService`: remove submission bypasses without coupling live
  store edits to onboarding rules.
- `AdminVendorController`, `AdminUpdateVendorRequest`, and admin vendor views:
  application details, rejection feedback, synchronized review actions.
- `User`, `Vendor`, and profile queries/controller: application relations and drafts.
- Profile `vendor-tab`, `vendor-store-pane`, registration component and tab-navigation
  compatibility handling.
- New onboarding page/components, Alpine state/transition module, scoped styles,
  English/Vietnamese translations, factories and feature/frontend tests.

Alpine owns current visible step, transitions and temporary form UI. Laravel owns
persisted data, field validation, permissions and lifecycle transitions. No Carousel,
automatic advance, swipe-to-skip, client-supplied status, or reliance on browser
storage for resume. Provide step focus/error focus and reduced-motion support.

### Delivery stages and review gates

1. **This stage:** document the field contract, data boundaries, state transitions,
   compatibility and affected areas. No runtime changes.
2. **Backend foundation:** migrations, models/enums, query/service/request layers,
   authenticated draft/submit actions, country catalog and isolated tests.
3. **Onboarding UI:** dedicated page, four steps, Save and exit/resume, responsive
   transitions, profile entry point and EN/VI text. Reuse Bootstrap/Alpine and
   existing address/image conventions; use at most `rounded-2` for containers.
4. **Review integration:** admin application detail/reject/approve/resubmit flow,
   legacy handling, and closure of old registration/status-update bypasses. Do not
   deploy the new flow as complete before these paths are integrated.
5. **Final verification:** complete owner/admin journeys, guest and cross-owner
   denial, partial/invalid drafts, conditional fields, uniqueness races, duplicate
   submissions, rejected resubmission, logo cleanup, legacy fixtures, localization,
   responsive layout, keyboard navigation and reduced motion.

Stop for user review between stages. No commits, pushes, branch changes, production
database changes, or paid services are part of these stages without authorization.

## Stage 2 implementation notes

Stage 2 introduced JSON endpoints without changing profile or admin entry points.
Stage 3 adds HTML responses for ordinary create/show GETs; requests accepting JSON
retain the owner-scoped API contract. Admin integration remains pending: do not
deploy this intermediate state as the completed onboarding overhaul.

### Endpoint contract

All endpoints require authentication through the existing web/session stack.
State-changing requests require CSRF protection. Send `Accept: application/json`
for JSON validation errors. Application route IDs use `vendor_application_id`.

| Method | Route name | Purpose |
| --- | --- | --- |
| GET | `vendor-applications.index` | Paginated list of the current user's applications |
| GET | `vendor-applications.create` | Prefill suggestions, step codes and localized countries; no writes |
| POST | `vendor-applications.store` | Save a new partial draft; HTTP 201 |
| GET | `vendor-applications.show` | Resume/read an owned application |
| PATCH | `vendor-applications.update` | Save supplied fields of an editable owned application |
| POST | `vendor-applications.submit` | Validate the persisted application and submit it |

POST/PATCH draft payloads accept the fields listed above. Additional controls:

- `current_step`: one of `seller`, `store`, `shipping`, `review`; a navigation hint.
- `validate_step`: optional `seller`, `store` or `shipping`. Validate that complete
  section against saved plus incoming values before committing the draft save.
  An invalid section leaves both fields and the navigation hint unchanged.
- `pickup_address_id`: optional ID of an address owned by the applicant. Its snapshot
  fills the pickup fields; it is not stored as a live reference.
- `destination_countries`: array of country codes. Input codes are uppercased and
  duplicates removed. Selecting another delivery mode clears old destinations.
- `brand_image`: optional multipart raster-image upload. For PHP multipart updates,
  send POST with `_method=PATCH` to the update URL.
- `remove_brand_image`: optional boolean; cannot be combined with a new image.

Missing optional fields keep saved values; explicit null clears nullable fields.
Draft saves validate provided data even when no step validation is requested.
Switching to `individual` clears the previous business name.

Submission accepts `confirmation=true` (accepted form equivalents also work).
Other posted fields do not override the persisted application. Save changes first,
then submit. A retry of an already submitted application returns the same linked
vendor. Submitted and approved applications cannot be edited through draft routes.
No step trusts client-supplied lifecycle, owner, vendor-link, review or image-path
fields. Normal store-management and account-role behavior is unchanged.

### Implementation choices

- Keep four small enums and two models rather than introducing a workflow engine.
- Share field validation in `VendorApplicationRules` between draft, section and
  full submission checks. Keep file validation at the HTTP boundary.
- Store each destination in a child row with a unique application/country pair.
- Use `CountryCatalog` with bundled EN/VI country files: 249 ISO 3166-1 codes from
  the system iso-codes dataset, with ICU display names. Runtime requests need
  neither an external API nor the PHP intl extension.
- Use `VendorApplicationQuery` for owner-scoped reads and row locking;
  `VendorOnboardingService` handles persistence, image cleanup and submission.
- Copy existing address snapshots and reuse the existing public storage disk.
- Add only new tables and relations. Existing vendor statuses and records are not
  migrated or backfilled. Review metadata columns are reserved for stage 4.

Only the isolated test database has been migrated during verification. Before
using the backend locally or after an intentional deployment, run normal
`php artisan migrate`; do not use `migrate:fresh` on existing application data.

The backend tests cover partial drafts, ownership and guest access, immutable
submitted/approved applications, stored-data validation, conditional fields,
country validation/localization, address snapshots, duplicate submissions, unique
name collisions after validation, rejected resubmission, unchanged legacy stores,
file validation/replacement/removal, shared-logo retention, storage/database failure
cleanup and hosted CSRF protection. True parallel submission testing remains part
of the final verification stage; stage 2 exercises retries and database constraints.

Stage 2 verification: 32 onboarding tests passed (205 assertions). The full PHP
suite passed 697 of 698 tests; the remaining failure is
`CheckoutPageTest::test_checkout_shows_creation_resume_and_processing_states`
at its creation-state assertion. It forbids the text "Continue payment", which
the existing wallet form now uses as its submit label. Stage 2 changes neither
checkout views nor that test; this mismatch is recorded separately from onboarding.
Pint and whitespace checks passed. No development/Render database was migrated.

## Stage 3 implementation notes

The dedicated onboarding page now provides Seller information, Store details,
Shipping information, and Review and submit steps. Small Blade components handle
fields, individual steps and the review summary. Alpine manages temporary inputs,
transitions, loading, error focus and image previews; Laravel remains authoritative
for validation, draft persistence and submission.

- Next saves and validates the current section before advancing. Back preserves
  inputs, while Save and exit permits incomplete drafts. The first successful save
  replaces the create URL with the saved application URL.
- Profile Stores links to the new page and lists paginated draft/rejected
  applications for resuming. Existing stores retain their separate tabs.
- Submitted/approved application pages are read-only. Rejected applications show
  previous feedback and can be edited for resubmission.
- Owned saved addresses can populate pickup fields; selected destination countries
  have a searchable checklist. An explicit multipart `destination_countries=[]`
  value is normalized to an empty array so clearing selections is unambiguous.
- Requests include session credentials and CSRF protection. Inputs survive failed
  saves. An uncertain first creation response prevents another create attempt and
  directs the applicant to check saved drafts instead of risking duplicates.
- English/Vietnamese text, theme-aware colors, responsive fields, keyboard/error
  focus and reduced-motion behavior are included. JavaScript is required for this
  interactive form; a no-script notice explains this requirement.

Stage 3 verification: 59 targeted PHP tests passed (411 assertions), covering
onboarding and affected profile flows. Sixteen frontend tests passed across the
onboarding and profile-navigation files. The production asset build and Pint passed.
A fixture-only browser preview confirmed Alpine initialization and the dark-theme
layout without querying or writing the development database.

Before opening Profile Stores or onboarding against an existing database, run
normal `php artisan migrate` to create the new application tables. This stage has
not migrated development or Render databases.

Stage 3 was reviewed before proceeding to the review integration below.

## Stage 4 implementation notes

Admin vendor detail pages now display the application snapshot, destination countries,
previous feedback, reviewer and review dates. The vendor list and dashboard link to
these details instead of posting a rejection without a reason. Approve/reject controls
are separated from live store configuration. All new copy has EN/VI translations.

`VendorApplicationReviewService` owns transactional admin decisions and availability
changes. It locks applications before their vendors, verifies matching ownership and
states, and retries deadlocks up to three times. Approval/rejection updates both
records together and records the reviewer/date; rejection requires a reason. Approval
clears previous feedback. Suspension and reinstatement preserve the approved review
snapshot; reinstatement accepts suspended stores only, never rejected applications.
Generic configuration prohibits the status field and accepts live information edits
only for approved/active/suspended stores with an approved application or no legacy
application. Pending/rejected configuration links redirect to the review page.

Legacy compatibility uses actual available information:

- Pending legacy stores can still be reviewed and approved without invented fields.
- Rejecting a legacy pending store creates a partial rejected application containing
  its existing store information and the actual review reason/reviewer/date. Missing
  seller/shipping information and the unknown original submission date remain null.
- Reapplying for an already rejected legacy store initializes one linked partial
  application through POST. Repeated requests resume it; cross-owner requests fail.
  The owner must complete the new fields before submitting the same vendor again.
- Existing approved/active legacy stores remain operational without backfilling.

The old `profile.vendor.register` POST no longer creates stores: browser clients get
303 to onboarding; JSON clients get 422 with a `redirect_url`. The old
`profile.vendor.resubmit` POST now opens/initializes the owned rejected application's
onboarding flow (303 for HTML, application plus redirect URL for JSON), ignoring old
posted store fields. It does not set the vendor pending. The old inline registration
component is a compatibility link, and the raw register/resubmit service methods have
been removed. Approved-store editing stays separate from onboarding.

Live logo replacement now retains any old file referenced by an application or other
vendor, preserving the review snapshot. No new migration or external integration was
introduced in stage 4, and no development/Render database was migrated.

Verification: all 14 new review tests pass, including full legacy resubmission,
state guards, cross-owner/non-admin denial, rollback on failed vendor writes, snapshot
image retention, escaped output and Vietnamese rendering. Related profile/admin/store
and translation tests also pass. The full PHP suite passes 715 of 716 tests (8691
assertions); the sole failure is the previously recorded checkout label assertion at
`CheckoutPageTest.php:88`. Pint and whitespace checks pass. A global `view:cache`
check encounters the existing missing `application-logo` component in starter auth
views; affected onboarding/admin views render successfully in feature tests.

Stage 4 was reviewed before proceeding to the final verification below.

## Stage 5 verification and completion

A small native language select and text-style Language submit button sit at the
bottom of onboarding, using the existing session-backed locale endpoint. Dirty application
fields are saved before posting the locale form; invalid or uncertain saves keep the
user on the current page with their entries intact. A pristine create page can change
language without creating a draft. Read-only applications also expose this control.
The select uses `rounded-2`; the shared table-based switcher elsewhere is unchanged.

### Completed checks

| Area | Evidence |
| --- | --- |
| Browser owner journey | Created a draft, advanced sections, triggered a required-name error, saved/exited and resumed from Profile Stores at the saved shipping step |
| Locale persistence | Changed EN to VI with unsaved contact-name edits; the first draft was saved, the URL changed to its application URL, and the translated page retained the edited value |
| Browser admin/owner round trip | Submitted the application; admin read and rejected it with feedback; owner reopened it, corrected the description and resubmitted; admin approved the same store and previous rejection feedback was cleared |
| Keyboard and focus | Invalid Next focused the store-name field; section changes focused their heading; Tab moved between visible form controls; Enter expanded the language control |
| Responsive layout | At 390 px the fields and summary rows used one column, page width did not exceed the viewport, and progress navigation remained internally scrollable; desktop used two-column fields |
| Motion | Reviewed the reduced-motion CSS override: section transitions are disabled and translation is removed when the preference is enabled; no cross-platform animation claim is made |
| Simultaneous submission | Two independent HTTP workers submitted the same committed draft: both returned the same linked vendor, with exactly one vendor row |
| Conflicting admin decisions | Concurrent approve/reject requests produced one successful decision and one 422; application/vendor state and review metadata matched the winner |
| Submission versus approval | A repeated submission could not return an approved store to pending or create another store |
| Legacy resume race | Two concurrent resume requests for a rejected legacy vendor returned one shared application and left the store rejected until full submission |
| Security regression | Owner/admin boundaries, immutable states, posted-field allowlists, CSRF, escaped output, conditional validation, uploaded images and rollback remain covered by the feature suites |

Browser checks used disposable factory accounts and the actual application on
`127.0.0.1:8772`, configured explicitly for `larpshop_testing` with a separate session
cookie. The temporary server has been stopped. No development or Render data was
used, no external message/payment was sent, and no production migration was run.

The concurrency suite uses `DatabaseTruncation` because workers need committed
fixtures. Both the parent and worker require the exact `larpshop_testing` database;
workers use separate processes/connections and synchronize their start. A short
saving-event delay makes their row-lock contention observable. It requires MySQL
and `proc_open`; it ran successfully here (four tests, 50 assertions).

### Final verification results

- Full PHP suite: **719 of 720 tests passed**, **8748 assertions**. The sole failure
  remains the existing checkout label assertion at `CheckoutPageTest.php:88`.
- Frontend onboarding/profile navigation: **19 tests passed**, including three new
  locale-save/recovery cases.
- Production Vite build, Pint for the added PHP tests/worker, and whitespace checks
  passed. No new frontend dependency was installed.
- Global Blade caching still encounters the previously recorded missing
  `application-logo` in the starter guest layout. The implemented onboarding and
  admin pages rendered successfully in both feature tests and browser checks.

All five agreed onboarding stages are complete. Browser verification was performed
in the available in-app browser; this is not a claim of separate Firefox/Safari or
assistive-device certification. Existing deployment instructions still require
normal `php artisan migrate` for the additive application tables and a fresh asset
build. No commit, push, merge or deployment has been performed.

### Files touched in the final stage

- `resources/views/vendor/onboarding/show.blade.php`
- `resources/views/components/locale-switcher.blade.php`
- `resources/js/components/vendor_onboarding.js`
- `resources/css/vendor_onboarding.css`
- `lang/en/onboarding.php`, `lang/vi/onboarding.php`
- `tests/Feature/VendorOnboarding/VendorOnboardingPageTest.php`
- `tests/Feature/VendorOnboarding/VendorOnboardingConcurrencyTest.php`
- `tests/Support/vendor-onboarding-worker.php`
- `tests/Frontend/vendor_onboarding.test.mjs`
- `docs/VENDOR_ONBOARDING_PLAN.md`

### Language control presentation correction

The final footer uses an inline native HTML `<select>` and a text-style Language
submit button. It replaces the expandable table, while retaining save-before-locale
behavior. The shared table-based switcher used elsewhere is unchanged.

The language control is now fixed at the bottom-right of the viewport. Language is
a label, and changing the native select submits automatically through the existing
save-before-locale handler. Bottom padding keeps final form actions clear of the
fixed control. Onboarding and admin application labels capitalize their first
character at presentation time; validation attribute translations stay lowercase
so they continue to fit naturally inside validation sentences.

### Follow-up: pre-existing verification blockers resolved

The checkout state test now checks creation/resume capability flags, the creation
form action and the specific continuation URL instead of forbidding the shared
Continue payment label. The label is legitimately used by the wallet creation form;
payment behavior was not changed to satisfy the test.

The guest layout now renders the existing LarpShop brand image directly instead of
referencing the missing Breeze application-logo component. Global Blade compilation
then exposed missing input-label and related components in three orphaned Volt
templates. Repository reference checks confirmed these templates are unused; they
were removed rather than restoring the retired Breeze UI and account actions:

- `resources/views/profile/pages/delete-user-form.blade.php`
- `resources/views/profile/pages/update-password-form.blade.php`
- `resources/views/profile/pages/update-profile-information-form.blade.php`

Active profile/security flows and their existing components remain in place.
Final verification after these fixes: **720/720 PHP tests passed, 8767 assertions**;
`php artisan view:cache`, Pint and whitespace checks passed. This supersedes the
earlier historical test and Blade-cache failure notes. No development database
migration, commit, push or deployment was performed.
