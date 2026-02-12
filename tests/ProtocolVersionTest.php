<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http;

use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Response;

final class ProtocolVersionTest extends TestCase
{
    public function testProtocolVersion(): void
    {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @And by default, the response uses HTTP protocol version 1.1 */
        self::assertSame('1.1', $response->getProtocolVersion());

        /** @When the protocol version is updated to HTTP/3 */
        $actual = $response->withProtocolVersion(version: '3');

        /** @Then the response should use the updated protocol version 3 */
        self::assertSame('3', $actual->getProtocolVersion());
    }
}