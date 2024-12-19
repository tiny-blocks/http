# Http

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    * [Using the status code](#status_code)
    * [Creating a response](#response)
* [License](#license)
* [Contributing](#contributing)

<div id='overview'></div> 

## Overview

Common implementations for HTTP protocol. The library exposes concrete implementations that follow the PSR standards,
specifically designed to operate with [PSR-7](https://www.php-fig.org/psr/psr-7)
and [PSR-15](https://www.php-fig.org/psr/psr-15), providing solutions for building HTTP responses, requests, and other
HTTP-related components.

<div id='installation'></div>

## Installation

```bash
composer require tiny-blocks/http
```

<div id='how-to-use'></div>

## How to use

The library exposes interfaces like `Headers` and concrete implementations like `Response`, `ContentType`, and others,
which facilitate construction.

<div id='status_code'></div>

### Using the status code

The library exposes a concrete implementation through the `Code` enum. You can retrieve the status codes, their
corresponding messages, and check for various status code ranges using the methods provided.

- **Get message**: Returns the [HTTP status message](https://developer.mozilla.org/en-US/docs/Web/HTTP/Messages)
  associated with the enum's code.

  ```php
  use TinyBlocks\Http\Code;
  
  Code::OK->message();                    # 200 OK
  Code::IM_A_TEAPOT->message();           # 418 I'm a teapot
  Code::INTERNAL_SERVER_ERROR->message(); # 500 Internal Server Error
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

<div id='response'></div>

### Creating a response

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

<div id='license'></div>

## License

Http is licensed under [MIT](LICENSE).

<div id='contributing'></div>

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
