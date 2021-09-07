<?php

declare(strict_types=1);

namespace Waglpz\GoogleSSO;

interface GoogleSSOPooreable
{
    public function createAuthUrl(): string;

    public function getAccessToken(): ?string;

    /** @return array<mixed> */
    public function fetchAccountDataUsingAuthorizationCode(string $code): array;
}
