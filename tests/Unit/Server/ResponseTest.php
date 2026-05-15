<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Server;

use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\Http\Models\Amount;
use Test\TinyBlocks\Http\Models\Color;
use Test\TinyBlocks\Http\Models\Currency;
use Test\TinyBlocks\Http\Models\Dragon;
use Test\TinyBlocks\Http\Models\Order;
use Test\TinyBlocks\Http\Models\Product;
use Test\TinyBlocks\Http\Models\Products;
use Test\TinyBlocks\Http\Models\Status;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Internal\Server\Stream\StreamFactory;
use TinyBlocks\Http\Server\Response;

final class ResponseTest extends TestCase
{
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

    /** @return array<string, array{code: Code, body: mixed, expectedBody: string}> */
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

    #[DataProvider('bodyProviderData')]
    public function testOkWhenAnyBodyShapeGivenThenSerializesToExpectedString(mixed $body, string $expected): void
    {
        /** @Given the body contains the provided data */
        /** @When we create an HTTP response with the given body */
        $actual = Response::ok(body: $body);

        /** @Then the body matches the expected output */
        self::assertSame($expected, $actual->getBody()->__toString());
    }

    public function testWithBodyWhenInvokedThenReplacesBodyContent(): void
    {
        /** @Given an HTTP response without body */
        $response = Response::ok(body: null);

        /** @When the body is initially empty */
        self::assertEmpty($response->getBody()->__toString());

        /** @And a new body is set for the response */
        $body = 'This is a new body';
        $actual = $response->withBody(body: StreamFactory::fromBody(body: $body)->write());

        /** @Then the response body matches the new content */
        self::assertSame($body, $actual->getBody()->__toString());
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

    /** @return array<string, array{body: mixed, expected: string}> */
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
                'expected' => json_encode([
                    'id'       => 1,
                    'products' => [
                        ['name' => 'Product One', 'amount' => ['value' => 100.50, 'currency' => 'USD']],
                        ['name' => 'Product Two', 'amount' => ['value' => 200.75, 'currency' => 'BRL']]
                    ]
                ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)
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
