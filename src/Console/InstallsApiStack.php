<?php

namespace Laravel\Breeze\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait InstallsApiStack
{
    /**
     * Install the API Breeze stack.
     *
     * @return int|null
     */
    protected function installApiStack()
    {
        $this->runCommands(['php artisan install:api']);

        $files = new Filesystem;

        // Controllers...
        $files->ensureDirectoryExists(app_path('Http/Controllers/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Controllers/Auth', app_path('Http/Controllers/Auth'));

        // Middleware...
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Middleware', app_path('Http/Middleware'));

        $this->installMiddlewareAliases([
            'verified' => '\App\Http\Middleware\EnsureEmailIsVerified::class',
        ]);

        $this->installMiddleware([
            '\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class',
        ], 'api', 'prepend');

        // Requests...
        $files->ensureDirectoryExists(app_path('Http/Requests/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Requests/Auth', app_path('Http/Requests/Auth'));

        // Providers...
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Providers', app_path('Providers'));

        // Routes...
        copy(__DIR__.'/../../stubs/api/routes/api.php', base_path('routes/api.php'));
        copy(__DIR__.'/../../stubs/api/routes/web.php', base_path('routes/web.php'));
        copy(__DIR__.'/../../stubs/api/routes/auth.php', base_path('routes/auth.php'));

        // Configuration...
        $files->copyDirectory(__DIR__.'/../../stubs/api/config', config_path());

        // Environment...
        if (! $files->exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        file_put_contents(
            base_path('.env'),
            preg_replace('/APP_URL=(.*)/', 'APP_URL=http://localhost:8000'.PHP_EOL.'FRONTEND_URL=http://localhost:3000', file_get_contents(base_path('.env')))
        );

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        $files->delete(base_path('tests/Feature/Auth/PasswordConfirmationTest.php'));

        // Cleaning...
        $this->removeScaffoldingUnnecessaryForApis();

        $this->components->info('Breeze scaffolding installed successfully.');
    }

        /**
     * Install the API Breeze stack.
     *
     * @return int|null
     */
    protected function installApiWithVueStack()
    {
        $this->runCommands(['php artisan install:api']);

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@inertiajs/vue3' => '^1.0.0',
                '@tailwindcss/forms' => '^0.5.3',
                '@vitejs/plugin-vue' => '^5.0.0',
                'autoprefixer' => '^10.4.12',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.2.1',
                'vue' => '^3.4.0',
            ] + $packages;
        });

        $files = new Filesystem;

        // Controllers...
        $files->ensureDirectoryExists(app_path('Http/Controllers/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Controllers/Auth', app_path('Http/Controllers/Auth'));

        // Middleware...
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Middleware', app_path('Http/Middleware'));

        // Middleware...
        $this->installMiddlewareAliases([
            'verified' => '\App\Http\Middleware\EnsureEmailIsVerified::class',
        ]);

        $this->installMiddleware([
            '\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class',
        ], 'api', 'prepend');

        // Requests...
        $files->ensureDirectoryExists(app_path('Http/Requests/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Requests/Auth', app_path('Http/Requests/Auth'));

        // Providers...
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Providers', app_path('Providers'));

        // Views...
        copy(__DIR__.'/../../stubs/api-vue/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        @unlink(resource_path('views/welcome.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Pages'));

        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/api-vue/resources/js/Components', resource_path('js/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/api-vue/resources/js/Layouts', resource_path('js/Layouts'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/api-vue/resources/js/Pages', resource_path('js/Pages'));

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(resource_path('js'))
                ->name('*.vue')
                ->notName('Welcome.vue')
            );
        }

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        // Routes...
        copy(__DIR__.'/../../stubs/api/routes/api.php', base_path('routes/api.php'));
        copy(__DIR__.'/../../stubs/api/routes/web.php', base_path('routes/web.php'));
        copy(__DIR__.'/../../stubs/api/routes/auth.php', base_path('routes/auth.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', resource_path('css/app.css'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/inertia-common/tailwind.config.js', base_path('tailwind.config.js'));
        copy(__DIR__.'/../../stubs/api-vue/vite.config.js', base_path('vite.config.js'));

        copy(__DIR__.'/../../stubs/inertia-common/jsconfig.json', base_path('jsconfig.json'));
        copy(__DIR__.'/../../stubs/api-vue/resources/js/app.js', resource_path('js/app.js'));

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');

        // Configuration...
        $files->copyDirectory(__DIR__.'/../../stubs/api/config', config_path());

        // Environment...
        if (! $files->exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        file_put_contents(
            base_path('.env'),
            preg_replace('/APP_URL=(.*)/', 'APP_URL=http://localhost:8000'.PHP_EOL.'FRONTEND_URL=http://localhost:3000', file_get_contents(base_path('.env')))
        );


        $files->delete(base_path('tests/Feature/Auth/PasswordConfirmationTest.php'));

        $this->components->info('Breeze scaffolding installed successfully.');
    }

    /**
     * Remove any application scaffolding that isn't needed for APIs.
     *
     * @return void
     */
    protected function removeScaffoldingUnnecessaryForApis()
    {
        $files = new Filesystem;

        // Remove frontend related files...
        $files->delete(base_path('package.json'));
        $files->delete(base_path('vite.config.js'));

        // Remove Laravel "welcome" view...
        $files->delete(resource_path('views/welcome.blade.php'));
        $files->put(resource_path('views/.gitkeep'), PHP_EOL);

        // Remove CSS and JavaScript directories...
        $files->deleteDirectory(resource_path('css'));
        $files->deleteDirectory(resource_path('js'));
    }

}
