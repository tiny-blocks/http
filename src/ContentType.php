<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * HTTP Content-Type header combining a MIME type with an optional character encoding.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
 */
final readonly class ContentType implements Headerable
{
    private function __construct(private MimeType $mimeType, private ?Charset $charset)
    {
    }

    /**
     * Creates a ContentType for <code>text/html</code> with an optional charset.
     *
     * @param Charset|null $charset The optional charset folded into the header value.
     * @return ContentType A ContentType for <code>text/html</code>.
     */
    public static function textHtml(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::TEXT_HTML, charset: $charset);
    }

    /**
     * Creates a ContentType for <code>text/plain</code> with an optional charset.
     *
     * @param Charset|null $charset The optional charset folded into the header value.
     * @return ContentType A ContentType for <code>text/plain</code>.
     */
    public static function textPlain(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::TEXT_PLAIN, charset: $charset);
    }

    /**
     * Creates a ContentType for <code>application/json</code> with an optional charset.
     *
     * @param Charset|null $charset The optional charset folded into the header value.
     * @return ContentType A ContentType for <code>application/json</code>.
     */
    public static function applicationJson(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::APPLICATION_JSON, charset: $charset);
    }

    /**
     * Creates a ContentType for <code>application/pdf</code> with an optional charset.
     *
     * @param Charset|null $charset The optional charset folded into the header value.
     * @return ContentType A ContentType for <code>application/pdf</code>.
     */
    public static function applicationPdf(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::APPLICATION_PDF, charset: $charset);
    }

    /**
     * Creates a ContentType for <code>application/octet-stream</code> with an optional charset.
     *
     * @param Charset|null $charset The optional charset folded into the header value.
     * @return ContentType A ContentType for <code>application/octet-stream</code>.
     */
    public static function applicationOctetStream(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::APPLICATION_OCTET_STREAM, charset: $charset);
    }

    /**
     * Creates a ContentType for <code>application/x-www-form-urlencoded</code> with an optional charset.
     *
     * @param Charset|null $charset The optional charset folded into the header value.
     * @return ContentType A ContentType for <code>application/x-www-form-urlencoded</code>.
     */
    public static function applicationFormUrlencoded(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::APPLICATION_FORM_URLENCODED, charset: $charset);
    }

    public function toArray(): array
    {
        if (is_null($this->charset)) {
            return ['Content-Type' => [$this->mimeType->value]];
        }

        $template = '%s; %s';

        return ['Content-Type' => [sprintf($template, $this->mimeType->value, $this->charset->toString())]];
    }
}
