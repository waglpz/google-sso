<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO;

use Psr\Http\Message\ServerRequestInterface;

use function Waglpz\Webapp\dataFromRequest;

final class LoginCallBack
{
    public function __construct(
        readonly GoogleSSOPooreable $googleSSO,
        readonly string $redirectAfterLogin = '/',
    ) {
    }

    /** @throws \JsonException */
    public function __invoke(
        ServerRequestInterface $request,
        callable|null $onSuccess,
        callable|null $onFail,
    ): bool|string {
        $method      = $request->getMethod();
        $requestData = dataFromRequest($request);
        if ($method === 'GET') {
            if (! isset($requestData['code']) || ! \is_string($requestData['code']) || $requestData['code'] === '') {
                return $this->googleSSO->createAuthUrl();
            }

            $accountData = $this->googleSSO->fetchAccountDataUsingAuthorizationCode($requestData['code']);

            if (isset($accountData['email'])) {
                return $onSuccess !== null ? $onSuccess($accountData) : true;
            }

            return $onFail !== null ? $onFail($accountData) : false;
        }

        throw new \Error('Invalid HTTP method given ' . $method . ' allowed only GET.');
    }
}
