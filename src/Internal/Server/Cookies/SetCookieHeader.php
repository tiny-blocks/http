<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Cookies;

use DateTimeImmutable;
use TinyBlocks\Http\SameSite;

final readonly class SetCookieHeader
{
    private const string EXPIRES_FORMAT = 'D, d M Y H:i:s \G\M\T';

    private function __construct(private array $parts)
    {
    }

    public static function from(
        CookieName $name,
        ?string $path,
        CookieValue $value,
        ?string $domain,
        ?int $maxAge,
        bool $secure,
        ?DateTimeImmutable $expires,
        bool $httpOnly,
        ?SameSite $sameSite,
        bool $partitioned
    ): SetCookieHeader {
        $nameValueTemplate = '%s=%s';
        $nameValue = sprintf($nameValueTemplate, $name->toString(), $value->toString());

        $attributes = SetCookieHeader::attributes(
            path: $path,
            domain: $domain,
            maxAge: $maxAge,
            expires: $expires
        );

        $flags = SetCookieHeader::flags(
            secure: $secure,
            httpOnly: $httpOnly,
            sameSite: $sameSite,
            partitioned: $partitioned
        );

        return new SetCookieHeader(parts: [$nameValue, ...$attributes, ...$flags]);
    }

    private static function flags(bool $secure, bool $httpOnly, ?SameSite $sameSite, bool $partitioned): array
    {
        $parts = [];

        if ($secure) {
            $parts[] = 'Secure';
        }

        if ($httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if (!is_null($sameSite)) {
            $sameSiteTemplate = 'SameSite=%s';
            $parts[] = sprintf($sameSiteTemplate, $sameSite->value);
        }

        if ($partitioned) {
            $parts[] = 'Partitioned';
        }

        return $parts;
    }

    private static function attributes(?string $path, ?string $domain, ?int $maxAge, ?DateTimeImmutable $expires): array
    {
        $parts = [];

        if (!is_null($maxAge)) {
            $maxAgeTemplate = 'Max-Age=%d';
            $parts[] = sprintf($maxAgeTemplate, $maxAge);
        }

        if (!is_null($expires)) {
            $expiresTemplate = 'Expires=%s';
            $parts[] = sprintf($expiresTemplate, $expires->format(SetCookieHeader::EXPIRES_FORMAT));
        }

        if (!is_null($path)) {
            $pathTemplate = 'Path=%s';
            $parts[] = sprintf($pathTemplate, $path);
        }

        if (!is_null($domain)) {
            $domainTemplate = 'Domain=%s';
            $parts[] = sprintf($domainTemplate, $domain);
        }

        return $parts;
    }

    public function toString(): string
    {
        return implode('; ', $this->parts);
    }
}
