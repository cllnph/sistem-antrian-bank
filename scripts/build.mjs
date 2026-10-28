import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

// Set native pool sizes before Node initializes its thread pool. Keep CI overrides.
const vite = fileURLToPath(new URL('../node_modules/vite/bin/vite.js', import.meta.url));
const result = spawnSync(process.execPath, [vite, 'build', ...process.argv.slice(2)], {
    stdio: 'inherit',
    windowsHide: true,
    env: {
        ...process.env,
        UV_THREADPOOL_SIZE: process.env.UV_THREADPOOL_SIZE ?? '64',
        RAYON_NUM_THREADS: process.env.RAYON_NUM_THREADS ?? '2',
    },
});
if (result.error) throw result.error;
process.exitCode = result.status ?? 1;
