<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Exceptions;

use DomainException;

final class ConflictingLifetimeAttributes extends DomainException
{
    public function __construct()
    {
        $message = sprintf(
            '%s%s',
            'Cookie lifetime attributes are conflicting. A cookie must declare its lifetime via either ',
            'Max-Age or Expires, not both. Choose one and reset the other with a new Cookie instance.'
        );

        parent::__construct($message);
    }
}
