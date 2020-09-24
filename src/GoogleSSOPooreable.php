<?php

declare(strict_types=1);

namespace GoogleSSO;

interface GoogleSSOPooreable
{
    public function createAuthUrl(): string;

    /** @return array<mixed> */
    public function fetchAccountDataUsingAuthorizationCode(string $code): array;
}
