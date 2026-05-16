<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client\Exceptions;

use InvalidArgumentException;

final class PathContainsScheme extends InvalidArgumentException
{
    private const string REASON_TEMPLATE = 'Path "%s" must not contain a scheme or be protocol-relative.';

    private function __construct(string $path)
    {
        $template = PathContainsScheme::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $path));
    }

    public static function create(string $path): PathContainsScheme
    {
        return new PathContainsScheme(path: $path);
    }
}
