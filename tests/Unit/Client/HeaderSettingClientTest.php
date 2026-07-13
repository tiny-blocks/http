<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\Http\Unit\ScriptedClient;
use TinyBlocks\Http\Client\HeaderSettingClient;

final class HeaderSettingClientTest extends TestCase
{
    public function testSetsTheResolvedValueAsAHeader(): void
    {
        /** @Given a client setting a header resolved at send time */
        $network = new ScriptedClient();
        $network->answers(new Response());
        $client = HeaderSettingClient::with(
            client: $network,
            headerValues: ['Correlation-Id' => static fn(): string => 'corr-123']
        );

        /** @When a request is sent */
        $client->sendRequest(new Request('GET', 'https://service.test/ping'));

        /** @Then the outbound request carries the resolved header */
        $sentRequest = $network->requestAt(index: 0);
        self::assertSame('corr-123', $sentRequest->getHeaderLine('Correlation-Id'));
    }

    public function testResolvesTheValueOnEverySend(): void
    {
        /** @Given a value that changes between sends */
        $values = ['first-id', 'second-id'];
        $network = new ScriptedClient();
        $network->answers(new Response(), new Response());
        $client = HeaderSettingClient::with(
            client: $network,
            headerValues: ['Correlation-Id' => static function () use (&$values): string {
                return array_shift($values);
            }]
        );

        /** @When two requests are sent */
        $client->sendRequest(new Request('GET', 'https://service.test/first'));
        $client->sendRequest(new Request('GET', 'https://service.test/second'));

        /** @Then each outbound request carries the value current at its send */
        self::assertSame('first-id', $network->requestAt(index: 0)->getHeaderLine('Correlation-Id'));
        self::assertSame('second-id', $network->requestAt(index: 1)->getHeaderLine('Correlation-Id'));
    }

    public function testOmitsTheHeaderWhenTheValueResolvesEmpty(): void
    {
        /** @Given a header value that resolves to an empty string */
        $network = new ScriptedClient();
        $network->answers(new Response());
        $client = HeaderSettingClient::with(
            client: $network,
            headerValues: ['Correlation-Id' => static fn(): string => '']
        );

        /** @When a request already carrying the header is sent */
        $client->sendRequest(
            new Request('GET', 'https://service.test/ping', ['Correlation-Id' => 'preexisting-id'])
        );

        /** @Then the request keeps what it already carried under that name */
        self::assertSame('preexisting-id', $network->requestAt(index: 0)->getHeaderLine('Correlation-Id'));
    }

    public function testReplacesAHeaderAlreadyPresentOnTheRequest(): void
    {
        /** @Given a header value resolving while the request already carries the same header */
        $network = new ScriptedClient();
        $network->answers(new Response());
        $client = HeaderSettingClient::with(
            client: $network,
            headerValues: ['Correlation-Id' => static fn(): string => 'resolved-id']
        );

        /** @When the request is sent */
        $client->sendRequest(
            new Request('GET', 'https://service.test/ping', ['Correlation-Id' => 'stale-id'])
        );

        /** @Then the resolved value replaces the preexisting one */
        self::assertSame('resolved-id', $network->requestAt(index: 0)->getHeaderLine('Correlation-Id'));
    }

    public function testKeepsSettingTheRemainingHeadersAfterAnEmptyValue(): void
    {
        /** @Given a first header resolving empty followed by a second one resolving a value */
        $network = new ScriptedClient();
        $network->answers(new Response());
        $client = HeaderSettingClient::with(
            client: $network,
            headerValues: [
                'Correlation-Id'  => static fn(): string => '',
                'X-Service-Token' => static fn(): string => 'token-xyz'
            ]
        );

        /** @When a request is sent */
        $client->sendRequest(new Request('GET', 'https://service.test/ping'));

        /** @Then the empty header is omitted and the following one is still set */
        $sentRequest = $network->requestAt(index: 0);
        self::assertFalse($sentRequest->hasHeader('Correlation-Id'));
        self::assertSame('token-xyz', $sentRequest->getHeaderLine('X-Service-Token'));
    }

    public function testSetsSeveralHeadersPreservingUnrelatedOnes(): void
    {
        /** @Given a client setting two headers on a request carrying an unrelated one */
        $network = new ScriptedClient();
        $network->answers(new Response());
        $client = HeaderSettingClient::with(
            client: $network,
            headerValues: [
                'X-Service-Token' => static fn(): string => 'token-abc',
                'Correlation-Id'  => static fn(): string => 'corr-999'
            ]
        );

        /** @When a request with an unrelated header is sent */
        $client->sendRequest(
            new Request('GET', 'https://service.test/ping', ['Accept' => 'application/json'])
        );

        /** @Then both resolved headers are set and the unrelated one is preserved */
        $sentRequest = $network->requestAt(index: 0);
        self::assertSame('token-abc', $sentRequest->getHeaderLine('X-Service-Token'));
        self::assertSame('corr-999', $sentRequest->getHeaderLine('Correlation-Id'));
        self::assertSame('application/json', $sentRequest->getHeaderLine('Accept'));
    }
}
