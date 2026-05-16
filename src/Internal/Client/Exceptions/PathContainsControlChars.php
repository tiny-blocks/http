<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client\Exceptions;

use InvalidArgumentException;

final class PathContainsControlChars extends InvalidArgumentException
{
    private const string REASON_TEMPLATE = 'Path "%s" must not contain control characters.';

    private function __construct(string $path)
    {
        $template = PathContainsControlChars::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $path));
    }

    public static function create(string $path): PathContainsControlChars
    {
        return new PathContainsControlChars(path: $path);
    }
}
