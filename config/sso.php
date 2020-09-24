<?php

declare(strict_types=1);

$default = [
    'redirectUri' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'],
    'accessType'  => 'online',
    'scopes'      => ['email', 'profile'],
    // value of 'state' to be auto generated. will show in response of google to check these is related our sent request
    'state'       => 'string.to.check.the.response.origin',
    'prompt'      => 'consent select_account', // can be 'none' for no any prompt by user
    // please change given path to the file contains real credentials from google
    'authConfig'  => __DIR__ . '/client_secret.apps.googleusercontent.com.json.example',
];

if (! \defined('APP_ENV')) {
    return $default;
}

$envSpecificConfig = __DIR__ . '/sso.' . APP_ENV . '.php';

/** @noinspection PhpIncludeInspection */
return \is_file($envSpecificConfig) ? \array_replace_recursive($default, include $envSpecificConfig) : $default;
