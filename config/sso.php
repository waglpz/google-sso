<?php

declare(strict_types=1);

$default = [
    'redirectUri' => PHP_SAPI !== 'cli' ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] : '',
    'accessType'  => 'online',
    'scopes'      => ['email', 'profile'],
    // value of 'state' to be auto generated. will show in response of google to check these is related our sent request
    'state'       => 'string.to.check.the.response.origin',
    'prompt'      => 'consent select_account', // can be 'none' for no any prompt by user
    // please change given path to the file contains real credentials from google
    'authConfig'  => $_SERVER['GOOGLE_SSO_CREDENTIAL_FILE'],
];

$envSpecificConfig = __DIR__ . '/sso/' . APP_ENV . '.php';

return is_file($envSpecificConfig) ? array_replace_recursive($default, include $envSpecificConfig) : $default;
