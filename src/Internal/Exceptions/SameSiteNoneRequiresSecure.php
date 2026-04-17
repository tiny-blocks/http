<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Exceptions;

use DomainException;

final class SameSiteNoneRequiresSecure extends DomainException
{
    public function __construct()
    {
        $message = sprintf(
            '%s%s',
            'Cookies with SameSite=None require the Secure flag to be set; modern browsers reject ',
            'such cookies otherwise. Call secure() on the Cookie instance.'
        );

        parent::__construct($message);
    }
}
