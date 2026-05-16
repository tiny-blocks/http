<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use TinyBlocks\Http\Internal\Server\Cookies\CookieName;
use TinyBlocks\Http\Internal\Server\Cookies\CookieValue;
use TinyBlocks\Http\Internal\Server\Exceptions\ConflictingLifetimeAttributes;
use TinyBlocks\Http\Internal\Server\Exceptions\CookieNameIsInvalid;
use TinyBlocks\Http\Internal\Server\Exceptions\CookieValueIsInvalid;
use TinyBlocks\Http\Internal\Server\Exceptions\SameSiteNoneRequiresSecure;

final readonly class Cookie implements Headerable
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
        private bool $httpOnly,
        private ?SameSite $sameSite,
        private bool $partitioned
    ) {
    }

    /**
     * Creates a Cookie from a name and a value, with no attributes set.
     *
     * @param string $name The cookie name.
     * @param string $value The cookie value.
     * @return Cookie A Cookie carrying the given name and value and no other attributes.
     * @throws CookieNameIsInvalid If the name is empty or contains forbidden characters.
     * @throws CookieValueIsInvalid If the value contains forbidden characters.
     */
    public static function create(string $name, string $value): Cookie
    {
        return new Cookie(
            name: CookieName::from(value: $name),
            path: null,
            value: CookieValue::from(value: $value),
            domain: null,
            maxAge: null,
            secure: false,
            expires: null,
            httpOnly: false,
            sameSite: null,
            partitioned: false
        );
    }

    /**
     * Creates a Cookie that instructs the browser to discard an existing cookie with the given name.
     *
     * @param string $name The cookie name being expired.
     * @return Cookie A Cookie with an empty value and <code>Max-Age=0</code> set.
     * @throws CookieNameIsInvalid If the name is empty or contains forbidden characters.
     */
    public static function expire(string $name): Cookie
    {
        return new Cookie(
            name: CookieName::from(value: $name),
            path: null,
            value: CookieValue::from(value: ''),
            domain: null,
            maxAge: 0,
            secure: false,
            expires: null,
            httpOnly: false,
            sameSite: null,
            partitioned: false
        );
    }

    /**
     * Returns a copy of the Cookie with the <code>Secure</code> attribute enabled.
     *
     * @return Cookie A new instance carrying the <code>Secure</code> attribute.
     */
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
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned
        );
    }

    /**
     * Returns a copy of the Cookie with the <code>HttpOnly</code> attribute enabled.
     *
     * @return Cookie A new instance carrying the <code>HttpOnly</code> attribute.
     */
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
            httpOnly: true,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned
        );
    }

    /**
     * Returns a copy of the Cookie with the <code>Partitioned</code> attribute enabled.
     *
     * @return Cookie A new instance carrying the <code>Partitioned</code> attribute.
     */
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
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: true
        );
    }

    /**
     * Returns a copy of the Cookie with the path replaced.
     *
     * @param string $path The replacement path.
     * @return Cookie A new instance carrying the replaced path.
     */
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
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned
        );
    }

    /**
     * Returns a copy of the Cookie with the value replaced.
     *
     * @param string $value The replacement value.
     * @return Cookie A new instance carrying the replaced value.
     * @throws CookieValueIsInvalid If the value contains forbidden characters.
     */
    public function withValue(string $value): Cookie
    {
        return new Cookie(
            name: $this->name,
            path: $this->path,
            value: CookieValue::from(value: $value),
            domain: $this->domain,
            maxAge: $this->maxAge,
            secure: $this->secure,
            expires: $this->expires,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned
        );
    }

    /**
     * Returns a copy of the Cookie with the domain replaced.
     *
     * @param string $domain The replacement domain.
     * @return Cookie A new instance carrying the replaced domain.
     */
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
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned
        );
    }

    /**
     * Returns a copy of the Cookie with the <code>Max-Age</code> replaced.
     *
     * @param int $seconds The replacement lifetime in seconds.
     * @return Cookie A new instance carrying the replaced <code>Max-Age</code>.
     */
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
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned
        );
    }

    /**
     * Returns a copy of the Cookie with the <code>Expires</code> replaced and normalized to UTC.
     *
     * @param DateTimeInterface $expires The replacement expiration timestamp; normalized to UTC.
     * @return Cookie A new instance carrying the replaced <code>Expires</code>.
     */
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
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite,
            partitioned: $this->partitioned
        );
    }

    /**
     * Returns a copy of the Cookie with the <code>SameSite</code> attribute replaced.
     *
     * @param SameSite $sameSite The replacement <code>SameSite</code> attribute.
     * @return Cookie A new instance carrying the replaced <code>SameSite</code> attribute.
     */
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
            httpOnly: $this->httpOnly,
            sameSite: $sameSite,
            partitioned: $this->partitioned
        );
    }

    public function toArray(): array
    {
        $invariantViolation = match (true) {
            $this->sameSite === SameSite::NONE && !$this->secure => new SameSiteNoneRequiresSecure(),
            !is_null($this->maxAge) && !is_null($this->expires)  => new ConflictingLifetimeAttributes(),
            default                                              => null
        };

        if (!is_null($invariantViolation)) {
            throw $invariantViolation;
        }

        $nameValueTemplate = '%s=%s';
        $parts = [sprintf($nameValueTemplate, $this->name->toString(), $this->value->toString())];

        if (!is_null($this->maxAge)) {
            $maxAgeTemplate = 'Max-Age=%d';
            $parts[] = sprintf($maxAgeTemplate, $this->maxAge);
        }

        if (!is_null($this->expires)) {
            $expiresTemplate = 'Expires=%s';
            $parts[] = sprintf($expiresTemplate, $this->expires->format(Cookie::EXPIRES_FORMAT));
        }

        if (!is_null($this->path)) {
            $pathTemplate = 'Path=%s';
            $parts[] = sprintf($pathTemplate, $this->path);
        }

        if (!is_null($this->domain)) {
            $domainTemplate = 'Domain=%s';
            $parts[] = sprintf($domainTemplate, $this->domain);
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if (!is_null($this->sameSite)) {
            $sameSiteTemplate = 'SameSite=%s';
            $parts[] = sprintf($sameSiteTemplate, $this->sameSite->value);
        }

        if ($this->partitioned) {
            $parts[] = 'Partitioned';
        }

        return ['Set-Cookie' => [implode('; ', $parts)]];
    }
}
