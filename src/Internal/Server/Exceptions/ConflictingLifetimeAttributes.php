<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use DomainException;

final class ConflictingLifetimeAttributes extends DomainException
{
    private const string REASON = 'Cookie lifetime attributes are conflicting. A cookie must declare its lifetime via either Max-Age or Expires, not both. Choose one and reset the other with a new Cookie instance.';

    public function __construct()
    {
        parent::__construct(self::REASON);
    }
}
