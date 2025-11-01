<?php

use Symfony\Component\Dotenv\Dotenv;

if (file_exists(dirname(__DIR__).'/.env.test')) {
    (new Dotenv())->usePutenv()->loadEnv(dirname(__DIR__).'/.env', 'test');
}

