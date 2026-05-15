# Http

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    + [Server](#server)
        * [Decoding a request](#decoding-a-request)
        * [Creating a response](#creating-a-response)
        * [Setting cookies](#setting-cookies)
        * [Status code](#status-code)
    + [Client](#client)
        * [Building Http with a PSR-18 client and PSR-17 factories](#building-http-with-a-psr-18-client-and-psr-17-factories)
        * [Making a request](#making-a-request)
        * [Reading the response](#reading-the-response)
        * [Query parameters](#query-parameters)
        * [Custom headers and content type](#custom-headers-and-content-type)
        * [Setting the User-Agent](#setting-the-user-agent)
        * [Error handling](#error-handling)
        * [Configuring timeouts](#configuring-timeouts)
        * [Testing with InMemoryTransport](#testing-with-inmemorytransport)
        * [Extending with custom transports](#extending-with-custom-transports)
* [FAQ](#faq)
* [License](#license)
* [Contributing](#contributing)

## Overview

The library covers both sides of an HTTP exchange:

- **Server side** (`TinyBlocks\Http\Server`) — decodes a PSR-7 `ServerRequestInterface` into typed accessors and builds
  outgoing `ResponseInterface` instances with cookies, cache-control, and status codes.
- **Client side** (`TinyBlocks\Http\Client`) — composes outbound requests, sends them through a `Transport` port backed
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
use Psr\Http\Message\ServerRequestInterface;
use TinyBlocks\Http\Server\Request;

/** @var ServerRequestInterface $psrRequest */
$method = Request::from(request: $psrRequest)->method();
```

#### Creating a response

Each helper returns a PSR-7 `ResponseInterface` and defaults to `application/json`:

```php
use TinyBlocks\Http\Server\Response;

Response::ok(body: ['message' => 'Resource created successfully.']);
Response::created(body: ['id' => 42]);
Response::noContent();
Response::notFound(body: ['error' => 'Resource not found.']);
```

For custom status codes, use `from(...)`:

```php
use TinyBlocks\Http\Code;
use TinyBlocks\Http\Server\Response;

Response::from(body: ['status' => 'accepted'], code: Code::ACCEPTED);
```

Attach additional headers via varargs of `Headerable`:

```php
use TinyBlocks\Http\CacheControl;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\ResponseCacheDirectives;
use TinyBlocks\Http\Server\Response;

$cacheControl = CacheControl::fromResponseDirectives(
    ResponseCacheDirectives::maxAge(maxAgeInWholeSeconds: 10000)
);

Response::ok(['ok' => true], $cacheControl, ContentType::applicationJson())
    ->withHeader('X-Trace-Id', 'abc-123');
```

#### Setting cookies

`Cookie` implements `Headerable` and composes naturally with `Response`:

```php
use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\SameSite;
use TinyBlocks\Http\Server\Response;

$session = Cookie::create(name: 'session', value: $token)
    ->httpOnly()
    ->secure()
    ->withSameSite(sameSite: SameSite::STRICT)
    ->withPath(path: '/v1/sessions')
    ->withMaxAge(seconds: 604800);

Response::ok(['ok' => true], $session);
```

To expire a cookie, use `Cookie::expire(...)` with the same `Path` and `Domain` used at creation.

```php
use TinyBlocks\Http\Cookie;
use TinyBlocks\Http\SameSite;
use TinyBlocks\Http\Server\Response;

$expired = Cookie::expire(name: 'session')
    ->httpOnly()
    ->secure()
    ->withSameSite(sameSite: SameSite::STRICT)
    ->withPath(path: '/v1/sessions');

Response::noContent($expired);
```

#### Status code

The `Code` enum carries the full RFC HTTP status set with typed helpers:

```php
use TinyBlocks\Http\Code;

Code::OK->value;             // 200
Code::OK->message();         // "OK"
Code::OK->isSuccess();       // true
Code::INTERNAL_SERVER_ERROR->isError(); // true

Code::isValidCode(code: 200);   // true
Code::isErrorCode(code: 500);   // true
Code::isSuccessCode(code: 200); // true
```

### Client

#### Building Http with a PSR-18 client and PSR-17 factories

Assemble the façade with any PSR-18 client and PSR-17 factories.

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Http;

$factory = new HttpFactory();
$client = new Client(['timeout' => 30, 'connect_timeout' => 5]);

$http = Http::create()
    ->withTransport(transport: NetworkTransport::with(client: $client, factory: $factory))
    ->withBaseUrl(url: 'https://api.example.com')
    ->build();
```

For a single-call construction without the fluent builder:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use TinyBlocks\Http\Client\Transports\NetworkTransport;
use TinyBlocks\Http\Http;

$factory = new HttpFactory();

$http = Http::with(
    baseUrl: 'https://api.example.com',
    transport: NetworkTransport::with(
        client: new Client(['timeout' => 30, 'connect_timeout' => 5]),
        factory: $factory
    )
);
```

#### Making a request

`Request::create(...)` accepts only `url` as required. Everything else has sensible defaults.

```php
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

$response = $http->send(
    request: Request::create(
        url: '/v1/charges',
        body: ['amount' => 1000, 'currency' => 'usd'],
        method: Method::POST
    )
);
```

A simple `GET` needs only the URL:

```php
use TinyBlocks\Http\Client\Request;

$response = $http->send(request: Request::create(url: '/v1/charges/abc123'));
```

#### Reading the response

```php
if ($response->isSuccess()) {
    $id = $response->body()->get(key: 'id')->toString();
    $amount = $response->body()->get(key: 'amount')->toInteger();
}

$response->code();      // Code enum
$response->headers();   // TinyBlocks\Http\Headers value object
$response->raw();       // Psr\Http\Message\ResponseInterface
```

`Headers` exposes case-insensitive lookup:

```php
$contentType = $response->headers()->get(name: 'content-type'); // "application/json"
$hasTrace = $response->headers()->has(name: 'X-Trace-Id');      // true
```

#### Query parameters

Pass the query as a named parameter — the library encodes it in RFC3986 form.

```php
use TinyBlocks\Http\Client\Request;

$response = $http->send(
    request: Request::create(
        url: '/v1/charges',
        query: ['status' => 'succeeded', 'limit' => 50]
    )
);
```

#### Custom headers and content type

Any `Headerable` composes via varargs:

```php
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Headerable;
use TinyBlocks\Http\Method;

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
    request: Request::create(
        '/v1/charges',
        ['amount' => 1000],
        null,
        Method::POST,
        ContentType::applicationJson(),
        new IdempotencyKey(value: $key)
    )
);
```

Custom headers always win over the library's JSON defaults.

<div id='setting-the-user-agent'></div>

#### Setting the User-Agent

The `UserAgent` value object implements `Headerable` and renders the standard
`User-Agent` header. Empty version is normalized to "no version" — the rendered
header carries only the product token in that case, so configuration with an
optional version flows in directly.

```php
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;
use TinyBlocks\Http\UserAgent;

$userAgent = UserAgent::from(product: 'MyApp', version: '1.2.3');

$response = $http->send(
    request: Request::create('/v1/charges', null, null, Method::GET, $userAgent)
);
```

When the version is unknown:

```php
use TinyBlocks\Http\UserAgent;

$userAgent = UserAgent::from(product: 'MyApp');
// renders as: User-Agent: MyApp
```

`UserAgent` composes naturally with any other `Headerable`:

```php
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Method;
use TinyBlocks\Http\UserAgent;

$response = $http->send(
    request: Request::create(
        '/v1/charges',
        ['amount' => 1000],
        null,
        Method::POST,
        UserAgent::from(product: 'MyApp', version: '1.2.3'),
        ContentType::applicationJson()
    )
);
```

#### Error handling

Every failure raises an `HttpException`. The hierarchy is flat — each exception extends a native PHP base
(`RuntimeException` or `LogicException`) and implements `HttpException` directly. Catch the specific class when
you need to react to a particular failure mode; otherwise catch the umbrella `HttpException`.

```php
use TinyBlocks\Http\Exceptions\HttpException;
use TinyBlocks\Http\Exceptions\HttpNetworkFailed;
use TinyBlocks\Http\Exceptions\MalformedPath;

try {
    $response = $http->send(request: $request);
} catch (HttpNetworkFailed $exception) {
    // DNS, connection refused, timeout — retry candidate
} catch (MalformedPath $exception) {
    // path contained a scheme, was protocol-relative, or held control chars
} catch (HttpException $exception) {
    // any other library-thrown failure
    $exception->url();
    $exception->method();
    $exception->reason();
}
```

| Exception                     | Cause                                                                                 |
|-------------------------------|---------------------------------------------------------------------------------------|
| `HttpRequestFailed`           | Generic PSR-18 `ClientExceptionInterface`.                                            |
| `HttpNetworkFailed`           | PSR-18 `NetworkExceptionInterface` — DNS, timeout, connection refused.                |
| `HttpRequestInvalid`          | PSR-18 `RequestExceptionInterface` — request malformed before transport.              |
| `MalformedPath`               | Path attempts to escape the base URL (scheme, protocol-relative, control characters). |
| `NoMoreResponses`             | `InMemoryTransport` exhausted (programmer error).                                     |
| `HttpConfigurationInvalid`    | Builder called without required dependencies.                                         |
| `SynthesizedResponseHasNoRaw` | `Response::raw()` called on a response created via `Response::with(...)`.             |

#### Configuring timeouts

PSR-18 does not standardize timeouts. Configure them on the underlying client before injection.

**Guzzle:**

```php
use GuzzleHttp\Client;

$client = new Client(['timeout' => 30, 'connect_timeout' => 5]);
```

**Symfony HttpClient:**

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

$client = new Psr18Client(client: HttpClient::create(['timeout' => 30]));
```

#### Testing with InMemoryTransport

Pre-program responses with `Response::with(...)` and feed them to `InMemoryTransport`:

```php
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
    ->withTransport(transport: $transport)
    ->withBaseUrl(url: 'https://api.example.com')
    ->build();
```

Calls consume responses in FIFO order. Exhaustion raises `NoMoreResponses`.

#### Extending with custom transports

Implement `Transport` to add retry, logging, circuit breaker, or any other cross-cutting concern. The decorator wraps
any inner `Transport`.

```php
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

Compose it into the façade:

```php
$http = Http::create()
    ->withTransport(
        transport: new RetryingTransport(
            inner: NetworkTransport::with(client: $client, factory: $factory),
            maxAttempts: 3
        )
    )
    ->withBaseUrl(url: 'https://api.example.com')
    ->build();
```

## FAQ

### 01. Why is there a `Headerable` interface and a `Headers` value object?

`Headerable` is the contract implemented by classes that emit one or more header lines — `ContentType`, `Cookie`,
`CacheControl`, and any custom header type. `Headers` is the value object that carries the consolidated header set of an
HTTP request or response, with case-insensitive lookup and merging.

### 02. Why are timeouts not part of the public API?

PSR-18 does not standardize timeouts. Exposing them in the façade would require a transport-specific contract that leaks
the underlying client. Configure timeouts on the PSR-18 client before injecting it.

### 03. Why does `Response::raw()` throw on a synthesized response?

A response created via `Response::with(...)` has no PSR-7 backing — it exists only for in-process scenarios (tests,
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
