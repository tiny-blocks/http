<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use LogicException;

/**
 * Raised when <code>Response::raw()</code> is called on a response synthesized via <code>Response::with()</code>.
 *
 * Synthesized responses exist only for in-process scenarios (tests, in-memory transports) and have
 * no backing PSR-7 message to expose.
 */
final class SynthesizedResponseHasNoRaw extends LogicException implements HttpException
{
    private const string REASON = 'Response was synthesized via Response::with(...) and has no underlying PSR-7 raw '
        . 'response.';

    private function __construct()
    {
        parent::__construct(message: SynthesizedResponseHasNoRaw::REASON);
    }

    /**
     * Creates a SynthesizedResponseHasNoRaw signaling that the response has no underlying PSR-7 raw message.
     *
     * @return SynthesizedResponseHasNoRaw The composed exception describing the synthesized-response state.
     */
    public static function create(): SynthesizedResponseHasNoRaw
    {
        return new SynthesizedResponseHasNoRaw();
    }
}
