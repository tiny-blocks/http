<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use RuntimeException;
use Throwable;
use TinyBlocks\Http\Client\Request;

final class MalformedPath extends RuntimeException implements HttpException
{
    private const string REASON_TEMPLATE = 'Path "%s" is malformed and cannot be composed safely against a base URL.';

    private function __construct(private readonly string $path, ?Throwable $previous)
    {
        $template = MalformedPath::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $path), previous: $previous);
    }

    /**
     * Creates a MalformedPath from the offending Request, optionally wrapping the original cause.
     *
     * @param Request $request The request whose path could not be composed safely against the base URL.
     * @param Throwable|null $previous The original cause preserved as the previous throwable.
     * @return MalformedPath The composed exception describing the malformed path.
     */
    public static function fromRequest(Request $request, ?Throwable $previous = null): MalformedPath
    {
        return new MalformedPath(path: $request->url(), previous: $previous);
    }

    /**
     * Returns the path.
     *
     * @return string The malformed path that triggered the exception.
     */
    public function path(): string
    {
        return $this->path;
    }
}
