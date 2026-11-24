<?php

use Symfony\Component\Dotenv\Dotenv;

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';

require dirname(__DIR__).'/vendor/autoload.php';

// PHPUnit does not go through SymfonyRuntime, so load the same .env chain the
// app uses. `.env.local` is deliberately skipped in test; the JWT passphrase
// comes from the gitignored `.env.test.local` instead.
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
