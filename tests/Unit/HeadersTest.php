<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\Headers;

final class HeadersTest extends TestCase
{
    public function testGetWhenMissingKeyGivenThenReturnsNull(): void
    {
        /** @Given headers with one entry */
        $headers = Headers::fromArray(entries: ['Content-Type' => 'application/json']);

        /** @When looking up a non-existent header */
        /** @Then null is returned */
        self::assertNull($headers->get('X-Missing'));
    }

    public function testWithWhenNewNameGivenThenAppendsHeader(): void
    {
        /** @Given headers with one entry */
        $headers = Headers::fromArray(entries: ['Accept' => 'application/json']);

        /** @When adding a header with a name not already present */
        $updated = $headers->with(name: 'X-Trace-Id', value: 'abc-123');

        /** @Then the new header is appended and the original is preserved */
        self::assertSame('application/json', $updated->get('Accept'));
        self::assertSame('abc-123', $updated->get('X-Trace-Id'));

        /** @And the original instance is unchanged */
        self::assertNull($headers->get('X-Trace-Id'));
    }

    public function testHasWhenMissingKeyGivenThenReturnsFalse(): void
    {
        /** @Given empty headers */
        $headers = Headers::fromArray(entries: []);

        /** @When checking for a non-existent header */
        /** @Then has() returns false */
        self::assertFalse($headers->has('Content-Type'));
    }

    #[DataProvider('invalidHeaderNameProvider')]
    public function testWithWhenInvalidHeaderNameGivenThenThrows(string $name): void
    {
        /** @Given a valid Headers instance */
        $headers = Headers::fromArray(entries: []);

        /** @Then an exception indicating the name is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When adding a header with an invalid name */
        $headers->with(name: $name, value: 'value');
    }

    #[DataProvider('invalidHeaderValueProvider')]
    public function testWithWhenInvalidHeaderValueGivenThenThrows(string $value): void
    {
        /** @Given a valid Headers instance */
        $headers = Headers::fromArray(entries: []);

        /** @Then an exception indicating the value is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);

        /** @When adding a header with an invalid value */
        $headers->with(name: 'X-Custom', value: $value);
    }

    public function testApplyToWhenEntriesGivenThenAttachesHeaders(): void
    {
        /** @Given a Headers instance with one entry */
        $headers = Headers::fromArray(entries: ['X-Trace' => 'abc']);

        /** @And a PSR-7 request */
        $psrRequest = new Psr17Factory()->createRequest('GET', 'https://api.example.com');

        /** @When applying the headers to the request */
        $applied = $headers->applyTo(message: $psrRequest);

        /** @Then the resulting message carries the header */
        self::assertSame('abc', $applied->getHeaderLine('X-Trace'));
    }

    public function testWithWhenExistingNameGivenThenReplacesEntry(): void
    {
        /** @Given headers with a Content-Type entry */
        $headers = Headers::fromArray(entries: ['Content-Type' => 'application/json']);

        /** @When replacing the Content-Type value */
        $updated = $headers->with(name: 'Content-Type', value: 'text/plain');

        /** @Then the value is replaced on the updated instance */
        self::assertSame('text/plain', $updated->get('Content-Type'));

        /** @And the original instance retains its original value */
        self::assertSame('application/json', $headers->get('Content-Type'));
    }

    #[DataProvider('validHeaderNameProvider')]
    public function testFromArrayWhenValidHeaderNameGivenThenAccepts(string $name): void
    {
        /** @Given a valid header name */

        /** @When creating Headers from an array with that name */
        $headers = Headers::fromArray(entries: [$name => 'value']);

        /** @Then the header is present */
        self::assertTrue($headers->has($name));
    }

    #[DataProvider('invalidHeaderNameProvider')]
    public function testFromArrayWhenInvalidHeaderNameGivenThenThrows(string $name): void
    {
        /** @Given an invalid header name */

        /** @Then an exception indicating the name is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is invalid');

        /** @When creating Headers from an array with that name */
        Headers::fromArray(entries: [$name => 'value']);
    }

    #[DataProvider('validHeaderValueProvider')]
    public function testFromArrayWhenValidHeaderValueGivenThenAccepts(string $value): void
    {
        /** @Given a valid header value */

        /** @When creating Headers from an array with that value */
        $headers = Headers::fromArray(entries: ['X-Custom' => $value]);

        /** @Then the header value is present */
        self::assertSame($value, $headers->get('X-Custom'));
    }

    public function testHasWhenMixedCaseKeyGivenThenIsCaseInsensitive(): void
    {
        /** @Given headers with a mixed-case key */
        $headers = Headers::fromArray(entries: ['X-Trace' => 'abc']);

        /** @When checking existence with different casing */
        /** @Then has() returns true regardless of case */
        self::assertTrue($headers->has('x-trace'));
        self::assertTrue($headers->has('X-TRACE'));
        self::assertTrue($headers->has('X-Trace'));
    }

    public function testToArrayWhenMultipleEntriesGivenThenReturnsAll(): void
    {
        /** @Given headers with two entries */
        $headers = Headers::fromArray(entries: ['X-Trace' => 'abc', 'X-Request-ID' => '123']);

        /** @When converting to array */
        $array = $headers->toArray();

        /** @Then all entries are present */
        self::assertSame('abc', $array['X-Trace']);
        self::assertSame('123', $array['X-Request-ID']);
        self::assertCount(2, $array);
    }

    #[DataProvider('invalidHeaderValueProvider')]
    public function testFromArrayWhenInvalidHeaderValueGivenThenThrows(string $value): void
    {
        /** @Given an invalid header value */

        /** @Then an exception indicating the value is invalid is thrown */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is invalid');

        /** @When creating Headers from an array with that value */
        Headers::fromArray(entries: ['X-Custom' => $value]);
    }

    public function testWithWhenCasingDiffersThenReplacesExistingEntry(): void
    {
        /** @Given headers with a Content-Type entry stored under mixed case */
        $headers = Headers::fromArray(entries: ['Content-Type' => 'application/json']);

        /** @When replacing using a different casing */
        $updated = $headers->with(name: 'content-type', value: 'text/plain');

        /** @Then only one Content-Type entry exists and it carries the new value */
        self::assertSame('text/plain', $updated->get('Content-Type'));
        self::assertCount(1, $updated->toArray());
    }

    public function testConstructorWhenEntriesGivenThenExposesEachEntry(): void
    {
        /** @Given an array of headers */
        $entries = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

        /** @When creating Headers from an array */
        $headers = Headers::fromArray(entries: $entries);

        /** @Then the entries are accessible */
        self::assertSame('application/json', $headers->get('Content-Type'));
        self::assertSame('application/json', $headers->get('Accept'));
    }

    public function testFromWhenNoArgumentsGivenThenReturnsEmptyHeaders(): void
    {
        /** @When creating Headers with no headerable arguments */
        $headers = Headers::from();

        /** @Then the headers are empty */
        self::assertSame([], $headers->toArray());
    }

    public function testMergedWithWhenOtherCollidesThenExistingEntryWins(): void
    {
        /** @Given headers with a Content-Type entry */
        $headers = Headers::fromArray(entries: ['Content-Type' => 'application/json; charset=utf-8']);

        /** @When merging with a Headers carrying a default Content-Type */
        $merged = $headers->mergedWith(other: Headers::fromArray(entries: ['Content-Type' => 'application/json']));

        /** @Then the existing header wins */
        self::assertSame('application/json; charset=utf-8', $merged->get('Content-Type'));

        /** @And only one Content-Type entry exists in the merged result */
        self::assertCount(1, $merged->toArray());
    }

    public function testFromWhenMultipleHeaderablesGivenThenMergesEntries(): void
    {
        /** @Given a Content-Type headerable */
        $contentType = ContentType::applicationJson(charset: Charset::UTF_8);

        /** @And a Cookie headerable */
        $cookie = Cookie::create(name: 'session', value: 'abc123');

        /** @When creating Headers from multiple headerables */
        $headers = Headers::from($contentType, $cookie);

        /** @Then both header entries are present */
        self::assertTrue($headers->has('Content-Type'));
        self::assertTrue($headers->has('Set-Cookie'));
    }

    public function testApplyToWhenEntriesGivenThenLeavesOriginalUnchanged(): void
    {
        /** @Given a Headers instance with one entry */
        $headers = Headers::fromArray(entries: ['X-Trace' => 'abc']);

        /** @And a PSR-7 request */
        $psrRequest = new Psr17Factory()->createRequest('GET', 'https://api.example.com');

        /** @When applying the headers to the request */
        $headers->applyTo(message: $psrRequest);

        /** @Then the original request is unchanged */
        self::assertSame('', $psrRequest->getHeaderLine('X-Trace'));
    }

    public function testGetWhenMixedCaseKeyGivenThenLookupIsCaseInsensitive(): void
    {
        /** @Given headers with a mixed-case key */
        $headers = Headers::fromArray(entries: ['Content-Type' => 'application/json']);

        /** @When looking up with different casing */
        /** @Then the lookup succeeds */
        self::assertSame('application/json', $headers->get('content-type'));
        self::assertSame('application/json', $headers->get('CONTENT-TYPE'));
        self::assertSame('application/json', $headers->get('Content-Type'));
    }

    public function testWithWhenUpperCaseNameGivenThenReplacesExistingEntry(): void
    {
        /** @Given headers with a Content-Type entry stored under mixed case */
        $headers = Headers::fromArray(entries: ['Content-Type' => 'application/json']);

        /** @When replacing using an entirely uppercase name */
        $updated = $headers->with(name: 'CONTENT-TYPE', value: 'text/plain');

        /** @Then only one Content-Type entry exists and it carries the new value */
        self::assertSame('text/plain', $updated->get('Content-Type'));
        self::assertCount(1, $updated->toArray());
    }

    public function testMergedWithWhenCasingDiffersThenStillTreatsAsCollision(): void
    {
        /** @Given headers with a lowercase key */
        $headers = Headers::fromArray(entries: ['content-type' => 'application/json; charset=utf-8']);

        /** @When merging with a Headers using mixed casing */
        $merged = $headers->mergedWith(other: Headers::fromArray(entries: ['Content-Type' => 'application/json']));

        /** @Then the existing header wins despite different casing */
        self::assertSame('application/json; charset=utf-8', $merged->get('content-type'));

        /** @And only one Content-Type entry exists in the merged result */
        self::assertCount(1, $merged->toArray());
    }

    public function testFromMessageWhenMultiValueHeaderGivenThenFoldsWithComma(): void
    {
        /** @Given a PSR-7 response with a header that carries multiple values */
        $psrResponse = new Psr17Factory()->createResponse(200)
            ->withHeader('Accept', 'application/json')
            ->withAddedHeader('Accept', 'text/html');

        /** @When building Headers from the message */
        $headers = Headers::fromMessage(message: $psrResponse);

        /** @Then the multi-value header is folded with a comma separator */
        self::assertSame('application/json, text/html', $headers->get('Accept'));
    }

    public function testMergedWithWhenOtherHasNewEntriesThenBothAppearInResult(): void
    {
        /** @Given headers with one entry */
        $headers = Headers::fromArray(entries: ['Accept' => 'application/json']);

        /** @When merging with a Headers carrying a default that does not conflict */
        $merged = $headers->mergedWith(other: Headers::fromArray(entries: ['Content-Type' => 'application/json']));

        /** @Then both entries are present */
        self::assertSame('application/json', $merged->get('Accept'));
        self::assertSame('application/json', $merged->get('Content-Type'));
    }

    public function testApplyToWhenEmptyHeadersGivenThenReturnsMessageUnchanged(): void
    {
        /** @Given an empty Headers instance */
        $headers = Headers::fromArray(entries: []);

        /** @And a PSR-7 request */
        $psrRequest = new Psr17Factory()->createRequest('GET', 'https://api.example.com');

        /** @When applying the empty headers to the request */
        $applied = $headers->applyTo(message: $psrRequest);

        /** @Then the same request instance is returned without modification */
        self::assertSame($psrRequest, $applied);
    }

    public function testFromMessageWhenEmptyHeadersGivenThenReturnsEmptyHeaders(): void
    {
        /** @Given a PSR-7 response with no headers */
        $psrResponse = new Psr17Factory()->createResponse(200);

        /** @When building Headers from the message */
        $headers = Headers::fromMessage(message: $psrResponse);

        /** @Then the Headers instance is empty */
        self::assertSame([], $headers->toArray());
    }

    public function testWithWhenMultipleHeadersExistThenOtherHeadersArePreserved(): void
    {
        /** @Given headers with two entries */
        $headers = Headers::fromArray(entries: ['Content-Type' => 'application/json', 'Accept' => 'application/json']);

        /** @When replacing the Content-Type header */
        $updated = $headers->with(name: 'Content-Type', value: 'text/plain');

        /** @Then the replaced entry is updated and the other entry is unchanged */
        self::assertSame('text/plain', $updated->get('Content-Type'));
        self::assertSame('application/json', $updated->get('Accept'));
        self::assertCount(2, $updated->toArray());
    }

    public static function validHeaderNameProvider(): array
    {
        return [
            'Content-Type'  => ['Content-Type'],
            'X-Trace-Id'    => ['X-Trace-Id'],
            'Accept'        => ['Accept'],
            'Authorization' => ['Authorization']
        ];
    }

    public static function validHeaderValueProvider(): array
    {
        return [
            'application/json'            => ['application/json'],
            'text/plain with charset'     => ['text/plain; charset=utf-8'],
            'Value with horizontal tab'   => ["has\ttab"]
        ];
    }

    public static function invalidHeaderNameProvider(): array
    {
        return [
            'Empty name'           => [''],
            'Name with space'      => ['foo bar'],
            'Name with colon'      => ['foo:bar'],
            'Name with CR'         => ["foo\r"],
            'Name with CRLF'       => ["foo\r\nbar"],
            'Name with tab'        => ["foo\tbar"],
            'Name with null byte'  => ["foo\x00bar"]
        ];
    }

    public static function invalidHeaderValueProvider(): array
    {
        return [
            'Value with LF'           => ["foo\nbar"],
            'Value with CR'           => ["foo\rbar"],
            'CRLF injection'          => ["foo\r\nbar: injected"],
            'Value with null byte'    => ["foo\x00bar"]
        ];
    }
}
