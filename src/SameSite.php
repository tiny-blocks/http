<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

enum SameSite: string
{
    case LAX = 'Lax';
    case NONE = 'None';
    case STRICT = 'Strict';
}
