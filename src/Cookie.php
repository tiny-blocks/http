<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use TinyBlocks\Http\Internal\Cookies\CookieName;
use TinyBlocks\Http\Internal\Cookies\CookieValue;
use TinyBlocks\Http\Internal\Exceptions\ConflictingLifetimeAttributes;
use TinyBlocks\Http\Internal\Exceptions\SameSiteNoneRequiresSecure;

final readonly class Cookie implements Headers
{
    private const string EXPIRES_FORMAT = 'D, d M Y H:i:s \G\M\T';

    private function __construct(
        private CookieName $name,
        private ?string $path,
        private CookieValue $value,
        private ?string $domain,
        private ?int $maxAge,
        private bool $secure,
        private ?DateTimeImmutable $expires,
        private ?SameSite $sameSite,
        private bool $httpOnly,
        private bool $partitioned
    ) {
    }

    public static function create(string $name, string $value): Cookie
    {
        return new Cookie(
            name: CookieName::from($name),
            path: null,
            value: CookieValue::from($value),
            domain: null,
            maxAge: null,
            secure: false,
            expires: null,
            sameSite: null,
            httpOnly: false,
            partitioned: false
        );
    }

    public static function expire(string $name): Cookie
    {
        return new Cookie(
            name: CookieName::from($name),
            path: null,
            value: CookieValue::from(''),
            domain: null,
            maxAge: 0,
            secure: false,
            expires: null,
            sameSite: null,
            httpOnly: false,
            partitioned: false
        );
    }

    public function secure(): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: $this->value,
            domain: $this->domain,
            maxAge: $this->maxAge,
            secure: true,
            expires: $this->expires,
            sameSite: $this->sameSite,
            httpOnly: $this->httpOnly,
            partitioned: $this->partitioned
        );
    }

    public function httpOnly(): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: $this->value,
            domain: $this->domain,
            maxAge: $this->maxAge,
            secure: $this->secure,
            expires: $this->expires,
            sameSite: $this->sameSite,
            httpOnly: true,
            partitioned: $this->partitioned
        );
    }

    public function partitioned(): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: $this->value,
            domain: $this->domain,
            maxAge: $this->maxAge,
            secure: $this->secure,
            expires: $this->expires,
            sameSite: $this->sameSite,
            httpOnly: $this->httpOnly,
            partitioned: true
        );
    }

    public function withPath(string $path): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $path,
            value: $this->value,
            domain: $this->domain,
            maxAge: $this->maxAge,
            secure: $this->secure,
            expires: $this->expires,
            sameSite: $this->sameSite,
            httpOnly: $this->httpOnly,
            partitioned: $this->partitioned
        );
    }

    public function withValue(string $value): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: CookieValue::from($value),
            domain: $this->domain,
            maxAge: $this->maxAge,
            secure: $this->secure,
            expires: $this->expires,
            sameSite: $this->sameSite,
            httpOnly: $this->httpOnly,
            partitioned: $this->partitioned
        );
    }

    public function withDomain(string $domain): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: $this->value,
            domain: $domain,
            maxAge: $this->maxAge,
            secure: $this->secure,
            expires: $this->expires,
            sameSite: $this->sameSite,
            httpOnly: $this->httpOnly,
            partitioned: $this->partitioned
        );
    }

    public function withMaxAge(int $seconds): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: $this->value,
            domain: $this->domain,
            maxAge: $seconds,
            secure: $this->secure,
            expires: $this->expires,
            sameSite: $this->sameSite,
            httpOnly: $this->httpOnly,
            partitioned: $this->partitioned
        );
    }

    public function withExpires(DateTimeInterface $expires): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: $this->value,
            domain: $this->domain,
            maxAge: $this->maxAge,
            secure: $this->secure,
            expires: DateTimeImmutable::createFromInterface($expires)->setTimezone(new DateTimeZone('UTC')),
            sameSite: $this->sameSite,
            httpOnly: $this->httpOnly,
            partitioned: $this->partitioned
        );
    }

    public function withSameSite(SameSite $sameSite): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: $this->value,
            domain: $this->domain,
            maxAge: $this->maxAge,
            secure: $this->secure,
            expires: $this->expires,
            sameSite: $sameSite,
            httpOnly: $this->httpOnly,
            partitioned: $this->partitioned
        );
    }

    public function toArray(): array
    {
        $invariantViolation = match (true) {
            $this->sameSite === SameSite::NONE && !$this->secure  => new SameSiteNoneRequiresSecure(),
            !is_null($this->maxAge) && !is_null($this->expires)   => new ConflictingLifetimeAttributes(),
            default                                               => null,
        };

        if (!is_null($invariantViolation)) {
            throw $invariantViolation;
        }

        $parts = [sprintf('%s=%s', $this->name->toString(), $this->value->toString())];

        if (!is_null($this->maxAge)) {
            $parts[] = sprintf('Max-Age=%d', $this->maxAge);
        }

        if (!is_null($this->expires)) {
            $parts[] = sprintf('Expires=%s', $this->expires->format(self::EXPIRES_FORMAT));
        }

        if (!is_null($this->path)) {
            $parts[] = sprintf('Path=%s', $this->path);
        }

        if (!is_null($this->domain)) {
            $parts[] = sprintf('Domain=%s', $this->domain);
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if (!is_null($this->sameSite)) {
            $parts[] = sprintf('SameSite=%s', $this->sameSite->value);
        }

        if ($this->partitioned) {
            $parts[] = 'Partitioned';
        }

        return ['Set-Cookie' => [implode('; ', $parts)]];
    }
}
