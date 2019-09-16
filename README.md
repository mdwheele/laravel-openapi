# OpenAPI-driven routing and validation for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/vpre/mdwheele/laravel-openapi.svg?style=flat-square)](https://packagist.org/packages/mdwheele/laravel-openapi)
![PHP from Packagist](https://img.shields.io/packagist/php-v/mdwheele/laravel-openapi)
[![Total Downloads](https://img.shields.io/packagist/dt/mdwheele/laravel-openapi.svg?style=flat-square)](https://packagist.org/packages/mdwheele/laravel-openapi)
[![CircleCI](https://circleci.com/gh/mdwheele/laravel-openapi.svg?style=svg)](https://circleci.com/gh/mdwheele/laravel-openapi)


This package allows you to create a single specification for your Laravel application that will register routes and validate all requests your API receives as well as all responses that you generate.

The [OpenAPI](https://github.com/OAI/OpenAPI-Specification) development experience in PHP feels disjoint... 

* I can update my OpenAPI specification with no impact on the actual implementation, leaving room for drift. 
* I can try and glue them together with process and custom tooling, but I feel like I'm gluing 9,001 pieces of the internet together and it's different for each project. I'd prefer if someone else to do that work.
* Documentation generators are **AMAZING**, but if there's nothing to stop implementation from drifting away from documentation, then is it worth it?
* Tooling to validate JSON Schema is great, but the error messages I get back are hard to grok for beginners and aren't always obvious.

This package aims to create a positive developer experience where you truly have a **single source of record**, your OpenAPI specification. From this, the package will automatically register routes with Laravel. Additionally, it will attach a [Middleware](https://laravel.com/docs/master/middleware) to these routes that will validate all incoming requests and outgoing responses. When the package detects a mismatch in implementation and specification, you'll get a **helpful** error message that hints at **what to do next**.


## Installation

You can install the package through Composer.

```bash
composer require mdwheele/laravel-openapi
```

Optionally, you can publish the config file of this package with this command:

```bash
php artisan vendor:public --provider="Mdwheele\OpenApi\OpenApiServiceProvider"
```

The following config file will be published in `config/openapi.php`:

```php
<?php

return [

    /*
     * The path to your OpenApi specification root document.
     */
    'path' => env('OPENAPI_PATH'),

    /*
     * Whether or not to validate response schemas. You may want to
     * enable this in development and disable in production. Do as you
     * wish!
     */
    'validate_responses' => env('OPENAPI_VALIDATE_RESPONSES', true)

];
```

## Usage

Configure `OPENAPI_PATH` to point at your top-level specification. The package will parse your OpenAPI specification to create appropriate routes and attach the `ValidateOpenApi` middleware. The middleware validates all requests coming to your API as well as all responses that you generate from your Controllers. If the middleware encounters a validation error, it will throw an `OpenApiException`, which will have a summary error message along with a bag of detailed errors describing what's wrong (as best as we can). 

It is a good idea to incorporate this into your normal exception handler like so:

```php
class Handler extends Exception Handler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        // This is only an example. You can format this however you 
        // wish. The point is that the library gives you easy access to 
        // "what went wrong" so you can react accordingly.
        if ($exception instanceof OpenApiException) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ], 400);
        }

        return parent::render($request, $exception);
    }
}
```

When you generate a response that doesn't match the OpenApi schema you've specified, you'll get something like the following:

```json
{
  "message": "The response from CoreFeaturesController@healthCheck does not match your OpenAPI specification.",
  "errors": [
    "The [status] property must be one of [ok, warning, critical].",
    "The [updates[0].timestamp] property is missing. It must be included.",
    "The property unsupported is not defined and the definition for [updates[0]] does not allow additional properties."
  ]
}
```

As a further example, check out the following API specification.

```yaml
openapi: "3.0.0"
info:
  version: 1.0.0
  title: Your Application
servers:
  - url: https://localhost/api
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
                  $ref: '#/components/schemas/Pet'
components:
  schemas:
    Pet:
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

## Caution!

:mute: **Opinion Alert** *... and feel free to take with grain of salt.*

Just as over-specifying tests can leave a bad taste in your mouth, over-specifying your API can lead you down a path of resistance and analysis paralysis. When you're using JSON Schema to specify request bodies, parameters and responses, **take care** to understand that you are specifying valid **HTTP messages**, not necessarily every business rule in your system. 

For example, I've seen many folks get stuck with "But @mdwheele! I need to have conditional responses because when X happens, I need one response. But when Y happens, I need a totally different response.". My advice on this is to write tests :grinning:. What this library does for you is allows confidence to not have to write *tons* of structural tests just to make sure everything is under a top-level `data` envelope; that `filter` is allowed as a query parameter, etc. 

Another way to think of this is the difference between "form validation" and "business invariants". There *is* an overlap many times, but the goals are different. Form validation (or OpenAPI specification validation) says "Do I have a valid HTTP message?" while business rules are more nuanced (e.g. "Is this user approved to create purchase orders totaling more than $5,000?").  

## Roadmap

- [ ] Continue to improve error messages to be as helpful as possible. In the mean time, use the package and if it's ever unclear how to respond to an error message, [send it in](https://github.com/mdwheele/laravel-openapi/issues/new) as a bug.
- [x] Add additional specification examples to guarantee we're casting a wide net to accommodate as many use-cases as possible.
- [ ] Improve framework integration error handling.  

### Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email mdwheele@gmail.com instead of using the issue tracker.

## Credits

- [Dustin Wheeler](https://github.com/mdwheele)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
