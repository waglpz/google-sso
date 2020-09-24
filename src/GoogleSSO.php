<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO;

use Google_Client;
use InvalidArgumentException;

final class GoogleSSO implements GoogleSSOPooreable
{
    /** @var  array<string,mixed> */
    private array $config;
    private Google_Client $client;
    private bool $initiated;

    /** @param array<string,mixed> $config */
    public function __construct(array $config, ?Google_Client $client = null)
    {
        $this->initiated = false;
        $this->config    = $config;
        if ($client === null) {
            return;
        }

        $this->client = $client;
    }

    private function createClient(): Google_Client
    {
        if (! isset($this->client)) {
            $this->client = new Google_Client();
        }

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
            if (! \method_exists($this->client, $methodName)) {
                throw new InvalidArgumentException(
                    \sprintf(
                        'Google oauth client can not configured properly, method %s not exist.',
                        $methodName
                    ),
                    500
                );
            }

            $this->client->$methodName($value);
        }

        $this->initiated = true;
    }

    public function createAuthUrl(): string
    {
        $googleClient = $this->createClient();

        return $googleClient->createAuthUrl();
    }

    /** @return array<mixed> */
    public function fetchAccountDataUsingAuthorizationCode(string $code): array
    {
        $googleClient = $this->createClient();
        $accessToken  = $googleClient->fetchAccessTokenWithAuthCode($code);
        if (! isset($accessToken['id_token'])) {
            throw new \Error('Unexpected result from Google.', 500);
        }

        $tokenData = \explode('.', $accessToken['id_token']);
        if (! isset($tokenData[1])) {
            throw new \Error('Unexpected Google Token result.', 500);
        }

        $data              = \base64_decode($tokenData[1], true);
        $googleProfileData = [];

        if ($data !== false) {
            $googleProfileData = \json_decode(
                $data,
                true,
                512,
                \JSON_THROW_ON_ERROR
            );
        }

        return $googleProfileData;
    }
}
