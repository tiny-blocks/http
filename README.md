# Http

[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/tiny-blocks/http/blob/main/LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    + [Server](#server)
        - [Decoding a request](#decoding-a-request)
        - [Creating a response](#creating-a-response)
        - [Setting cookies](#setting-cookies)
        - [Status code](#status-code)
    + [Client](#client)
        - [Building Http with a PSR-18 client and PSR-17 factories](#building-http-with-a-psr-18-client-and-psr-17-factories)
        - [Making a request](#making-a-request)
        - [Reading the response](#reading-the-response)
        - [Query parameters](#query-parameters)
        - [Custom headers and content type](#custom-headers-and-content-type)
        - [Setting the User-Agent](#setting-the-user-agent)
        - [Error handling](#error-handling)
        - [Configuring timeouts](#configuring-timeouts)
        - [Testing with InMemoryTransport](#testing-with-inmemorytransport)
        - [Extending with custom transports](#extending-with-custom-transports)
* [FAQ](#faq)
* [License](#license)
* [Contributing](#contributing)

## Overview

The library covers both sides of an HTTP exchange:

- **Server side** (`TinyBlocks\Http\Server`) - decodes a PSR-7 `ServerRequestInterface` into typed accessors and builds
  outgoing `ResponseInterface` instances with cookies, cache-control, and status codes.
- **Client side** (`TinyBlocks\Http\Client`) - composes outbound requests, sends them through a `Transport` port backed
  by any PSR-18 client, and exposes responses with typed body and header access.

Shared primitives at `TinyBlocks\Http\`: `Method`, `Code`, `Headers`, `Headerable`, `ContentType`, `MimeType`,
`Charset`, `Cookie`, `SameSite`, `CacheControl`, `ResponseCacheDirectives`, `UserAgent`.

## Installation

```bash
composer require tiny-blocks/http
```

## How to use

### Server

#### Decoding a request

Wrap a PSR-7 `ServerRequestInterface` and read typed fields from the body, route parameters, and query string.

```php
<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Server\Request;

/** @var ServerRequestInterface $psrRequest */
$decoded = Request::from(request: $psrRequest)->decode();

$id = $decoded->uri()->route()->get(key: 'id')->toInteger();
$sort = $decoded->uri()->queryParameters()->get(key: 'sort')->toString();
$name = $decoded->body()->get(key: 'name')->toString();
$amount = $decoded->body()->get(key: 'amount')->toFloat();
```

The HTTP method is available as a typed `Method` enum:

```php
<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Server\Request;

/** @var ServerRequestInterface $psrRequest */
$method = Request::from(request: $psrRequest)->method();
```

#### Creating a response

Each helper returns a PSR-7 `ResponseInterface` and defaults to `application/json`:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Server\Response;

Response::ok(body: ['message' => 'Resource created successfully.']);
Response::created(body: ['id' => 42]);
Response::noContent();
Response::notFound(body: ['error' => 'Resource not found.']);
```

For custom status codes, use `from(...)`:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Code;
use TinyBlocks\Http\Server\Response;

Response::from(body: ['status' => 'accepted'], code: Code::ACCEPTED);
```

Attach additional headers via varargs of `Headerable`:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\ResponseCacheDirectives;
use TinyBlocks\Http\Server\Response;

$cacheControl = CacheControl::fromResponseDirectives(
    ResponseCacheDirectives::maxAge(maxAgeInWholeSeconds: 10000)
);

Response::ok(['ok' => true], $cacheControl, ContentType::applicationJson())
    ->withHeader(name: 'X-Trace-Id', value: 'abc-123');
```

`withStatus($code, $reasonPhrase)` honors the supplied reason phrase: when a non-empty string is
passed, `getReasonPhrase()` returns it instead of the enum-derived phrase.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Code;
use TinyBlocks\Http\Server\Response;

$response = Response::ok(body: null)->withStatus(Code::OK->value, 'All Good');
$response->getReasonPhrase(); # "All Good"
```

#### Setting cookies

`Cookie` implements `Headerable` and composes naturally with `Response`.

`withSameSite(SameSite::NONE)` automatically enables the `Secure` flag. Browsers reject
`SameSite=None` cookies that lack it. Calling `secure()` separately is not required.

`withMaxAge(...)` and `withExpires(...)` are mutually exclusive (last-write-wins): setting one
clears the other. This follows RFC 6265 §4.1.2.2, which specifies that `Max-Age` takes precedence
over `Expires` when both are present.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\SameSite;
use TinyBlocks\Http\Server\Response;

$session = Cookie::create(name: 'session', value: $token)
    ->secure()
    ->httpOnly()
    ->withPath(path: '/v1/sessions')
    ->withMaxAge(seconds: 604800)
    ->withSameSite(sameSite: SameSite::STRICT);

Response::ok(['ok' => true], $session);
```

Setting `SameSite=None` without calling `secure()` first is safe. Secure is set automatically:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\SameSite;
use TinyBlocks\Http\Server\Response;

# Secure is applied automatically when SameSite=None is set.
$crossSite = Cookie::create(name: 'session', value: $token)
    ->withSameSite(sameSite: SameSite::NONE);

Response::ok(['ok' => true], $crossSite);
```

To expire a cookie, use `Cookie::expire(...)` with the same `Path` and `Domain` used at creation.
The expired cookie carries both `Max-Age=0` and `Expires` set to the Unix epoch: modern browsers
honor `Max-Age`. The `Expires` fallback covers legacy user agents.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\SameSite;
use TinyBlocks\Http\Server\Response;

$expired = Cookie::expire(name: 'session')
    ->secure()
    ->httpOnly()
    ->withPath(path: '/v1/sessions')
    ->withSameSite(sameSite: SameSite::STRICT);

Response::noContent($expired);
```

#### Status code

The `Code` enum carries the full RFC HTTP status set with typed helpers:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Code;

Code::OK->value;                              # 200
Code::OK->message();                          # "OK"
Code::OK->isSuccess();                        # true
Code::CONTINUE->isInformational();            # true
Code::MOVED_PERMANENTLY->isRedirection();     # true
Code::BAD_REQUEST->isClientError();           # true
Code::INTERNAL_SERVER_ERROR->isError();       # true
Code::INTERNAL_SERVER_ERROR->isServerError(); # true

Code::isValidCode(code: 200);   # true
Code::isErrorCode(code: 500);   # true
Code::isSuccessCode(code: 200); # true
```

### Client

#### Building Http with a PSR-18 client and PSR-17 factories

Assemble the facade with any PSR-18 client and PSR-17 factories.

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Http;

$factory = new HttpFactory();
$client = new Client(config: ['timeout' => 30, 'connect_timeout' => 5]);

$http = Http::create()
    ->withBaseUrl(url: 'https://api.example.com')
    ->withTransport(transport: NetworkTransport::with(client: $client, factory: $factory))
    ->build();
```

For a single-call construction without the fluent builder:

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Http;

$client = new Client(config: ['timeout' => 30, 'connect_timeout' => 5]);
$factory = new HttpFactory();

$http = Http::with(
    baseUrl: 'https://api.example.com',
    transport: NetworkTransport::with(
        client: $client,
        factory: $factory
    )
);
```

#### Making a request

Six shortcut factories cover the most common HTTP methods. Supply only the arguments the request
needs. The `body`, `queryParameters`, and `headers` all default to absent or empty.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headers;

$response = $http->send(request: Request::get(url: '/v1/charges/abc123'));

$response = $http->send(
    request: Request::post(
        url: '/v1/charges',
        body: ['amount' => 1000, 'currency' => 'usd'],
        headers: Headers::from(ContentType::applicationJson())
    )
);

$response = $http->send(request: Request::delete(url: '/v1/charges/abc123'));
```

For HTTP methods not covered by the six shortcuts (`OPTIONS`, `TRACE`, `CONNECT`, or any custom
method), use `Request::for(...)`, which accepts an explicit `Method` argument:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

$response = $http->send(
    request: Request::for(url: '/v1/charges', method: Method::OPTIONS)
);
```

`Method` also exposes RFC 9110 safety and idempotency predicates:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Method;

Method::GET->isSafe();        # true  (RFC 9110 §9.2.1)
Method::POST->isSafe();       # false
Method::PUT->isIdempotent();  # true  (RFC 9110 §9.2.2)
Method::POST->isIdempotent(); # false
```

#### Reading the response

```php
<?php

declare(strict_types=1);

if ($response->isSuccess()) {
    $id = $response->body()->get(key: 'id')->toString();
    $amount = $response->body()->get(key: 'amount')->toInteger();
}

$response->raw();       # Psr\Http\Message\ResponseInterface
$response->code();      # Code enum
$response->headers();   # TinyBlocks\Http\Headers value object
```

`Headers` exposes case-insensitive lookup:

```php
$contentType = $response->headers()->get(name: 'content-type'); # "application/json"
$hasTrace = $response->headers()->has(name: 'X-Trace-Id');      # true
```

#### Query parameters

Pass query parameters via `queryParameters:`. The library encodes them in RFC 3986 form.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Request;

$response = $http->send(
    request: Request::get(
        url: '/v1/charges',
        queryParameters: ['status' => 'succeeded', 'limit' => 50]
    )
);
```

To replace query parameters on an existing request, use `withQueryParameters(...)`:

```php
$updated = $request->withQueryParameters(queryParameters: ['limit' => 100]);
```

#### Custom headers and content type

Compose any combination of `Headerable` via `Headers::from(...)`:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headerable;
use TinyBlocks\Http\Headers;

final readonly class IdempotencyKey implements Headerable
{
    public function __construct(private string $value)
    {
    }

    public function toArray(): array
    {
        return ['Idempotency-Key' => $this->value];
    }
}

$response = $http->send(
    request: Request::post(
        url: '/v1/charges',
        body: ['amount' => 1000],
        headers: Headers::from(
            ContentType::applicationJson(),
            new IdempotencyKey(value: $key)
        )
    )
);
```

Custom headers always win over the library's JSON defaults.

To add or replace a single header on an existing request, use `withHeader(...)`. The lookup is
case-insensitive: replacing `Content-Type` via `content-type` still finds and replaces the entry.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Request;

$updated = Request::get(url: '/v1/charges')
    ->withHeader(name: 'X-Trace-Id', value: 'abc-123');
```

#### Setting the User-Agent

The `UserAgent` value object implements `Headerable` and renders the standard
`User-Agent` header. An absent or empty version is normalized to "no version". The rendered
header carries only the product token in that case.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\UserAgent;

$userAgent = UserAgent::from(product: 'MyApp', version: '1.2.3');

$response = $http->send(
    request: Request::get(
        url: '/v1/charges',
        headers: Headers::from($userAgent)
    )
);
```

When the version is unknown:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\UserAgent;

$userAgent = UserAgent::from(product: 'MyApp');
# renders as: User-Agent: MyApp
```

`UserAgent` composes naturally with any other `Headerable`:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headers;
use TinyBlocks\Http\UserAgent;

$response = $http->send(
    request: Request::post(
        url: '/v1/charges',
        body: ['amount' => 1000],
        headers: Headers::from(
            UserAgent::from(product: 'MyApp', version: '1.2.3'),
            ContentType::applicationJson()
        )
    )
);
```

#### Error handling

Every failure raises an `HttpException`. `TransportFailure` (which extends `HttpException`) carries `url()`,
`method()`, and `reason()`, and is implemented by every exception raised by the transport layer. The remaining
`HttpException` implementations carry only the marker contract. Inspect their concrete class for the invariant
they violated. Catch the specific class when you need to react to a particular failure mode. Order of catch
branches matters because PHP matches the first applicable branch.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\HttpRequestInvalid;
use TinyBlocks\Http\Exceptions\TransportFailure;

try {
    $http->send(request: $request);
} catch (HttpRequestInvalid $exception) {
    # PSR-18 RequestExceptionInterface: request malformed before transport.
    echo $exception->url();
    echo $exception->method()->name;
    echo $exception->reason();
} catch (TransportFailure $exception) {
    # Other transport failures (network errors, generic PSR-18 client failures).
    echo $exception->url();
    echo $exception->method()->name;
    echo $exception->reason();
} catch (HttpException $exception) {
    # Library-level failures (configuration, malformed path, exhausted in-memory transport).
    echo $exception::class;
}
```

| Exception                     | Cause                                                                                 |
|-------------------------------|---------------------------------------------------------------------------------------|
| `HttpRequestFailed`           | Generic PSR-18 `ClientExceptionInterface`.                                            |
| `HttpNetworkFailed`           | PSR-18 `NetworkExceptionInterface` - DNS, timeout, connection refused.                |
| `HttpRequestInvalid`          | PSR-18 `RequestExceptionInterface` - request malformed before transport.              |
| `MalformedPath`               | Path attempts to escape the base URL (scheme, protocol-relative, control characters). |
| `NoMoreResponses`             | `InMemoryTransport` exhausted (programmer error).                                     |
| `HttpConfigurationInvalid`    | Builder called without required dependencies.                                         |
| `SynthesizedResponseHasNoRaw` | `Response::raw()` called on a response created via `Response::with(...)`.             |

#### Configuring timeouts

PSR-18 does not standardize timeouts. Configure them on the underlying client before injection.

**Guzzle:**

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client;

$client = new Client(config: ['timeout' => 30, 'connect_timeout' => 5]);
```

**Symfony HttpClient:**

```php
<?php

declare(strict_types=1);

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

$client = new Psr18Client(client: HttpClient::create(defaultOptions: ['timeout' => 30]));
```

#### Testing with InMemoryTransport

Pre-program responses with `Response::with(...)` and feed them to `InMemoryTransport`:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transports\InMemoryTransport;
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Http;

$transport = InMemoryTransport::with(
    responses: [
        Response::with(code: Code::CREATED, body: ['id' => 'ch_abc123']),
        Response::with(code: Code::OK, body: ['status' => 'paid'])
    ]
);

$http = Http::create()
    ->withBaseUrl(url: 'https://api.example.com')
    ->withTransport(transport: $transport)
    ->build();
```

Calls consume responses in FIFO order. Exhaustion raises `NoMoreResponses`.

#### Extending with custom transports

Implement `Transport` to add retry, logging, circuit breaker, or any other cross-cutting concern. The decorator wraps
any inner `Transport`.

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Client\Response;
use TinyBlocks\Http\Client\Transport;
use TinyBlocks\Http\Exceptions\HttpNetworkFailed;

final readonly class RetryingTransport implements Transport
{
    public function __construct(
        private Transport $inner,
        private int $maxAttempts
    ) {
    }

    public function send(Request $request): Response
    {
        $attempt = 0;

        while (true) {
            try {
                return $this->inner->send(request: $request);
            } catch (HttpNetworkFailed $exception) {
                $attempt++;

                if ($attempt >= $this->maxAttempts) {
                    throw $exception;
                }
            }
        }
    }
}
```

Compose it into the facade:

```php
<?php

declare(strict_types=1);

use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Http;

$http = Http::create()
    ->withBaseUrl(url: 'https://api.example.com')
    ->withTransport(
        transport: new RetryingTransport(
            inner: NetworkTransport::with(client: $client, factory: $factory),
            maxAttempts: 3
        )
    )
    ->build();
```

## FAQ

### 01. Why is there a `Headerable` interface and a `Headers` value object?

`Headerable` is the contract implemented by classes that emit one or more header lines such as `ContentType`, `Cookie`,
`CacheControl`, and any custom header type. `Headers` is the value object that carries the consolidated header set of an
HTTP request or response, with case-insensitive lookup and merging.

### 02. Why are timeouts not part of the public API?

PSR-18 does not standardize timeouts. Exposing them in the facade would require a transport-specific contract that leaks
the underlying client. Configure timeouts on the PSR-18 client before injecting it.

### 03. Why does `Response::raw()` throw on a synthesized response?

A response created via `Response::with(...)` has no PSR-7 backing - it exists only for in-process scenarios (tests,
`InMemoryTransport`). Calling `raw()` in that mode is a programmer error and raises `SynthesizedResponseHasNoRaw`.

### 04. Why is path validation enforced at the resolver?

To protect the configured base URL from being hijacked by paths that contain a scheme, are protocol-relative, or carry
control characters. Such inputs raise `MalformedPath` before the transport is invoked.

### 05. What happens to status codes outside the `Code` enum?

`Response::from()` requires a code present in the enum, which covers every RFC code in use. Non-RFC status codes are
reachable through `Response::raw()->getStatusCode()`.

## License

Http is licensed under [MIT](LICENSE).

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
