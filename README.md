# Http

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    * [Request](#request)
    * [Response](#response)
* [License](#license)
* [Contributing](#contributing)

<div id='overview'></div> 

## Overview

Common implementations for the HTTP protocol. The library exposes concrete implementations that follow the PSR standards
and are **framework-agnostic**, designed to work consistently across any ecosystem that supports
[PSR-7](https://www.php-fig.org/psr/psr-7) and [PSR-15](https://www.php-fig.org/psr/psr-15), providing solutions for
building HTTP responses, requests, and other HTTP-related components.

<div id='installation'></div>

## Installation

```bash
composer require tiny-blocks/http
```

<div id='how-to-use'></div>

## How to use

The library exposes interfaces like `Headers` and concrete implementations like `Request`, `Response`, `ContentType`,
and others, which facilitate construction.

<div id='request'></div>

### Request

#### Decoding a request

The library provides a small public API to decode a PSR-7 `ServerRequestInterface` into a typed structure, allowing you
to access route parameters and JSON body fields consistently.

- **Decode a request**: Use `Request::from(...)` to wrap the PSR-7 request and call `decode()`. The decoded object
  exposes `uri` and `body`.

  ```php
  use Psr\Http\Message\ServerRequestInterface;
  use TinyBlocks\Http\Request;

  /** @var ServerRequestInterface $psrRequest */
  $decoded = Request::from(request: $psrRequest)->decode();

  $name = $decoded->body->get(key: 'name')->toString();
  $payload = $decoded->body->toArray();

  $id = $decoded->uri->route()->get(key: 'id')->toInteger();
  ```

- **Typed access with defaults**: Each value is returned as an Attribute, which provides safe conversions and default
  values when the underlying value is missing or not compatible.

  ```php
  use TinyBlocks\Http\Request;
  
  $decoded = Request::from(request: $psrRequest)->decode();
  
  $id = $decoded->uri->route()->get(key: 'id')->toInteger();  # default: 0
  $note = $decoded->body->get(key: 'note')->toString();       # default: ""
  $tags = $decoded->body->get(key: 'tags')->toArray();        # default: []
  $price = $decoded->body->get(key: 'price')->toFloat();      # default: 0.00
  $active = $decoded->body->get(key: 'active')->toBoolean();  # default: false
  ```

- **Custom route attribute name**: If your framework stores route params in a different request attribute, you can
  specify it via route().

  ```php
  use TinyBlocks\Http\Request;
  
  $decoded = Request::from(request: $psrRequest)->decode();

  $id = $decoded->uri->route(name: '_route_params')->get(key: 'id')->toInteger();
  ```

<div id='response'></div>

### Response

#### Creating a response

The library provides an easy and flexible way to create HTTP responses, allowing you to specify the status code,
headers, and body. You can use the `Response` class to generate responses, and the result will always be a
`ResponseInterface` from the PSR, ensuring compatibility with any framework that adheres
to the [PSR-7](https://www.php-fig.org/psr/psr-7) standard.

- **Creating a response with a body**: To create an HTTP response, you can pass any type of data as the body.
  Optionally, you can also specify one or more headers. If no headers are provided, the response will default to
  `application/json` content type.

  ```php
  use TinyBlocks\Http\Response;
    
  Response::ok(body: ['message' => 'Resource created successfully.']);
  ```

- **Creating a response with a body and custom headers**: You can also add custom headers to the response. For instance,
  if you want to specify a custom content type or any other header, you can pass the headers as additional arguments.

  ```php
  use TinyBlocks\Http\Response;
  use TinyBlocks\Http\ContentType;
  use TinyBlocks\Http\CacheControl;
  use TinyBlocks\Http\ResponseCacheDirectives;
    
  $body = 'This is a plain text response';
  
  $contentType = ContentType::textPlain();
  
  $cacheControl = CacheControl::fromResponseDirectives(
      maxAge: ResponseCacheDirectives::maxAge(maxAgeInWholeSeconds: 10000),
      staleIfError: ResponseCacheDirectives::staleIfError()
  );
  
  Response::ok($body, $contentType, $cacheControl)
      ->withHeader(name: 'X-ID', value: 100)
      ->withHeader(name: 'X-NAME', value: 'Xpto');
  ```

#### Using the status code

The library exposes a concrete implementation through the `Code` enum. You can retrieve the status codes, their
corresponding messages, and check for various status code ranges using the methods provided.

- **Get message**: Returns the [HTTP status message](https://developer.mozilla.org/en-US/docs/Web/HTTP/Messages)
  associated with the enum's code.

  ```php
  use TinyBlocks\Http\Code;
  
  Code::OK->value;                        # 200
  Code::OK->message();                    # OK
  Code::IM_A_TEAPOT->message();           # I'm a teapot
  Code::INTERNAL_SERVER_ERROR->message(); # Internal Server Error
  ```

- **Check if the code is valid**: Determines if the given code is a valid HTTP status code represented by the enum.

  ```php
  use TinyBlocks\Http\Code;
  
  Code::isValidCode(code: 200); # true
  Code::isValidCode(code: 999); # false
  ```

- **Check if the code is an error**: Determines if the given code is in the error range (**4xx** or **5xx**).

  ```php
  use TinyBlocks\Http\Code;
  
  Code::isErrorCode(code: 500); # true
  Code::isErrorCode(code: 200); # false
  ```

- **Check if the code is a success**: Determines if the given code is in the success range (**2xx**).

  ```php
  use TinyBlocks\Http\Code;
  
  Code::isSuccessCode(code: 500); # false
  Code::isSuccessCode(code: 200); # true
  ```

<div id='license'></div>

## License

Http is licensed under [MIT](LICENSE).

<div id='contributing'></div>

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
