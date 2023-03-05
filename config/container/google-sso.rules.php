<?php

declare(strict_types=1);

use Waglpz\GoogleSSO\App;
use Waglpz\GoogleSSO\GoogleSSO;
use Waglpz\GoogleSSO\GoogleSSOPooreable;
use Waglpz\GoogleSSO\Login;
use Waglpz\GoogleSSO\LoginCallBack;
use Waglpz\Webapp\Security\AuthStoragePHPSession;

use function Waglpz\Config\config;

return [
    '*'              => [
        'substitutions' => [
            GoogleSSOPooreable::class => GoogleSSO::class,
            //GoogleUserGroupsFetchable::class => GoogleUserGroupsFetcher::class,
        ],
    ],
    //-> Remote Services
    //    GoogleUserGroupsFetcher::class => [
    //        'shared'          => true,
    //        'constructParams' => [config('google-groups-api')],
    //    ],
    '$defaultWebApp'                 => [
        'shared' => true,
        'instanceOf' =>  App::class,
    ],
    GoogleSSO::class => [
        'shared'          => true,
        'constructParams' => [config('sso')],
    ],
    LoginCallBack::class => [
        'shared'          => true,
        'constructParams' => [config('redirect_after_login')],
    ],
    Login::class => [
        'shared' => true,
        'constructParams' => [
            static function ($data) {
                $authStorage        = new AuthStoragePHPSession();
                $authStorage->email = $data['email'];

                return true;
            },
            static function ($data) {
                return false;
            },
        ],
    ],
    //<- Remote Services
];
