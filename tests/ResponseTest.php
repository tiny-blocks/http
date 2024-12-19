<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Internal\Exceptions\BadMethodCall;
use TinyBlocks\Http\Internal\Response\Stream\StreamFactory;
use TinyBlocks\Http\Models\Amount;
use TinyBlocks\Http\Models\Color;
use TinyBlocks\Http\Models\Currency;
use TinyBlocks\Http\Models\Dragon;
use TinyBlocks\Http\Models\Order;
use TinyBlocks\Http\Models\Product;
use TinyBlocks\Http\Models\Products;
use TinyBlocks\Http\Models\Status;

final class ResponseTest extends TestCase
{
    public function testResponseOk(): void
    {
        /** @Given a body with data */
        $body = [
            'id'     => PHP_INT_MAX,
            'name'   => 'Drakengard Firestorm',
            'type'   => 'Dragon',
            'weight' => 6000.00
        ];

        /** @When we create the HTTP response with this body */
        $actual = Response::ok(body: $body);

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should match the JSON-encoded body */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->getContents());

        /** @And the status code should be 200 */
        self::assertSame(Code::OK->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isSuccessCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "OK" */
        self::assertSame(Code::OK->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testResponseCreated(): void
    {
        /** @Given a body with data */
        $body = [
            'id'     => 1,
            'name'   => 'New Resource',
            'type'   => 'Item',
            'weight' => 100.00
        ];

        /** @When we create the HTTP response with this body */
        $actual = Response::created(body: $body);

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should match the JSON-encoded body */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->getContents());

        /** @And the status code should be 201 */
        self::assertSame(Code::CREATED->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isSuccessCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "Created" */
        self::assertSame(Code::CREATED->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testResponseAccepted(): void
    {
        /** @Given a body with data */
        $body = [
            'id'     => 1,
            'status' => 'Processing'
        ];

        /** @When we create the HTTP response with this body */
        $actual = Response::accepted(body: $body);

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should match the JSON-encoded body */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->getContents());

        /** @And the status code should be 202 */
        self::assertSame(Code::ACCEPTED->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isSuccessCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "Accepted" */
        self::assertSame(Code::ACCEPTED->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testResponseNoContent(): void
    {
        /** @Given I have no data for the body */
        /** @When we create the HTTP response without body */
        $actual = Response::noContent();

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should be empty */
        self::assertEmpty($actual->getBody()->__toString());
        self::assertEmpty($actual->getBody()->getContents());

        /** @And the status code should be 204 */
        self::assertSame(Code::NO_CONTENT->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isSuccessCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "No Content" */
        self::assertSame(Code::NO_CONTENT->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testResponseBadRequest(): void
    {
        /** @Given a body with error details */
        $body = [
            'error'   => 'Invalid request',
            'message' => 'The request body is malformed.'
        ];

        /** @When we create the HTTP response with this body */
        $actual = Response::badRequest(body: $body);

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should match the JSON-encoded body */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->getContents());

        /** @And the status code should be 400 */
        self::assertSame(Code::BAD_REQUEST->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "Bad Request" */
        self::assertSame(Code::BAD_REQUEST->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testResponseNotFound(): void
    {
        /** @Given a body with error details */
        $body = [
            'error'   => 'Not found',
            'message' => 'The requested resource could not be found.'
        ];

        /** @When we create the HTTP response with this body */
        $actual = Response::notFound(body: $body);

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should match the JSON-encoded body */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->getContents());

        /** @And the status code should be 404 */
        self::assertSame(Code::NOT_FOUND->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "Not Found" */
        self::assertSame(Code::NOT_FOUND->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testResponseConflict(): void
    {
        /** @Given a body with conflict details */
        $body = [
            'error'   => 'Conflict',
            'message' => 'There is a conflict with the current state of the resource.'
        ];

        /** @When we create the HTTP response with this body */
        $actual = Response::conflict(body: $body);

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should match the JSON-encoded body */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->getContents());

        /** @And the status code should be 409 */
        self::assertSame(Code::CONFLICT->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "Conflict" */
        self::assertSame(Code::CONFLICT->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testResponseUnprocessableEntity(): void
    {
        /** @Given a body with validation errors */
        $body = [
            'error'   => 'Validation Failed',
            'message' => 'The input data did not pass validation.'
        ];

        /** @When we create the HTTP response with this body */
        $actual = Response::unprocessableEntity(body: $body);

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should match the JSON-encoded body */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->getContents());

        /** @And the status code should be 422 */
        self::assertSame(Code::UNPROCESSABLE_ENTITY->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "Unprocessable Entity" */
        self::assertSame(Code::UNPROCESSABLE_ENTITY->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    public function testResponseInternalServerError(): void
    {
        /** @Given a body with error details */
        $body = [
            'code'    => 10000,
            'message' => 'An unexpected error occurred on the server.'
        ];

        /** @When we create the HTTP response with this body */
        $actual = Response::internalServerError(body: $body);

        /** @Then the protocol version should be "1.1" */
        self::assertSame('1.1', $actual->getProtocolVersion());

        /** @And the body of the response should match the JSON-encoded body */
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->__toString());
        self::assertSame(json_encode($body, JSON_PRESERVE_ZERO_FRACTION), $actual->getBody()->getContents());

        /** @And the status code should be 500 */
        self::assertSame(Code::INTERNAL_SERVER_ERROR->value, $actual->getStatusCode());
        self::assertTrue(Code::isValidCode(code: $actual->getStatusCode()));
        self::assertTrue(Code::isErrorCode(code: $actual->getStatusCode()));

        /** @And the reason phrase should be "Internal Server Error" */
        self::assertSame(Code::INTERNAL_SERVER_ERROR->message(), $actual->getReasonPhrase());

        /** @And the headers should contain Content-Type as application/json with charset=utf-8 */
        self::assertSame(['Content-Type' => ['application/json; charset=utf-8']], $actual->getHeaders());
    }

    #[DataProvider('bodyProviderData')]
    public function testResponseBodySerialization(mixed $body, string $expected): void
    {
        /** @Given the body contains the provided data */
        /** @When we create an HTTP response with the given body */
        $actual = Response::ok(body: $body);

        /** @Then the body of the response should match the expected output */
        self::assertSame($expected, $actual->getBody()->__toString());
        self::assertSame($expected, $actual->getBody()->getContents());
    }

    public function testResponseWithBody(): void
    {
        /** @Given an HTTP response with without body */
        $response = Response::ok(body: null);

        /** @When the body of the response is initially empty */
        self::assertEmpty($response->getBody()->__toString());
        self::assertEmpty($response->getBody()->getContents());

        /** @And a new body is set for the response */
        $body = 'This is a new body';
        $actual = $response->withBody(body: StreamFactory::fromBody(body: $body)->write());

        /** @Then the response body should be updated to match the new content */
        self::assertSame($body, $actual->getBody()->__toString());
        self::assertSame($body, $actual->getBody()->getContents());
    }

    public function testExceptionWhenBadMethodCallOnWithStatus(): void
    {
        /** @Given an HTTP response */
        $response = Response::noContent();

        /** @Then a BadMethodCall exception should be thrown when calling withStatus */
        self::expectException(BadMethodCall::class);
        self::expectExceptionMessage('Method <withStatus> cannot be used.');

        /** @When attempting to call withStatus */
        $response->withStatus(code: Code::OK->value);
    }

    public static function bodyProviderData(): array
    {
        return [
            'UnitEnum'                => [
                'body'     => Color::RED,
                'expected' => 'RED'
            ],
            'BackedEnum'              => [
                'body'     => Status::PAID,
                'expected' => '1'
            ],
            'Null value'              => [
                'body'     => null,
                'expected' => ''
            ],
            'Empty string'            => [
                'body'     => '',
                'expected' => ''
            ],
            'Simple object'           => [
                'body'     => new Dragon(name: 'Drakengard Firestorm', weight: 6000.0),
                'expected' => '{"name":"Drakengard Firestorm","weight":6000.0}'
            ],
            'Non-empty string'        => [
                'body'     => 'Hello, World!',
                'expected' => 'Hello, World!'
            ],
            'Serializer object'       => [
                'body'     => new Order(
                    id: 1,
                    products: new Products(elements: [
                        new Product(name: 'Product One', amount: new Amount(value: 100.50, currency: Currency::USD)),
                        new Product(name: 'Product Two', amount: new Amount(value: 200.75, currency: Currency::BRL))
                    ])
                ),
                'expected' => '{"id":1,"products":[{"name":"Product One","amount":{"value":100.5,"currency":"USD"}},{"name":"Product Two","amount":{"value":200.75,"currency":"BRL"}}]}'
            ],
            'Boolean true value'      => [
                'body'     => true,
                'expected' => 'true'
            ],
            'Boolean false value'     => [
                'body'     => false,
                'expected' => 'false'
            ],
            'Large integer value'     => [
                'body'     => PHP_INT_MAX,
                'expected' => (string)PHP_INT_MAX
            ],
            'DateTimeInterface value' => [
                'body'     => new DateTime('2024-12-16'),
                'expected' => '[]'
            ]
        ];
    }
}
