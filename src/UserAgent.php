<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Exceptions\UserAgentProductIsEmpty;
use TinyBlocks\Http\Exceptions\UserAgentValueIsInvalid;
use TinyBlocks\Http\Internal\Client\UserAgentProduct;
use TinyBlocks\Http\Internal\Client\UserAgentVersion;

/**
 * HTTP User-Agent header value composed of a product token and an optional version.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
 */
final readonly class UserAgent implements Headerable
{
    private function __construct(private UserAgentProduct $product, private UserAgentVersion $version)
    {
    }

    /**
     * Builds a User-Agent header value from a product token and an optional version.
     *
     * An absent or empty version is normalized to no version (the rendered header carries only
     * the product token in that case). The product token must not be empty.
     *
     * @param string $product The mandatory product token (e.g., "MyApp").
     * @param string|null $version The optional version, or null when absent. Empty string is treated as absent.
     * @return UserAgent A new immutable value object.
     * @throws UserAgentProductIsEmpty When the product token is empty.
     * @throws UserAgentValueIsInvalid When the product or version contains control characters or a forward slash
     *                                 (product only).
     */
    public static function from(string $product, ?string $version = null): UserAgent
    {
        return new UserAgent(
            product: UserAgentProduct::from(value: $product),
            version: UserAgentVersion::from(value: $version)
        );
    }

    public function toArray(): array
    {
        $version = $this->version->toString();

        if (is_null($version)) {
            return ['User-Agent' => $this->product->toString()];
        }

        $template = '%s/%s';

        return ['User-Agent' => sprintf($template, $this->product->toString(), $version)];
    }
}
