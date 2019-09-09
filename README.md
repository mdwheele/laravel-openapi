# OpenAPI-driven routing and validation for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mdwheele/laravel-openapi.svg?style=flat-square)](https://packagist.org/packages/mdwheele/laravel-openapi)
[![CircleCI](https://circleci.com/gh/mdwheele/laravel-openapi.svg?style=svg)](https://circleci.com/gh/mdwheele/laravel-openapi)
[![Total Downloads](https://img.shields.io/packagist/dt/mdwheele/laravel-openapi.svg?style=flat-square)](https://packagist.org/packages/mdwheele/laravel-openapi)


This package allows you to create a single specification for your Laravel application that will register routes and validate all requests your API receives as well as all responses that you generate.

The [OpenAPI](https://github.com/OAI/OpenAPI-Specification) development experience in PHP feels disjoint... 

* I can update my OpenAPI specification with no impact on the actual implementation, leaving room for drift. 
* I can try and glue them together with process and custom tooling, but I feel like I'm gluing 40,000 pieces of the internet together and it's different for each project
* Documentation generators are **AMAZING**, but if there's nothing to stop implementation from drifting away from documentation, then is it worth it?
* Tooling to validate JSON Schema is great, but the error messages I get back are hard to grok for beginners and aren't always obvious.

This package aims to create a positive developer experience where you truly have a **single source of record**, your OpenAPI specification. From this, the package will automatically register routes with Laravel. Additionally, it will attach a [Middleware](https://laravel.com/docs/master/middleware) to these routes that will validate all incoming requests and outgoing responses. When the package detects a mismatch in implementation and specification, you'll get a **helpful** error message that hints at **what to do next**.

To show you what we mean, when you install the package, create a basic OpenAPI specification like below:

```yaml
openapi: "3.0.0"
info:
  version: 1.0.0
  title: Your Application
servers:
  - url: https://{host}/{basePath}
    variables:
      host:
        default: localhost
      basePath:
        default: api
paths:
  /pets:
    get:
      summary: List all pets
      operationId: App\Http\Controllers\PetsController@index
      responses:
        '200':
          description: An array of Pets.
          content:
            application/json:
              schema:
                type: array
                items: 
                  type: object
                  required:
                    - id
                    - name
                  properties:
                    id:
                      type: integer
                      format: int64
                    name:
                      type: string
```

This specification says that there will be an endpoint at `https://localhost/api/pets` that can receive a `GET` request and will only return responses with a `200` status code. Those successful responses will return `application/json` that contains an `array` of JavaScript objects that **MUST** have both an `id` (that is an `integer`) and a `name` (that can be any string). 

Any of the following circumstances will trigger an `OpenApiException` that will include more information on what's needed in order to resolve the mismatch between your implementation and the OpenAPI specification you've designed:

- If you return `403` response from `/api/pets`, you'll get an exception that explains that there is no specified response for `403` and there is no `default` handler.
- If you return anything other than `application/json`, you'll get a similar exception explaining the acceptable media types that can be returned.
- If you return JavaScript objects that use a `string`-based `id` (e.g. `id: 'foo'`), you'll be told that the response your controller generated does not match the specified JSON Schema. Additionally, you'll be given some pointers as to what, specifically, was wrong and some hints on how to resolve.


## Installation

You can install the package via composer:

```bash
composer require mdwheele/laravel-openapi
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email mdwheele@gmail.com instead of using the issue tracker.

## Credits

- [Dustin Wheeler](https://github.com/mdwheele)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
