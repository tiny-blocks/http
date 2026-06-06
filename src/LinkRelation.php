<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * IANA-registered link relation token carried in an RFC 8288 Link header.
 *
 * @see https://www.iana.org/assignments/link-relations/link-relations.xhtml
 */
enum LinkRelation: string
{
    case LAST = 'last';
    case NEXT = 'next';
    case SELF = 'self';
    case FIRST = 'first';
    case PREVIOUS = 'prev';
}
