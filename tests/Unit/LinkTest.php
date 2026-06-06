<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\Http\Exceptions\LinkUriIsInvalid;
use TinyBlocks\Http\Link;
use TinyBlocks\Http\LinkRelation;
use TinyBlocks\Http\Server\Response;

final class LinkTest extends TestCase
{
    #[DataProvider('invalidUrisProvider')]
    public function testToWhenInvalidUriGivenThenThrowsLinkUriIsInvalid(string $uri): void
    {
        /** @Given a link target URI that breaks the RFC 8288 link target rules */

        /** @Then an exception indicating the link URI is invalid is raised */
        $this->expectException(LinkUriIsInvalid::class);
        $this->expectExceptionMessage('is invalid');

        /** @When starting a Link to that URI */
        Link::to(uri: $uri, relation: LinkRelation::NEXT);
    }

    public function testOkWhenLinkGivenThenResponseCarriesFoldedLinkHeader(): void
    {
        /** @Given a Link to a single next-page target */
        $link = Link::to(uri: 'https://api.example.com/items?page=2&per_page=20', relation: LinkRelation::NEXT);

        /** @When building an OK response that carries the Link header */
        $response = Response::ok(['data' => []], $link);

        /** @Then the response exposes the folded Link header value */
        self::assertSame(
            '<https://api.example.com/items?page=2&per_page=20>; rel="next"',
            $response->getHeaderLine('Link')
        );
    }

    public function testToArrayWhenSingleLinkGivenThenRendersFoldedLinkValue(): void
    {
        /** @Given a Link to a single next-page target */
        $link = Link::to(uri: 'https://api.example.com/items?page=2&per_page=20', relation: LinkRelation::NEXT);

        /** @When converting it to the header map */
        $actual = $link->toArray();

        /** @Then the map carries the single folded RFC 8288 link value */
        self::assertSame(['Link' => ['<https://api.example.com/items?page=2&per_page=20>; rel="next"']], $actual);
    }

    public function testToArrayWhenMultipleLinksGivenThenJoinsThemInInsertionOrder(): void
    {
        /** @Given a Link chaining every pagination target in navigation order */
        $links = Link::to(uri: 'https://api.example.com/items?page=1', relation: LinkRelation::FIRST)
            ->and(uri: 'https://api.example.com/items?page=4', relation: LinkRelation::PREVIOUS)
            ->and(uri: 'https://api.example.com/items?page=6', relation: LinkRelation::NEXT)
            ->and(uri: 'https://api.example.com/items?page=9', relation: LinkRelation::LAST);

        /** @When converting it to the header map */
        $actual = $links->toArray();

        /** @Then every relation is folded and the links join with a comma in insertion order */
        self::assertSame(['Link' => [implode(', ', [
            '<https://api.example.com/items?page=1>; rel="first"',
            '<https://api.example.com/items?page=4>; rel="prev"',
            '<https://api.example.com/items?page=6>; rel="next"',
            '<https://api.example.com/items?page=9>; rel="last"'
        ])]], $actual);
    }

    public function testAndWhenInvokedThenReturnsNewInstanceLeavingTheOriginalUnchanged(): void
    {
        /** @Given a Link to a single self target */
        $original = Link::to(uri: 'https://api.example.com/items?page=2', relation: LinkRelation::SELF);

        /** @When appending another link */
        $extended = $original->and(uri: 'https://api.example.com/items?page=3', relation: LinkRelation::NEXT);

        /** @Then a new Link instance is returned */
        self::assertNotSame($original, $extended);

        /** @And the original Link is left unchanged */
        self::assertSame(['Link' => ['<https://api.example.com/items?page=2>; rel="self"']], $original->toArray());
    }

    public static function invalidUrisProvider(): array
    {
        return [
            'Empty string'          => ['uri' => ''],
            'Whitespace only'       => ['uri' => '   '],
            'Opening angle bracket' => ['uri' => 'https://api.example.com/<items'],
            'Closing angle bracket' => ['uri' => 'https://api.example.com/items>'],
            'Carriage return'       => ['uri' => "https://api.example.com/items\r"],
            'Line feed'             => ['uri' => "https://api.example.com/items\n"]
        ];
    }
}
