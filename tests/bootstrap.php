<?php

// Checked out on its own (its own vendor/), or installed in an application's
// vendor/omnibase/health (a path repository's symlink: /srv/omnibase/health in
// the containers) - the application's autoloader then, which does not know
// this bundle's autoload-dev: the test namespace is registered here, and the
// classes under test are this checkout's.
foreach ([
    __DIR__.'/../vendor/autoload.php',
    getcwd().'/vendor/autoload.php',
    __DIR__.'/../../../autoload.php',
] as $autoload) {
    if (is_file($autoload)) {
        $loader = require $autoload;
        if ($loader instanceof \Composer\Autoload\ClassLoader) {
            $loader->addPsr4('Base\\Health\\Tests\\', __DIR__);
            $loader->addPsr4('Base\\Health\\', __DIR__.'/../src', true);
        }
        // Inside an application: its kernel booted once, so that omnibase's class aliases
        // (App\Entity\User and what it refers to) exist for the tests that stub an account.
        if (class_exists(\App\Kernel::class) && is_file(getcwd().'/.env')) {
            $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
            (new \Symfony\Component\Dotenv\Dotenv())->bootEnv(getcwd().'/.env');
            (new \App\Kernel('test', true))->boot();
        }

        return;
    }
}

throw new RuntimeException('No composer autoloader found.');
