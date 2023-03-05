<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\WebController;

class Login extends WebController
{
    /** @var callable|null */
    private $onSuccess;

    /** @var callable|null */
    private $onFail;

    public function __construct(
        PhpRenderer $view,
        readonly LoginCallBack $loginCallBack,
        callable|null $onSuccess = null,
        callable|null $onFail = null,
        readonly string $redirectToUrlOnSuccess = '/',
    ) {
        parent::__construct($view);

        $this->onSuccess = $onSuccess;
        $this->onFail    = $onFail;
    }

    /**
     * @throws \JsonException
     * @throws \Throwable
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->disableLayout();
        $resultOrUrl = ($this->loginCallBack)($request, $this->onSuccess, $this->onFail);

        if ($resultOrUrl === false) {
            // on login request after google opt in
            $resultOrUrl = ($this->loginCallBack)($request, null, null);
            $model       = [
                'singleSignInUrl' => $resultOrUrl,
                'message'         => 'Not Authorized',
                'seitenTitle'     => 'Login SSO',
            ];

            return $this->render($model);
        }

        if ($resultOrUrl !== true) {
            // initial on first
            $model = [
                'singleSignInUrl' => $resultOrUrl,
                'message'         => 'Please log in',
                'seitenTitle'     => 'Login SSO',
            ];

            return $this->render($model);
        }

        // login successful we redirect user ...
        self::redirect($this->redirectToUrlOnSuccess);

        throw new \Error('never return ;)');
    }
}
