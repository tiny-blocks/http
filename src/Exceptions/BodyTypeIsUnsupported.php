<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Exceptions;

use InvalidArgumentException;

/**
 * Raised when an arbitrary object is passed as a response body.
 *
 * Only <code>Serializable</code>, <code>BackedEnum</code>, <code>UnitEnum</code>, scalar types, arrays,
 * and <code>null</code> are accepted. Passing a generic object (such as a domain entity or a value
 * object that does not implement <code>Serializable</code>) is rejected to prevent unintentional leakage
 * of object internals as JSON.
 */
final class BodyTypeIsUnsupported extends InvalidArgumentException implements HttpException
{
    private const string REASON_TEMPLATE = 'Response body type <%s> is not supported. Use a Serializable, BackedEnum, '
        . 'UnitEnum, scalar, array, or null.';

    private function __construct(string $class)
    {
        $template = BodyTypeIsUnsupported::REASON_TEMPLATE;

        parent::__construct(message: sprintf($template, $class));
    }

    /**
     * Creates a BodyTypeIsUnsupported signaling that the given class is not an accepted body type.
     *
     * @param string $class The fully qualified class name of the unsupported object.
     * @return BodyTypeIsUnsupported The composed exception describing the unsupported body type.
     */
    public static function for(string $class): BodyTypeIsUnsupported
    {
        return new BodyTypeIsUnsupported(class: $class);
    }
}
