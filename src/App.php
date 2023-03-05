<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO;

use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Route\Url;
use Waglpz\Webapp\App as WrappedApp;
use Waglpz\Webapp\Security\AuthStoragePHPSession;
use Waglpz\Webapp\WebController;

final class App
{
    private string $loginUri;
    private string $apiUri;

    public function __construct(
        private readonly WrappedApp $app,
        private readonly ServerRequestInterface $request,
        AuthStoragePHPSession $authStorage,
    ) {
        $this->apiUri  = '/api';
        $requestTarget = $request->getRequestTarget();

        if ((\session_status() === \PHP_SESSION_NONE) && ! \str_starts_with($requestTarget, $this->apiUri)) {
            \session_start();
        }

        if ($requestTarget === '/logout') {
            $_SESSION = [];
            \session_destroy();
            WebController::redirect('/');
        }

        $this->loginUri = Url::forRoute('/login', null, null, Url::RETAIN_NOT);
        if (
            isset($authStorage->email)
            || \str_starts_with($requestTarget, $this->loginUri)
            || \str_starts_with($requestTarget, $this->apiUri)
        ) {
            return;
        }

        WebController::redirect($this->loginUri);
    }

    public function run(): void
    {
        if (! $this->canDispatch()) {
            throw new \Error(
                \sprintf(
                    'Unfortunately requested resource "%s" does not exist!',
                    $this->request->getRequestTarget(),
                ),
                404,
            );
        }

        $this->app->run($this->request);
    }

    private function canDispatch(): bool
    {
        $requestTarget = $this->request->getRequestTarget();
        if (\str_starts_with($requestTarget, '/rh~')) {
            $position = \strpos($requestTarget, '?');
            if ($position !== false) {
                $requestTarget = \substr($requestTarget, 0, $position);
            }

            $hashPart = \substr($requestTarget, 0, 13);
            $uriPart  = \substr($requestTarget, 12);
            $hash     = \substr(\trim($hashPart, '/'), 3);

            return \hash(Url::HASH_ALGO, $uriPart) === $hash;
        }

        if (\str_starts_with($requestTarget, '/h~')) {
            $hashPart = \substr($requestTarget, 0, 12);
            $uriPart  = \substr($requestTarget, 11);
            $hash     = \substr(\trim($hashPart, '/'), 2);

            return \hash(Url::HASH_ALGO, $uriPart) === $hash;
        }

        return $requestTarget === '/'
            || \str_starts_with($requestTarget, $this->loginUri)
            || \str_starts_with($requestTarget, $this->apiUri);
    }
}
