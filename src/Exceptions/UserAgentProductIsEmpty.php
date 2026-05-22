<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when <code>UserAgent::from()</code> receives an empty product token.
 */
final class UserAgentProductIsEmpty extends InvalidArgumentException implements HttpException
{
    private const string REASON = 'User-Agent product must not be empty.';

    private function __construct()
    {
        parent::__construct(message: UserAgentProductIsEmpty::REASON);
    }

    /**
     * Creates a UserAgentProductIsEmpty signaling that the product token is empty.
     *
     * @return UserAgentProductIsEmpty The composed exception describing the empty-product-token state.
     */
    public static function create(): UserAgentProductIsEmpty
    {
        return new UserAgentProductIsEmpty();
    }
}
