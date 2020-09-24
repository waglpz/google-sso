<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO\Tests;

use PHPUnit\Framework\TestCase;
use Waglpz\GoogleSSO\GoogleSSO;

final class GoogleSSOTest extends TestCase
{
    /** @var array<string> */
    private array $validToken;
    /** @var array<mixed> */
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = [
            'redirectUri' => 'http://test-server',
            'accessType'  => 'online',
            'scopes'      => ['email', 'profile'],
            'state'       => 'some.string.to.check.the.response.origin',
            'prompt'      => 'select_account',
            'authConfig'  => '/some/path/some.config.json',
        ];

        // eyJlbWFpbCI6ICJmcmVkeUBhY21lLmNvbSJ9 base64_encode('{"email": "fredy@acme.com"}')
        $this->validToken = ['id_token' => 'abc.eyJlbWFpbCI6ICJmcmVkeUBhY21lLmNvbSJ9'];
    }

    /** @test */
    public function authorizationUrlCreated(): void
    {
        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())->method('setRedirectUri')->with('http://test-server');
        $googleClient->expects(self::once())->method('setAccessType')->with('online');
        $googleClient->expects(self::once())->method('setScopes')->with(['email', 'profile']);
        $googleClient->expects(self::once())->method('setState')->with('some.string.to.check.the.response.origin');
        $googleClient->expects(self::once())->method('setPrompt')->with('select_account');
        $googleClient->expects(self::once())->method('setAuthConfig')->with('/some/path/some.config.json');
        $googleClient->expects(self::once())->method('createAuthUrl')->willReturn('authorization-url');
        $sso = new GoogleSSO($this->config, $googleClient);
        $url = $sso->createAuthUrl();
        self::assertSame('authorization-url', $url);
    }

    /** @test */
    public function errorUnexpectedResult(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Unexpected result from Google.');

        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn([]);
        $sso = new GoogleSSO($this->config, $googleClient);
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
    }

    /** @test */
    public function errorConfigurationMethodNotExist(): void
    {
        $methodName = 'WRONG_METHOD_NAME';
        $config     = [$methodName => 'value'];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage(
            \sprintf(
                'Google oauth client can not configured properly, method %s not exist.',
                'set' . $methodName
            )
        );

        $googleClient = $this->createMock(\Google_Client::class);
        $sso          = new GoogleSSO($config, $googleClient);
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
    }

    /** @test */
    public function errorUnexpectedGoogleTokenResult(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Unexpected Google Token result.');

        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn(['id_token' => 'abc']);
        $sso = new GoogleSSO($this->config, $googleClient);
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
    }

    /** @test */
    public function errorJsonDecodeResult(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn(['id_token' => 'abc.']);
        $sso = new GoogleSSO($this->config, $googleClient);
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
    }

    /** @test */
    public function fetchAccountDataUsingAuthorizationCode(): void
    {
        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn($this->validToken);
        $sso         = new GoogleSSO($this->config, $googleClient);
        $accountData = $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
        self::assertSame(['email' => 'fredy@acme.com'], $accountData);
    }

    /** @test */
    public function doesNotRedundantCreateGoogleClient(): void
    {
        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())->method('setRedirectUri')->with('http://test-server');
        $googleClient->expects(self::once())->method('setAccessType')->with('online');
        $googleClient->expects(self::once())->method('setScopes')->with(['email', 'profile']);
        $googleClient->expects(self::once())->method('setState')->with('some.string.to.check.the.response.origin');
        $googleClient->expects(self::once())->method('setPrompt')->with('select_account');
        $googleClient->expects(self::once())->method('setAuthConfig')->with('/some/path/some.config.json');
        $googleClient->expects(self::exactly(2))
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn($this->validToken);
        $googleClient->expects(self::exactly(2))->method('createAuthUrl')->willReturn('authorization-url');

        $sso = new GoogleSSO($this->config, $googleClient);

        $sso->createAuthUrl();
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
        $sso->createAuthUrl();
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
    }
}
