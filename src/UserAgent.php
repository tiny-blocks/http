<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Exceptions\UserAgentProductIsEmpty;

final readonly class UserAgent implements Headerable
{
    private function __construct(private string $product, private ?string $version)
    {
    }

    /**
     * Builds a User-Agent header value from a product token and an optional version.
     *
     * An empty version is normalized to "no version" — the rendered header
     * carries only the product token in that case. The product token must not
     * be empty.
     *
     * @param string $product The mandatory product token (e.g., "MyApp").
     * @param string $version The optional version. Empty string means "absent".
     * @return UserAgent A new immutable value object.
     * @throws UserAgentProductIsEmpty When the product token is empty.
     */
    public static function from(string $product, string $version = ''): UserAgent
    {
        if ($product === '') {
            throw UserAgentProductIsEmpty::create();
        }

        return new UserAgent(
            product: $product,
            version: $version === '' ? null : $version
        );
    }

    public function toArray(): array
    {
        if (is_null($this->version)) {
            return ['User-Agent' => $this->product];
        }

        $template = '%s/%s';

        return ['User-Agent' => sprintf($template, $this->product, $this->version)];
    }
}
