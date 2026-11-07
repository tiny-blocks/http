<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Server;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Exceptions\ProtocolVersionIsUnsupported;
use TinyBlocks\Http\Server\Response;

final class ProtocolVersionTest extends TestCase
{
    public function testWithProtocolVersionWhenInvokedThenReturnsResponseWithUpdatedProtocol(): void
    {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @And by default, the response uses HTTP protocol version 1.1 */
        self::assertSame('1.1', $response->getProtocolVersion());

        /** @When the protocol version is updated to HTTP/3 */
        $actual = $response->withProtocolVersion('3');

        /** @Then the response uses the updated protocol version 3 */
        self::assertSame('3', $actual->getProtocolVersion());
    }

    #[DataProvider('supportedVersionProvider')]
    public function testWithProtocolVersionWhenVersionIsSupportedThenCarriesItsCanonicalForm(
        string $version,
        string $expected
    ): void {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @When the protocol version is set to a version the response supports */
        $actual = $response->withProtocolVersion($version);

        /** @Then the response carries the canonical form of that version */
        self::assertSame($expected, $actual->getProtocolVersion());
    }

    #[DataProvider('unsupportedVersionProvider')]
    public function testWithProtocolVersionWhenVersionIsUnsupportedThenProtocolVersionIsUnsupported(
        string $version
    ): void {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @Then the version is rejected, naming the versions that are supported */
        $this->expectException(ProtocolVersionIsUnsupported::class);
        $this->expectExceptionMessage('is not supported. Supported versions are 1.0, 1.1, 2 and 3.');

        /** @When the protocol version is set to a version outside 1.0, 1.1, 2 and 3 */
        $response->withProtocolVersion($version);
    }

    public static function supportedVersionProvider(): array
    {
        return [
            'HTTP/1.0'      => ['version' => '1.0', 'expected' => '1.0'],
            'HTTP/1.1'      => ['version' => '1.1', 'expected' => '1.1'],
            'HTTP/2'        => ['version' => '2', 'expected' => '2'],
            'HTTP/2 as 2.0' => ['version' => '2.0', 'expected' => '2'],
            'HTTP/3'        => ['version' => '3', 'expected' => '3'],
            'HTTP/3 as 3.0' => ['version' => '3.0', 'expected' => '3']
        ];
    }

    public static function unsupportedVersionProvider(): array
    {
        return [
            'Empty version'        => ['version' => ''],
            'HTTP/0.9'             => ['version' => '0.9'],
            'Unknown minor'        => ['version' => '1.2'],
            'Unknown HTTP/2 minor' => ['version' => '2.1'],
            'Unknown major'        => ['version' => '4'],
            'With the prefix'      => ['version' => 'HTTP/1.1'],
            'With trailing space'  => ['version' => '1.1 '],
            'Ending in LF'         => ['version' => "1.1\n"]
        ];
    }
}
