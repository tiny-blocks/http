<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use TinyBlocks\Http\Exceptions\LinkUriIsInvalid;

/**
 * RFC 8288 Link header carrying one or more web links, each pairing a target URI with a relation type.
 *
 * @see https://www.rfc-editor.org/rfc/rfc8288
 */
final readonly class Link implements Headerable
{
    private function __construct(private array $links)
    {
    }

    /**
     * Creates a Link from a target URI and its relation type.
     *
     * @param string $uri The link target URI.
     * @param LinkRelation $relation The relation type folded into the link value.
     * @return Link A Link carrying the single web link.
     * @throws LinkUriIsInvalid If the URI is blank or carries a CR, LF, or angle bracket.
     */
    public static function to(string $uri, LinkRelation $relation): Link
    {
        return new Link(links: [])->and(uri: $uri, relation: $relation);
    }

    /**
     * Appends another web link and returns a new Link with every existing link plus the appended one.
     *
     * @param string $uri The link target URI.
     * @param LinkRelation $relation The relation type folded into the link value.
     * @return Link A new Link carrying every existing web link followed by the appended one.
     * @throws LinkUriIsInvalid If the URI is blank or carries a CR, LF, or angle bracket.
     */
    public function and(string $uri, LinkRelation $relation): Link
    {
        if (trim($uri) === '' || preg_match('/[\r\n<>]/', $uri) === 1) {
            throw LinkUriIsInvalid::for(uri: $uri);
        }

        $template = '<%s>; rel="%s"';
        $link = sprintf($template, $uri, $relation->value);

        return new Link(links: [...$this->links, $link]);
    }

    public function toArray(): array
    {
        return ['Link' => [implode(', ', $this->links)]];
    }
}
