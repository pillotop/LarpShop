import { readdirSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

function entriesIn(directory, extension) {
    return readdirSync(directory, { recursive: true })
        .filter((file) => file.endsWith(extension))
        .sort()
        .map((file) => `${directory}/${file}`);
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                ...entriesIn('resources/css', '.css'),
                ...entriesIn('resources/js', '.js'),
            ],
            refresh: true,
        }),
    ],
});
