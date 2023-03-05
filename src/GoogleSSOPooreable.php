<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO;

interface GoogleSSOPooreable
{
    public function createAuthUrl(): string;

    public function getAccessToken(): string|null;

    /** @return array<mixed> */
    public function fetchAccountDataUsingAuthorizationCode(string $code): array;

    /**
     * @return array<string,string|null>
     *
     * @throws \InvalidArgumentException
     */
    public function fetchAccessTokenWithAuthCode(string $code): array;
}
