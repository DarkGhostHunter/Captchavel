![Franck V. - Unsplash (UL) #JjGXjESMxOY](https://images.unsplash.com/photo-1535378620166-273708d44e4c?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=600&q=80)

# Captchavel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/captchavel.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/captchavel)
[![Build Status](https://img.shields.io/travis/darkghosthunter/captchavel/master.svg?style=flat-square)](https://travis-ci.org/darkghosthunter/captchavel)
[![Quality Score](https://img.shields.io/scrutinizer/g/darkghosthunter/captchavel.svg?style=flat-square)](https://scrutinizer-ci.com/g/darkghosthunter/captchavel)
[![Total Downloads](https://img.shields.io/packagist/dt/darkghosthunter/captchavel.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/captchavel)

Easily integrate Google Recaptcha v3 into your Laravel application. 

## Installation

You can install the package via composer:

```bash
composer require darkghosthunter/captchavel
```

## Usage

The first thing you need is to add the `CAPTCHAVEL_KEY` and `CAPTCHAVEL_SECRET` environment variables in your `.env` file for your reCAPTCHA Site Key and Secret Key, respectively. If you don't have them, you should get into your [Google reCAPTCHA Admin console](https://g.co/recaptcha/admin) and add it for your application.

```dotenv
CAPTCHAVEL_KEY=JmXJEeOqjHkr9LXEzgjuKsAhV84RH--DvRJo5mXl
CAPTCHAVEL_SECRET=JmXJEeOqjHkr9WjDR4rjuON1MGxqCxdOA4zDTH0w
```

Captchavel by default works on `auto` mode, allowing you minimal configuration in the backend and frontend. Let's start with the latter.

### Frontend

Just add the `data-recaptcha="true"` attribute to the forms where you want to have the reCAPTCHA check.

```blade
<form action="/login" method="post" data-recaptcha="true">
    @csrf
    <input type="text" class="form-control" name="username" placeholder="Username">
    <input type="password" class="form-control" name="password" placeholder="Password">
    <button type="submit" class="btn btn-primary">Log in</button>
</form>
``` 

The Google reCAPTCHA script will be automatically injected on all responses for better analytics.

> Check the `manual` mode if you want finer control on how to deal with the frontend reCAPTCHA scripts. 

### Backend

Then, you should add the `recaptcha` middleware inside your controllers that will receive input and you want to protect with the reCAPTCHA check.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomController extends Controller
{
    /**
     * Create a new CustomController instance
     *  
     * @return void 
     */
    public function __construct()
    {
        $this->middleware('recaptcha')->only('form');
    }
    
    /**
     * Receive the HTTP POST Request
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function form(Request $request)
    {
        $request->validate([
            'username' => 'required|string|exists:users,username'
        ]);
        
        // ...
        
        return response()->view('web.user.success');
        
    }
    
    // ...
}
```

Since it's a middleware, you can alternatively set it inside your route declaration: 

```php
<?php

use Illuminate\Support\Facades\Route;

Route::post('form')->uses('CustomController@form')->middleware('recaptcha');
```

> The `recaptcha` middleware only works on `POST/PUT/PATCH/DELETE` methods, so don't worry if you use it in a `GET` method you will receive a nice `InvalidMethodException` so you can use it correctly.

### Accessing the reCAPTCHA response

You can access the reCAPTCHA response using the `ReCaptcha` facade, which will return the reCAPTCHA Response from the servers, with useful helpers so you don't have to dig in the raw response:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DarkGhostHunter\Captchavel\ReCaptcha;

class CustomController extends Controller
{
    /**
     * Create a new CustomController instance
     *  
     * @return void 
     */
    public function __construct()
    {
        $this->middleware('recaptcha')->only('form');
    }
    
    /**
     * Receive the HTTP POST Request
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function form(Request $request)
    {
        $request->validate([
            'username' => 'required|string|exists:users,username'
        ]);
        
        if (ReCaptcha::isRobot()) {
            return response()->view('web.user.you-are-a-robot');
        }
        
        return response()->view('web.user.success');
    }
    
    // ...
}
```

* `isResolved()`: If the reCAPTCHA check was made in the current Request.
* `isHuman()`: Detects if the Request has been made by a Human (equal or above threshold).
* `isRobot()`: Detects if the Request has been made by a Robot (below threshold).
* `since()`: Returns the time the reCAPTCHA challenge was resolved as a Carbon timestamp.

## On Production Environments

The package won't be enabled unless your site is on `production` environment. If you want to enable this on `local` environment to test locally, you can set the `CAPTCHAVEL_LOCAL=true`.

## Configuration

For finner configuration, publish the configuration file for Captchavel:

```bash
php artisan vendor:publish --provider="DarkGhostHunter\Captchavel\CaptchavelServiceProvider"
```

You will get a config file with this array:

```php
<?php

return [
    'mode' => env('CAPTCHAVEL_MODE', 'auto'),
    'enable_local' => env('CAPTCHAVEL_LOCAL', false),
    'key' => env('CAPTCHAVEL_KEY'),
    'secret' => env('CAPTCHAVEL_SECRET'),
    'return_on_robot' => false,
    'threshold' => 0.5,
    'request_method' => null,
];
```

### Mode

Captchavel works painlessly once installed. You can modify the behaviour with just changing the `CAPTCHAVEL_MODE` to `auto` or `manual`.

```dotenv
CAPTCHAVEL_MODE=auto
```

#### `auto`

The `auto` option leverages the frontend work to you. Just add the `data-recaptcha="true"` attribute to the forms where you want to check for reCAPTCHA.

```blade
<form action="/login" method="post" data-recaptcha="true">
    @csrf
    <input type="text" class="form-control" name="username" placeholder="Username">
    <input type="password" class="form-control" name="password" placeholder="Password">
    <button type="submit" class="btn btn-primary">Log in</button>
</form>
```

Captchavel will inject the Google reCAPTCHA v3 as a deferred script in the head before `<head>` tag, in every response (except JSON, AJAX or anything non-HTML), so it can have more analytics about how users interact with your site.

#### `manual`

This will disable the global middleware that injects the Google reCAPTCHA script in your frontend. You have total liberty on how to include the reCAPTCHA script and how to enable the reCAPTCHA check in the frontend. 

Since Captchavel won't include anything in your views, you should check out the [Google reCAPTCHA documentation](https://developers.google.com/recaptcha/docs/v3) on how to implement it yourself.

### Enable on Local Environment

By default, this package is transparent on `local` environment, so you can develop without having to fill a reCAPTCHA box anywhere.

For troubleshooting, you can forcefully enable Captchavel setting `enable_local` to `true`, or better, using your environment `.env` file and setting `CAPTCHAVEL_LOCAL` to `true`.  

### Key and Secret

There parameters are self-explanatory. One is the reCAPTCHA Site Key, which is shown publicly in your views, and the Secret, which is used to recover the user interaction information privately inside your application.

If you don't have them, use the [Google reCAPTCHA Admin console](https://g.co/recaptcha/admin) to create a pair. 

### Return on Robot

This is basically a "kill switch" for Requests being made by robots. When the reCAPTCHA is being made by a robot (score below threshold) it will return the request back to where it originated, additionally with the old input. 

You can also override this at middleware-level using a third parameter:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::post('form')->uses('CustomController@form')->middleware('recaptcha:null,true');
```

### Threshold

Google reCAPTCHA v3 returns a *score* for interactions. Lower scores means the Request has been probably made by a robot, while high scores mean a more human-like interaction.

By default, this package uses a score of 0.5, which is considered *sane* in most of cases, but you can override it using the`CAPTCHAVEL_THRESHOLD` key with values between 0.1 and 1.0.
 
```dotenv
CAPTCHAVEL_THRESHOLD=0.7
```

Aside from that, you can also override the score using a parameter within the `recaptha` middleware, which will take precedence over the default score (set or not). For example, you can set it lower for Logins, but higher for Password Resets, User Reviews or Comments.

```php
<?php

use Illuminate\Support\Facades\Route;

Route::post('form')->uses('CustomController@form')->middleware('recaptcha:0.8');
```

> Issuing `null` as first parameter will make the middleware to use the default threshold. 

### Request Method

The Google reCAPTCHA library underneath has flexibility in about how to make the request to the reCAPTCHA servers.

The `request_method` accepts the Class you want to instance. You should register it using the Service Container [Contextual Binding](https://laravel.com/docs/container#contextual-binding). 

The default `null` value is enough for any normal application, but you're free to, for example, create your own logic or use the classes included in the [ReCaptcha package](https://github.com/google/recaptcha/tree/master/src/ReCaptcha/RequestMethod) (that this package requires). You can mimic this next example, were we will use Guzzle.

#### Example implementation

First, we will create our `GuzzleRequestMethod` with the `submit()` method as required. This method will return the reCAPTCHA response from the external server using Guzzle.

`app\Http\ReCaptcha\GuzzleRequestMethod.php`
```php
<?php
namespace App\Http\ReCaptcha;

use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

class GuzzleRequestMethod implements RequestMethod
{
    // ...
    
    /**
     * Submit the request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params) 
    {
        return (new \GuzzleHttp\Client)
            ->post($params->toQueryString())
            ->getBody();
    }
}
```

Then, we will add the class to the `request_method` key in our configuration

`config/captchavel.php`
```php
<?php

return [
    
    // ...
    
    'request_method' => 'App\Http\ReCaptcha\GuzzleRequestMethod',
];
```

And finally, we will tell the Service Container to give our `GuzzleRequestMethod` to the underneath `ReCaptcha` class when Captchavel tries to instance it.

`app\Providers\AppServiceProvider.php`
```php
<?php
namespace App\Providers;

use App\Http\ReCaptcha\GuzzleRequestMethod;
use GuzzleHttp\Client;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // ...

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(ReCaptcha::class)
            ->needs(RequestMethod::class)
            ->give(function ($app) {
                return new GuzzleRequestMethod;
            });
    }
}
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

## Credits

- [Italo Baeza Cabrera](https://github.com/darkghosthunter)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.