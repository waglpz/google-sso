<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO\Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Waglpz\GoogleSSO\GoogleSSO;

final class GoogleSSOTest extends TestCase
{
    /** @var array<string> */
    private array $validToken;
    /** @var array<mixed> */
    private array $config;
    /** @var array<string> */
    private array $invalidToken;

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

        // eyJlbWFpbCI6ICJncm9zc2VAYWNtZS5jb20iLCAibmFtZSI6ICJHcm/Dn2UifQ==
        // base64_encode('{"email": "grosse@acme.com", "name": "Große"}')
        // we cut last "=" and mark invalid
        $this->invalidToken = ['id_token' => 'abc.eyJlbWFpbCI6ICJncm9zc2VAYWNtZS5jb20iLCAibmFtZSI6ICJHcm/Dn2UifQ='];
    }

    /**
     * @throws Exception
     *
     * @test
     */
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

    /**
     * @throws Exception|\JsonException
     *
     * @test
     */
    public function errorUnexpectedResult(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Unexpected result from Google.');

        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn([]);
        $sso = new GoogleSSO($this->config, $googleClient);
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
    }

    /**
     * @throws Exception|\JsonException
     *
     * @test
     */
    public function errorConfigurationMethodNotExist(): void
    {
        $methodName = 'WRONG_METHOD_NAME';
        $config     = [$methodName => 'value'];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage(
            \sprintf(
                'Google oauth client can not configured properly, method %s not exist.',
                'set' . $methodName,
            ),
        );

        $googleClient = $this->createMock(\Google_Client::class);
        $sso          = new GoogleSSO($config, $googleClient);
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
    }

    /**
     * @throws Exception|\JsonException
     *
     * @test
     */
    public function errorUnexpectedGoogleTokenResult(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Unexpected Google Token result.');

        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn(['id_token' => 'abc']);
        $sso = new GoogleSSO($this->config, $googleClient);
        $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
    }

    /**
     * @throws Exception
     *
     * @test
     */
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

    /**
     * @throws Exception
     * @throws \JsonException
     *
     * @test
     */
    public function fetchAccountDataUsingAuthorizationCode(): void
    {
        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn($this->validToken);
        $sso         = new GoogleSSO($this->config, $googleClient);
        $accountData = $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');

        $expectation = [
            'email' => 'fredy@acme.com',
            'id_token' => 'abc.eyJlbWFpbCI6ICJmcmVkeUBhY21lLmNvbSJ9',
        ];
        self::assertSame($expectation, $accountData);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     *
     * @test
     */
    public function fetchAccessTokenWithAuthCode(): void
    {
        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn($this->validToken);
        $sso         = new GoogleSSO($this->config, $googleClient);
        $accountData = $sso->fetchAccessTokenWithAuthCode('code-ABC');
        self::assertSame(['id_token' => 'abc.eyJlbWFpbCI6ICJmcmVkeUBhY21lLmNvbSJ9'], $accountData);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     *
     * @test
     */
    public function fetchAccountDataUsingAuthorizationCodeAlsoBase64IsInvalid(): void
    {
        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('fetchAccessTokenWithAuthCode')->with('code-ABC')
                     ->willReturn($this->invalidToken);
        $sso         = new GoogleSSO($this->config, $googleClient);
        $accountData = $sso->fetchAccountDataUsingAuthorizationCode('code-ABC');
        self::assertSame(
            [
                'email' => 'grosse@acme.com',
                'name'  => 'Große',
                'id_token' => 'abc.eyJlbWFpbCI6ICJncm9zc2VAYWNtZS5jb20iLCAibmFtZSI6ICJHcm/Dn2UifQ=',
            ],
            $accountData,
        );
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function getAccessTokenBeforeFetchAccountData(): void
    {
        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('getAccessToken')
                     ->willReturn(null);
        $sso         = new GoogleSSO($this->config, $googleClient);
        $accessToken = $sso->getAccessToken();
        self::assertNull($accessToken);
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function getAccessToken(): void
    {
        $googleAccessToken = ['access_token' => '123.access.abc'];

        $googleClient = $this->createMock(\Google_Client::class);
        $googleClient->expects(self::once())
                     ->method('getAccessToken')
                     ->willReturn($googleAccessToken);
        $sso         = new GoogleSSO($this->config, $googleClient);
        $accessToken = $sso->getAccessToken();
        self::assertSame('123.access.abc', $accessToken);
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
