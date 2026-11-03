# Profile mass-assignment privilege escalation demonstration

This deliberately vulnerable change is restricted to the `intentionally-vuln`
branch and local demonstrations with disposable test accounts.

## Affected endpoint and root cause

`PUT /profile` (`profile.update`), handled by `ProfileController::update()`.

The controller passes `$request->all()` to Eloquent instead of selecting approved
profile fields. `User` already includes `role` in its fillable attributes, so a
customer can submit `role=admin` and change their own privileges.

`UpdateProfileRequest` still validates username, email and phone number. However,
validation does not remove extra fields from `$request->all()`. The malicious
role field is not validated or authorized by this request.

Authentication, CSRF and the admin middleware remain enabled. The customer
becomes an admin in the database, so the normal admin role check subsequently
allows access. No role input was added to the visible profile form.

## Local reproduction

1. Sign in to the local application as a disposable customer account.
2. Confirm that opening `/admin` returns HTTP 403.
3. Intercept a normal profile update request using local testing tools. Preserve
   its existing session cookie, CSRF token, username and email fields.
4. Add the extra form field `role=admin` and submit the request to `PUT /profile`.
   With `Accept: application/json`, expect HTTP 200 and `user.role` equal to
   `admin`; a normal browser form submission redirects back to the profile.
5. Open `/admin` again using the same session. Expect HTTP 200.

An illustrative JSON body is below. Replace the name and email with those of the
same disposable account so that required fields and uniqueness validation pass:

```json
{
    "username": "demo_customer",
    "email": "demo_customer@example.test",
    "phone_number": null,
    "role": "admin"
}
```

The request still needs the authenticated session and a valid CSRF token. Do not
publish those values in screenshots or evidence. This demo changes persistent
account privileges; restore the disposable account through a trusted admin flow
after collecting evidence.

## Regression test

Run against the isolated test database configured in `phpunit.xml`:

```bash
php artisan test --filter=ProfileAuthorizationTest
```

The test intentionally asserts secure behavior: the role must remain `customer`.
It should fail on this branch because the actual role becomes `admin`. Do not
change that assertion to make the vulnerable branch appear secure.

## Remediation

Restore the explicit allowlist in `ProfileController::update()`:

```php
$data = $request->validated();
$user->update([
    'username' => $data['username'],
    'email' => $data['email'],
    'phone_number' => $data['phone_number'] ?? null,
]);
```

Keep role changes confined to authorized admin actions. Removing role from the
profile UI is insufficient; the server must ignore or reject submitted role
fields. Rerun the regression test after restoring the allowlist. Fixing this code
does not reverse privilege changes already made to demo accounts.

## Changed files

- `app/Http/Controllers/ProfileController.php`
- `tests/Feature/ProfileAuthorizationTest.php`
- `docs/PRIVILEGE_ESCALATION_DEMO.md`

`User.php` and `UpdateProfileRequest.php` were inspected but not changed. The
previous order-detail IDOR demonstration remains separate.
