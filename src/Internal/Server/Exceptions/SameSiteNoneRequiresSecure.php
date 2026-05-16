<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Server\Exceptions;

use DomainException;

final class SameSiteNoneRequiresSecure extends DomainException
{
    private const string REASON = 'Cookies with SameSite=None require the Secure flag to be set; modern browsers '
        . 'reject such cookies otherwise. Call secure() on the Cookie instance.';

    public function __construct()
    {
        parent::__construct(message: SameSiteNoneRequiresSecure::REASON);
    }
}
