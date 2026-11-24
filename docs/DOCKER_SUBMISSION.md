# Docker submission and verification

## Recorded verification

Verified on 2026-11-24 on Linux amd64 using the working tree based on `68ba5fc`
plus the Docker hand-in and dead-code cleanup changes:

| Check | Result |
| --- | --- |
| Multi-stage Docker build and initial empty-volume setup | Passed |
| PHP runtime / Composer platform requirements | PHP 8.4.28; all requirements passed |
| Backend suite inside the final application image | 825 passed; 11,629 assertions; 36.53 seconds |
| Frontend suite inside the Node build image | 77 passed; 0 failed |
| HTTP catalog, built assets, images, login, and role boundaries | Passed |
| Laravel route and Blade view cache compilation | Passed |
| Storage after forced web-container recreation | Passed; temporary marker removed afterward |

The backend environment settings were corrected after the first Docker test runs
revealed that inherited runtime settings overrode test isolation. No application
CSRF protection was disabled and no assertions were removed to obtain this result.
These are functional acceptance results, not production load-test certification.

## Requirements and first run

Install Docker Engine with the Docker Compose plugin, or Docker Desktop running
Linux containers. The first build requires internet access to fetch base images,
OS packages, Composer packages, and npm packages. Run from a terminal with Docker
access; do not run the application setup with personal production credentials.

```bash
sh docker/demo.sh setup
```

The script can be invoked from any working directory. It uses only
`compose.demo.yaml` and its generated `.env.docker-demo`, explicitly avoiding the
developer's `.env` and `compose.override.yaml`. Host PHP, Node, Composer, and MySQL
are not required. Keep the working directory writable for initial credential
generation.

Setup builds `larpshop-demo:local`, generates a stable application key and random
database passwords, starts MySQL, runs additive migrations and demo seeders, starts
the web application and scheduler, then runs HTTP acceptance checks. Setup can be
repeated after source changes, but it reruns seeders and may restore demo fixtures;
use `start` when resuming an existing demonstration.

## Access and accounts

- Application: <http://localhost:8080>
- Captured email: <http://localhost:8026>

The app and email UI bind only to the local machine. MySQL is not published to the
host. All seeded demonstration accounts use the public demo password
`password123`:

| Actor | Email |
| --- | --- |
| Customer | `customer@example.test` |
| Vendor | `vendor@example.test` |
| Admin | `demo-admin@example.test` |

These are local demonstration credentials, not production accounts. Use separate
browser profiles/containers to demonstrate different accounts simultaneously.

Local wallet simulation is enabled. Real Stripe and domestic payment integrations
are disabled, and no real provider keys are supplied. Simulated payment outcomes
exercise the local application flow without transferring money. Google OAuth and
real PayPal integration require separate provider configuration and are outside
this credential-free acceptance run. Registration and password reset email can
be inspected in Mailpit.

## Persistence and repeat runs

```bash
sh docker/demo.sh stop
sh docker/demo.sh start
sh docker/demo.sh verify
sh docker/demo.sh logs
```

The fixed `larpshop-demo` project uses its own `demo_mysql` and `demo_storage`
volumes, separate from the normal development stack's `mysql_data`. Uploaded and
private files are kept in the storage volume; bundled product images are
republished and the public-storage link is prepared at web startup. Restarting
does not drop databases or reseed them.

Preserve `.env.docker-demo` along with its volumes: changing the application key
invalidates encrypted/session data, and changing database passwords does not
change credentials already stored in an initialized MySQL volume. The generated
file is ignored by Git and Docker's build context. Local uploaded files, identity
documents, logs, compiled views, host Vite markers, and host dependency folders
are excluded from the image context.

To change ports, edit `.env.docker-demo` and add `DEMO_HTTP_PORT=8081` or
`DEMO_MAIL_PORT=8027`, then run `start`. Do not run two copies using the same
project name concurrently.

## Verification

```bash
sh docker/demo.sh verify
sh docker/demo.sh test
```

HTTP acceptance checks cover the running server, public catalog/deferred sections,
compiled asset delivery, local product image delivery, seeded logins, customer
denial from admin reports, and rendered vendor/admin pages. They do not submit
orders, contact real payment gateways, or establish browser visual correctness.

The test command creates/grants access to `larpshop_testing` in the isolated demo
MySQL container, then runs PHPUnit inside the built application image. The test
database can be recreated by tests; the demo `larpshop` database is separate.
The command explicitly sets `APP_ENV=testing` for the test process rather than
inheriting the running server's `local` environment. This keeps Laravel's test
middleware behavior and test-only gateways consistent inside Docker.
PHPUnit also forces its cache, session, mail, and queue settings through server
variables so the container's runtime settings cannot leak into isolated tests.
Concurrency tests launch PHP subprocesses inside that same image.

Frontend tests can be run without installing Node on the host:

```bash
docker build --target frontend -t larpshop-demo:frontend .
docker run --rm --entrypoint node -v "$PWD/tests:/app/tests:ro" larpshop-demo:frontend --test tests/Frontend/*.test.mjs tests/js/*.test.mjs
```

## Runtime and cleanup scope

The image includes Composer development dependencies because demo seeding needs
Faker and the hand-in must support PHPUnit. Node is used only in the frontend
build stage. The final image serves bundled Vite assets without a Node server.
The scheduler handles expiry/reconciliation; notifications use the synchronous
queue driver, so no separate queue worker is needed in this demo.

The supplied PHP CLI development server is suitable for local assessment, not a
production serving architecture. Production deployment, load testing, and live
provider certification are not implied by a successful Docker acceptance run.

Dead-code cleanup removes retired raw HTML prototypes, unreachable placeholder
Blade pages, duplicate pre-Vite `public/js` scripts, an unused product-card script,
the unused `CartRetrievalService_` stub, and commented obsolete routes. Active
Blade pages, payment adapters, migrations, and their regression tests are retained.
