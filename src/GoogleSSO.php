<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO;

use Google_Client;

final class GoogleSSO implements GoogleSSOPooreable
{
    private bool $initiated;

    /** @param array<string,mixed> $config */
    public function __construct(private readonly array $config, private Google_Client $client)
    {
        $this->initiated = false;
    }

    private function createClient(): Google_Client
    {
        if ($this->initiated === true) {
            return $this->client;
        }

        $this->configure();

        return $this->client;
    }

    private function configure(): void
    {
        foreach ($this->config as $name => $value) {
            $methodName = 'set' . \ucfirst($name);
            $cb         = [$this->client, $methodName];
            if (! \is_callable($cb)) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Google oauth client can not configured properly, method %s not exist.',
                        $methodName,
                    ),
                    500,
                );
            }

            \call_user_func_array($cb, [$value]);
        }

        $this->initiated = true;
    }

    public function createAuthUrl(): string
    {
        $googleClient = $this->createClient();

        return $googleClient->createAuthUrl();
    }

    /**
     * @throws \JsonException
     *
     * @inheritDoc
     */
    public function fetchAccountDataUsingAuthorizationCode(string $code): array
    {
        $googleClient = $this->createClient();
        $accessToken  = $googleClient->fetchAccessTokenWithAuthCode($code);
        if (! isset($accessToken['id_token'])) {
            throw new \UnexpectedValueException('Unexpected result from Google.', 500);
        }

        $tokenData = \explode('.', $accessToken['id_token']);
        if (! isset($tokenData[1])) {
            throw new \UnexpectedValueException('Unexpected Google Token result.', 500);
        }

        $valideBase64String = $this->prepareBase64($tokenData[1]);
        $data               = \base64_decode($valideBase64String, true);
        $googleProfileData  = [];

        if ($data !== false) {
            $googleProfileData = \json_decode(
                $data,
                true,
                512,
                \JSON_THROW_ON_ERROR,
            );
        }

        \assert(\is_array($googleProfileData));
        $googleProfileData['id_token'] = $accessToken['id_token'];

        return $googleProfileData;
    }

    /** @inheritDoc */
    public function fetchAccessTokenWithAuthCode(string $code): array
    {
        return $this->createClient()->fetchAccessTokenWithAuthCode($code);
    }

    private function prepareBase64(string $base64encodedString): string
    {
        $remainder = \strlen($base64encodedString) % 4;
        if ($remainder !== 0) {
            $padLength            = 4 - $remainder;
            $base64encodedString .= \str_repeat('=', $padLength);
        }

        return \strtr($base64encodedString, '-_', '+/');
    }

    public function getAccessToken(): string|null
    {
        $token = $this->client->getAccessToken();

        return $token['access_token'] ?? null;
    }
}
