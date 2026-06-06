<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Server;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Test\TinyBlocks\Http\Models\Amount;
use Test\TinyBlocks\Http\Models\Color;
use Test\TinyBlocks\Http\Models\Currency;
use Test\TinyBlocks\Http\Models\Dragon;
use Test\TinyBlocks\Http\Models\Order;
use Test\TinyBlocks\Http\Models\Product;
use Test\TinyBlocks\Http\Models\Products;
use Test\TinyBlocks\Http\Models\Status;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Exceptions\BodyTypeIsUnsupported;
use TinyBlocks\Http\Server\Response;

final class ResponseTest extends TestCase
{
    public function testGetBodyWhenDetachedThenSizeIsNull(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When detaching the underlying resource */
        $stream->detach();

        /** @Then the size collapses to null */
        self::assertNull($stream->getSize());
    }

    public function testGetBodyWhenClosedThenIsNotReadable(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When the stream is closed */
        $stream->close();

        /** @Then the stream is no longer readable */
        self::assertFalse($stream->isReadable());
    }

    public function testGetBodyWhenClosedThenIsNotSeekable(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When the stream is closed */
        $stream->close();

        /** @Then the stream is no longer seekable */
        self::assertFalse($stream->isSeekable());
    }

    public function testGetBodyWhenClosedThenIsNotWritable(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When the stream is closed */
        $stream->close();

        /** @Then the stream is no longer writable */
        self::assertFalse($stream->isWritable());
    }

    public function testGetBodyWhenClosedThenEofReturnsFalse(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When the stream is closed */
        $stream->close();

        /** @Then the detached stream reports it has not reached EOF */
        self::assertFalse($stream->eof());
    }

    public function testGetBodyWhenClosedThenReportsNullSize(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When the stream is closed */
        $stream->close();

        /** @Then the stream reports null size */
        self::assertNull($stream->getSize());
    }

    public function testGetBodyWhenInvokedThenStreamIsReadable(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When inspecting the stream */
        $isReadable = $stream->isReadable();

        /** @Then the stream is readable */
        self::assertTrue($isReadable);
    }

    public function testGetBodyWhenInvokedThenStreamIsSeekable(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When inspecting the stream */
        $isSeekable = $stream->isSeekable();

        /** @Then the stream is seekable */
        self::assertTrue($isSeekable);
    }

    public function testGetBodyWhenInvokedThenStreamIsWritable(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When inspecting the stream */
        $isWritable = $stream->isWritable();

        /** @Then the stream is writable */
        self::assertTrue($isWritable);
    }

    public function testGetBodyWhenDetachedThenIsNoLongerReadable(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When detaching the underlying resource */
        $stream->detach();

        /** @Then the stream is no longer readable */
        self::assertFalse($stream->isReadable());
    }

    public function testWithBodyWhenInvokedThenReplacesBodyContent(): void
    {
        /** @Given an HTTP response without body */
        $response = Response::ok(body: null);

        /** @And a fresh PSR-7 stream carrying the replacement bytes */
        $replacement = new Psr17Factory()->createStream('This is a new body');

        /** @When the body is replaced */
        $actual = $response->withBody($replacement);

        /** @Then the response body matches the new content */
        self::assertSame('This is a new body', $actual->getBody()->__toString());
    }

    public function testGetBodyWhenContentsReadThenStreamReachesEof(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When reading all contents to advance the cursor */
        $stream->getContents();

        /** @Then EOF is signaled */
        self::assertTrue($stream->eof());
    }

    public function testGetBodyWhenClosedTwiceThenSecondCloseIsANoOp(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @And the stream is already closed */
        $stream->close();

        /** @When closing the stream a second time */
        $stream->close();

        /** @Then the stream remains detached and reports null size */
        self::assertNull($stream->getSize());
    }

    public function testGetBodyWhenSeekedToOffsetThenTellMatchesOffset(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When seeking past the opening brace */
        $stream->seek(1);

        /** @Then the position reports the seeked offset */
        self::assertSame(1, $stream->tell());
    }

    public function testGetBodyWhenClosedThenReadRaisesNonReadableError(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @And the stream is closed */
        $stream->close();

        /** @Then reading raises a non-readable error */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not readable.');

        /** @When reading from the closed stream */
        $stream->read(1);
    }

    public function testGetBodyWhenClosedThenSeekRaisesNonSeekableError(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @And the stream is closed */
        $stream->close();

        /** @Then seeking raises a non-seekable error */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not seekable.');

        /** @When seeking on the closed stream */
        $stream->seek(0);
    }

    public function testOkWhenBodyGivenThenReturnsResponseWithStatus200(): void
    {
        /** @Given a body with data */
        $body = ['id' => PHP_INT_MAX, 'name' => 'Drakengard Firestorm', 'type' => 'Dragon', 'weight' => 6000.00];

        /** @When the response is created with the body */
        $actual = Response::ok(body: $body);

        /** @Then the response carries the body encoded as JSON and a 200 status */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(Code::OK->value, $actual->getStatusCode());
        self::assertTrue(Code::isSuccessCode(code: $actual->getStatusCode()));
        self::assertSame(Code::OK->message(), $actual->getReasonPhrase());
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testGetBodyWhenClosedThenWriteRaisesNonWritableError(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @And the stream is closed */
        $stream->close();

        /** @Then writing raises a non-writable error */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not writable.');

        /** @When writing to the closed stream */
        $stream->write('payload');
    }

    public function testGetBodyWhenDetachedThenReturnsUnderlyingResource(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When detaching the underlying resource */
        $resource = $stream->detach();

        /** @Then the returned value is a resource */
        self::assertIsResource($resource);
    }

    public function testGetBodyWhenInvokedThenStreamStartsAtPositionZero(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When inspecting position before reading */
        $tell = $stream->tell();

        /** @Then the position starts at zero */
        self::assertSame(0, $tell);
    }

    public function testGetBodyWhenSizeRequestedThenMatchesPayloadLength(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When asking the stream for its size */
        $size = $stream->getSize();

        /** @Then the size matches the encoded payload length */
        self::assertSame(strlen('{"name":"Hydra"}'), $size);
    }

    public function testGetBodyWhenReadInChunksThenReturnsContentSegments(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When reading a small chunk from the beginning */
        $chunk = $stream->read(4);

        /** @Then the chunk matches the leading bytes of the encoded payload */
        self::assertSame('{"na', $chunk);
    }

    public function testGetBodyWhenClosedThenTellRaisesMissingResourceError(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @And the stream is closed */
        $stream->close();

        /** @Then telling the position raises a missing-resource error */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No resource available.');

        /** @When asking for the position */
        $stream->tell();
    }

    public function testGetBodyWhenInvokedThenStreamIsNotAtEofBeforeReading(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When inspecting EOF before reading */
        $eof = $stream->eof();

        /** @Then EOF is not yet reached */
        self::assertFalse($eof);
    }

    public function testCreatedWhenBodyGivenThenReturnsResponseWithStatus201(): void
    {
        /** @Given a body with data */
        $body = ['id' => 1, 'name' => 'New Resource', 'type' => 'Item', 'weight' => 100.00];

        /** @When the response is created with the body */
        $actual = Response::created(body: $body);

        /** @Then the response carries the body and a 201 status */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(Code::CREATED->value, $actual->getStatusCode());
        self::assertTrue(Code::isSuccessCode(code: $actual->getStatusCode()));
        self::assertSame(Code::CREATED->message(), $actual->getReasonPhrase());
    }

    public function testAcceptedWhenBodyGivenThenReturnsResponseWithStatus202(): void
    {
        /** @Given a body with data */
        $body = ['id' => 1, 'status' => 'Processing'];

        /** @When the response is created with the body */
        $actual = Response::accepted(body: $body);

        /** @Then the response carries the body and a 202 status */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(Code::ACCEPTED->value, $actual->getStatusCode());
        self::assertTrue(Code::isSuccessCode(code: $actual->getStatusCode()));
        self::assertSame(Code::ACCEPTED->message(), $actual->getReasonPhrase());
    }

    public function testConflictWhenBodyGivenThenReturnsResponseWithStatus409(): void
    {
        /** @Given a body with conflict details */
        $body = ['error' => 'Conflict', 'message' => 'There is a conflict with the current state of the resource.'];

        /** @When the response is created with the body */
        $actual = Response::conflict(body: $body);

        /** @Then the status is 409 */
        self::assertSame(Code::CONFLICT->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    public function testGetBodyWhenReadLengthIsZeroThenRaisesNonReadableError(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @Then reading raises a non-readable error */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not readable.');

        /** @When reading with a non-positive length */
        $stream->read(0);
    }

    public function testNoContentWhenInvokedThenReturnsEmptyBodyWithStatus204(): void
    {
        /** @When the response is created without body */
        $actual = Response::noContent();

        /** @Then the body is empty and the status is 204 */
        self::assertEmpty($actual->getBody()->__toString());
        self::assertSame(Code::NO_CONTENT->value, $actual->getStatusCode());
        self::assertTrue(Code::isSuccessCode(code: $actual->getStatusCode()));
        self::assertSame(Code::NO_CONTENT->message(), $actual->getReasonPhrase());
    }

    public function testNotFoundWhenBodyGivenThenReturnsResponseWithStatus404(): void
    {
        /** @Given a body with error details */
        $body = ['error' => 'Not found', 'message' => 'The requested resource could not be found.'];

        /** @When the response is created with the body */
        $actual = Response::notFound(body: $body);

        /** @Then the status is 404 */
        self::assertSame(Code::NOT_FOUND->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    #[DataProvider('bodyProviderData')]
    public function testOkWhenAnyBodyShapeGivenThenSerializesToExpectedString(mixed $body, string $expected): void
    {
        /** @Given the body contains the provided data */
        /** @When we create an HTTP response with the given body */
        $actual = Response::ok(body: $body);

        /** @Then the body matches the expected output */
        self::assertSame($expected, $actual->getBody()->__toString());
    }

    public function testForbiddenWhenBodyGivenThenReturnsResponseWithStatus403(): void
    {
        /** @Given a body with error details */
        $body = ['error' => 'Forbidden', 'message' => 'You do not have permission to access this resource.'];

        /** @When the response is created with the body */
        $actual = Response::forbidden(body: $body);

        /** @Then the status is 403 */
        self::assertSame(Code::FORBIDDEN->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    public function testGetBodyWhenClosedThenGetContentsRaisesNonReadableError(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @And the stream is closed */
        $stream->close();

        /** @Then reading the contents raises a non-readable error */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not readable.');

        /** @When asking for the full contents */
        $stream->getContents();
    }

    public function testGetBodyWhenMetadataRequestedWithoutKeyThenReturnsArray(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When asking for the full metadata map */
        $metadata = $stream->getMetadata();

        /** @Then the metadata is exposed as an array */
        self::assertIsArray($metadata);
    }

    public function testBadGatewayWhenBodyGivenThenReturnsResponseWithStatus502(): void
    {
        /** @Given a body with upstream failure details */
        $body = ['error' => 'Bad Gateway', 'message' => 'The upstream server returned an invalid response.'];

        /** @When the response is created with the body */
        $actual = Response::badGateway(body: $body);

        /** @Then the status is 502 */
        self::assertSame(Code::BAD_GATEWAY->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    public function testBadRequestWhenBodyGivenThenReturnsResponseWithStatus400(): void
    {
        /** @Given a body with error details */
        $body = ['error' => 'Invalid request', 'message' => 'The request body is malformed.'];

        /** @When the response is created with the body */
        $actual = Response::badRequest(body: $body);

        /** @Then the status is 400 */
        self::assertSame(Code::BAD_REQUEST->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    public function testWithStatusWhenInvokedThenReturnsResponseWithUpdatedCode(): void
    {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @When calling withStatus with a new code */
        $updated = $response->withStatus(Code::OK->value);

        /** @Then the returned response reflects the new status code */
        self::assertSame(Code::OK->value, $updated->getStatusCode());
    }

    public function testGetBodyWhenMetadataKeyRequestedAfterCloseThenReturnsNull(): void
    {
        /** @Given a closed response stream */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();
        $stream->close();

        /** @When asking for a specific metadata key */
        $value = $stream->getMetadata('mode');

        /** @Then null is returned */
        self::assertNull($value);
    }

    #[DataProvider('responseFromProvider')]
    public function testFromWhenCodeAndBodyGivenThenRendersBodyWithMatchingStatus(
        Code $code,
        mixed $body,
        string $expectedBody
    ): void {
        /** @Given a specific status code and body */
        /** @When creating the HTTP response via the generic from method */
        $actual = Response::from(body: $body, code: $code);

        /** @Then the protocol version is "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response matches the expected output */
        self::assertSame($expectedBody, $actual->getBody()->__toString());

        /** @And the status code matches the provided code */
        self::assertSame($code->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));

        /** @And the reason phrase matches the code message */
        self::assertSame($code->message(), $actual->getReasonPhrase());

        /** @And the default Content-Type is application/json; charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testOkWhenArbitraryObjectGivenThenThrowsBodyTypeIsUnsupported(): void
    {
        /** @Given an arbitrary object that is not a Serializable, BackedEnum, or UnitEnum */
        $body = new Dragon(name: 'Drakengard Firestorm', weight: 6000.0);

        /** @Then an exception indicating the body type is unsupported is thrown */
        $this->expectException(BodyTypeIsUnsupported::class);
        $this->expectExceptionMessage('Response body type <Test\TinyBlocks\Http\Models\Dragon> is not supported');

        /** @When creating a response with the arbitrary object */
        Response::ok(body: $body);
    }

    public function testUnauthorizedWhenBodyGivenThenReturnsResponseWithStatus401(): void
    {
        /** @Given a body with error details */
        $body = ['error' => 'Unauthorized', 'message' => 'Authentication is required.'];

        /** @When the response is created with the body */
        $actual = Response::unauthorized(body: $body);

        /** @Then the status is 401 */
        self::assertSame(Code::UNAUTHORIZED->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    public function testGetBodyWhenStreamWrittenAdditionalDataThenReturnsByteCount(): void
    {
        /** @Given a response stream positioned at end-of-file */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();
        $stream->seek(0, SEEK_END);

        /** @When appending one byte via the StreamInterface write() */
        $written = $stream->write('+');

        /** @Then the write returns the byte count */
        self::assertSame(1, $written);
    }

    public function testGetBodyWhenMetadataRequestedAfterCloseThenReturnsEmptyArray(): void
    {
        /** @Given a closed response stream */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();
        $stream->close();

        /** @When asking for the full metadata map */
        $metadata = $stream->getMetadata();

        /** @Then an empty array is returned */
        self::assertSame([], $metadata);
    }

    public function testResponseFacadeForbidsInstantiationThroughAPrivateConstructor(): void
    {
        /** @Given the reflection of the public Response façade */
        $reflection = new ReflectionClass(Response::class);

        /** @And the constructor reflected from that class */
        $constructor = $reflection->getMethod('__construct');

        /** @When invoking the empty private constructor on a bare instance */
        $constructor->invoke($reflection->newInstanceWithoutConstructor());

        /** @Then the constructor is private to prevent direct instantiation */
        self::assertTrue($constructor->isPrivate());
    }

    public function testWithStatusWhenCustomReasonPhraseGivenThenReasonPhraseIsHonored(): void
    {
        /** @Given an HTTP response */
        $response = Response::ok(body: null);

        /** @When calling withStatus with a custom reason phrase */
        $updated = $response->withStatus(Code::OK->value, 'All Good');

        /** @Then the custom reason phrase is returned */
        self::assertSame('All Good', $updated->getReasonPhrase());
    }

    public function testServiceUnavailableWhenBodyGivenThenReturnsResponseWithStatus503(): void
    {
        /** @Given a body with service downtime details */
        $body = ['error' => 'Service Unavailable', 'message' => 'The service is temporarily unavailable.'];

        /** @When the response is created with the body */
        $actual = Response::serviceUnavailable(body: $body);

        /** @Then the status is 503 */
        self::assertSame(Code::SERVICE_UNAVAILABLE->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    public function testWithStatusWhenEmptyReasonPhraseGivenThenEnumDerivedPhraseIsUsed(): void
    {
        /** @Given an HTTP response */
        $response = Response::ok(body: null);

        /** @When calling withStatus with an empty reason phrase */
        $updated = $response->withStatus(Code::OK->value);

        /** @Then the enum-derived phrase is returned */
        self::assertSame(Code::OK->message(), $updated->getReasonPhrase());
    }

    public function testGetBodyWhenSeekedToOffsetThenSubsequentReadsResumeFromThatOffset(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When seeking past the opening brace */
        $stream->seek(1);

        /** @Then the next read starts at the seeked offset */
        self::assertSame('"', $stream->read(1));
    }

    public function testInternalServerErrorWhenBodyGivenThenReturnsResponseWithStatus500(): void
    {
        /** @Given a body with error details */
        $body = ['code' => 10000, 'message' => 'An unexpected error occurred on the server.'];

        /** @When the response is created with the body */
        $actual = Response::internalServerError(body: $body);

        /** @Then the status is 500 */
        self::assertSame(Code::INTERNAL_SERVER_ERROR->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    public function testUnprocessableEntityWhenBodyGivenThenReturnsResponseWithStatus422(): void
    {
        /** @Given a body with validation errors */
        $body = ['error' => 'Validation Failed', 'message' => 'The input data did not pass validation.'];

        /** @When the response is created with the body */
        $actual = Response::unprocessableEntity(body: $body);

        /** @Then the status is 422 */
        self::assertSame(Code::UNPROCESSABLE_ENTITY->value, $actual->getStatusCode());
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));
    }

    public function testWithStatusWhenCustomPhraseSetThenSubsequentWithHeaderPreservesIt(): void
    {
        /** @Given an HTTP response with a custom reason phrase */
        $response = Response::ok(body: null)->withStatus(Code::OK->value, 'All Good');

        /** @When adding a header to that response */
        $updated = $response->withHeader('X-Trace-Id', 'abc');

        /** @Then the custom reason phrase is still returned */
        self::assertSame('All Good', $updated->getReasonPhrase());
    }

    public function testGetBodyWhenStreamWrittenAdditionalDataThenContentsGrowAccordingly(): void
    {
        /** @Given a response stream positioned at end-of-file */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();
        $stream->seek(0, SEEK_END);

        /** @When appending one byte via the StreamInterface write() */
        $stream->write('+');

        /** @Then the stream size grows accordingly */
        self::assertSame(strlen('{"name":"Hydra"}+'), $stream->getSize());
    }

    public function testGetBodyWhenContentsReadThenReturnsTheWrittenJsonWithoutRequiringRewind(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When reading the stream contents directly */
        $contents = $stream->getContents();

        /** @Then the contents match the encoded body without needing a manual rewind */
        self::assertSame('{"name":"Hydra"}', $contents);
    }

    public function testGetBodyWhenMetadataRequestedForModeKeyThenExposesUnderlyingResourceMode(): void
    {
        /** @Given a response with a body */
        $stream = Response::ok(body: ['name' => 'Hydra'])->getBody();

        /** @When asking for the stream mode key */
        $mode = $stream->getMetadata('mode');

        /** @Then the value reflects the in-memory resource mode */
        self::assertSame('w+b', $mode);
    }

    public static function bodyProviderData(): array
    {
        return [
            'UnitEnum'           => [
                'body'     => Color::RED,
                'expected' => 'RED'
            ],
            'BackedEnum'         => [
                'body'     => Status::PAID,
                'expected' => '1'
            ],
            'Null value'         => [
                'body'     => null,
                'expected' => ''
            ],
            'Empty string'       => [
                'body'     => '',
                'expected' => ''
            ],
            'Non-empty string'   => [
                'body'     => 'Hello, World!',
                'expected' => 'Hello, World!'
            ],
            'Serializer object'  => [
                'body'     => new Order(
                    id: 1,
                    products: new Products(elements: [
                        new Product(name: 'Product One', amount: new Amount(value: 100.50, currency: Currency::USD)),
                        new Product(name: 'Product Two', amount: new Amount(value: 200.75, currency: Currency::BRL))
                    ])
                ),
                'expected' => json_encode([
                    'id'       => 1,
                    'products' => [
                        ['name' => 'Product One', 'amount' => ['value' => 100.50, 'currency' => 'USD']],
                        ['name' => 'Product Two', 'amount' => ['value' => 200.75, 'currency' => 'BRL']]
                    ]
                ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)
            ],
            'Boolean true value' => [
                'body'     => true,
                'expected' => 'true'
            ],
            'Boolean false value' => [
                'body'     => false,
                'expected' => 'false'
            ],
            'Large integer value' => [
                'body'     => PHP_INT_MAX,
                'expected' => (string)PHP_INT_MAX
            ]
        ];
    }

    public static function responseFromProvider(): array
    {
        return [
            'I am a teapot'                           => [
                'code'         => Code::IM_A_TEAPOT,
                'body'         => 'Short and stout',
                'expectedBody' => 'Short and stout'
            ],
            'OK with array body'                      => [
                'code'         => Code::OK,
                'body'         => ['status' => 'success'],
                'expectedBody' => '{"status":"success"}'
            ],
            'Accepted with null body'                 => [
                'code'         => Code::ACCEPTED,
                'body'         => null,
                'expectedBody' => ''
            ],
            'Not Found with string body'              => [
                'code'         => Code::NOT_FOUND,
                'body'         => 'Resource not found',
                'expectedBody' => 'Resource not found'
            ],
            'Internal Server Error with complex body' => [
                'code'         => Code::INTERNAL_SERVER_ERROR,
                'body'         => ['error' => ['code' => 500, 'message' => 'Crash']],
                'expectedBody' => '{"error":{"code":500,"message":"Crash"}}'
            ]
        ];
    }
}
