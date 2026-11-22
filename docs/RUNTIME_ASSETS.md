# Runtime image preparation

The Docker start command runs `docker/start.sh`. Before serving requests, it clears stale configuration and runs `php artisan assets:prepare`.

This command copies the bundled images from `database/seeders/assets/products/` to the configured public disk's `demo-products/` directory and creates `public/storage` pointing to that disk. It does not run database seeders, migrations, or change product records. Existing uploads under `products/` are preserved. An actual directory at `public/storage` is never deleted automatically; preparation fails so its contents can be inspected safely.

For an already running installation, the image preparation command can also be run manually:

```sh
php artisan assets:prepare
```

## Render configuration

- Set `APP_URL` to the actual public URL, including the correct hostname and HTTPS scheme.
- Leave the Docker command override empty so the image's startup script runs. An override must invoke `sh docker/start.sh` if one is required.
- Keep `PUBLIC_STORAGE_PATH` pointed to a directory inside an attached persistent disk for uploaded images to survive deployments. The default `storage/app/public` directory is only durable when that directory is backed by persistent storage.
- Run preparation in the running container, after disks are mounted. Preparing files in Render's pre-deploy command does not populate the running container's filesystem.
- Before changing an existing `PUBLIC_STORAGE_PATH`, copy existing uploads to the new location. Recreating database rows cannot recover missing uploaded files.

Repository-owned banner images use `/images/...` URLs on the current origin. Their display no longer depends on `APP_URL`. Uploaded banner images continue to use the configured public storage disk.

After deployment, verify a bundled banner such as `/images/banners/care-960.webp` and a seeded product image such as `/storage/demo-products/dumbbell-gym.jpg`. Both should return an image response with HTTP 200.
