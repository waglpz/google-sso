## Google SSO Client Library for PHP

The Google SSO Client Library enables you to work with single sign on via Google API.

### Requirements

PHP 8.2 or higher

### Installation

composer require waglpz/google-sso:"^1.0"

## Authentication with OAuth

1. Follow the instructions to [Create Web Application Credentials](https://github.com/googleapis/google-api-php-client/blob/master/docs/oauth-web.md)
1. Download the JSON credentials in some hidden directory and include this one path in config.
1. Set the path to these credentials using config `authConfig`.
1. Set the scopes required for the API you are going to call using config key `scopes`
1. Set your application's redirect URI in config
1. Set expected prompt when redirect to google. These can `none`, `consent` or `select_account`.
1. In the script handling the redirect URI, exchange the authorization code for an access token:
###### Example 

  ```php
    $config = include __DIR__ . '/../config/sso.php';
    $googleSSO = new \GoogleSSO\GoogleSSO($config);
    $authorizationCodeUrl = $googleSSO->createAuthUrl();
    // Go to the $authorizationCodeUrl and select account you will authenticate against.
    // these will redirect you to defined/known redirect URI and a PHP script will use the code which sends back from Google.
    $accountData = $googleSSO->fetchAccountDataUsingAuthorizationCode($_GET['code']);
    // $accountData contains necessary information if one was founded by google
  ```

## Code Quality and Testing ##

To check for coding style violations, run

```
composer waglpz:code:style:check
```

To automatically fix (fixable) coding style violations, run

```
composer waglpz:code:style:fix
```

To check for static type violations, run

```
waglpz:code:analyse
```

To check for regressions, run

```
composer waglpz:test:norma
```

To check all violations at once, run

```
composer waglpz:check:normal
```
