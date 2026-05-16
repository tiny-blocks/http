<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

final class UserAgentProductIsEmpty extends InvalidArgumentException implements HttpException
{
    private const string REASON = 'User-Agent product must not be empty.';

    private function __construct()
    {
        parent::__construct(message: UserAgentProductIsEmpty::REASON);
    }

    public static function create(): UserAgentProductIsEmpty
    {
        return new UserAgentProductIsEmpty();
    }
}
