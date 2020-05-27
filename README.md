![Franck V. - Unsplash (UL) #JjGXjESMxOY](https://images.unsplash.com/photo-1535378620166-273708d44e4c?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/captchavel.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/captchavel) [![License](https://poser.pugx.org/darkghosthunter/captchavel/license)](https://packagist.org/packages/darkghosthunter/larapoke)
![](https://img.shields.io/packagist/php-v/darkghosthunter/captchavel.svg)
 ![](https://github.com/DarkGhostHunter/Captchavel/workflows/PHP%20Composer/badge.svg) [![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Captchavel/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Captchavel?branch=master) [![Maintainability](https://api.codeclimate.com/v1/badges/9571f57106069b5f3aac/maintainability)](https://codeclimate.com/github/DarkGhostHunter/Captchavel/maintainability)

# Captchavel

Easily integrate Google reCAPTCHA v3 into your Laravel application.

> This is totally compatible with reCAPTCHA v2, so you can use both. Check [this GitHub comment](https://github.com/google/recaptcha/issues/279#issuecomment-445529732) about the caveats.

## Requirements

* Laravel 6 or Laravel 7

## Installation

You can install the package via composer:

```bash
composer require darkghosthunter/captchavel
```

## Usage

The first thing you need is to add the `RECAPTCHA_V3_KEY` and `RECAPTCHA_V3_SECRET` environment variables in your `.env` file with your reCAPTCHA Site Key and Secret Key, respectively. If you don't have them, you should go to your [Google reCAPTCHA Admin console](https://g.co/recaptcha/admin) and create them for your application.

```dotenv
RECAPTCHA_V3_KEY=JmXJEeOqjHkr9LXEzgjuKsAhV84RH--DvRJo5mXl
RECAPTCHA_V3_SECRET=JmXJEeOqjHkr9WjDR4rjuON1MGxqCxdOA4zDTH0w
```

Captchavel by default works on `auto` mode, allowing you minimal configuration in the backend and frontend. Let's start with the latter.

### Frontend

Just add the `data-recaptcha="true"` attribute to the forms where you want to have the reCAPTCHA check. A JavaScript will be injected in all your responses that will detect these forms an add a reCAPTCHA token to them so they can be checked in the backend. 

```blade
<form action="/login" method="post" data-recaptcha="true">
    @csrf
    <input type="text" class="form-control" name="username" placeholder="Username">
    <input type="password" class="form-control" name="password" placeholder="Password">
    <button type="submit" class="btn btn-primary">Log in</button>
</form>
``` 

The Google reCAPTCHA script from Google will be automatically injected on all responses for better analytics.

> Alternatively, you may want to use the [`manual` mode](#manual) if you want control on how to deal with the frontend reCAPTCHA script, or use a [personalized one](#editing-the-script-view).

#### Form submission prevented 

Form submission is disabled by default until the token from reCAPTCHA is retrieved. If you want to disable this behaviour, append `data-recaptcha-dont-prevent` to the form:

```blade
<!-- This form will be submittable even if the token hasn't been retrieved yet --> 
<form action="/login" method="post" data-recaptcha="true" data-recaptcha-dont-prevent="true">
    <!-- ... -->
</form>
```

#### Token resolved helper

When the reCAPTCHA token is being retrieved for the form, the form will have the property `recaptcha_unresolved` set to `true`. You can use this property for your other scripts to conditionally allow submission or whatever.

```javascript
if (form.recaptcha_unresolved) {
    alert('Wait until reCAPTCHA sends the token!');
} else {
    form.submit();
}
```

### Backend

After that, you should add the `recaptcha` middleware inside your controllers that will receive input and you want to *protect* with the reCAPTCHA check.

You can use the `isHuman()` and `isRobot()` methods in the Request instance to check if the request was made by a human or a robot, respectively.

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
        
        if ($request->isRobot()) {
            return response()->view('web.user.pending_approval');
        }
        
        return response()->view('web.user.success');
        
    }
}
```

Since it's a middleware, you can alternatively set it inside your route declaration: 

```php
<?php

use Illuminate\Support\Facades\Route;

Route::post('form')->uses('CustomController@form')->middleware('recaptcha');
```

> The `recaptcha` middleware only works on `POST/PUT/PATCH/DELETE` methods, so don't worry if you use it in a `GET` method. You will receive a nice `InvalidMethodException` so you can use it correctly.

### Accessing the reCAPTCHA response

You can access the reCAPTCHA response in four ways:

* using [dependency injection](https://laravel.com/docs/container#automatic-injection), 
* using the `ReCaptcha` facade anywhere in your code, 
* the `recaptcha()` helper, 
* and resolving it from the Service Container with `app('recaptha')`. 

These methods will return the reCAPTCHA Response from the servers, with useful helpers so you don't have to dig in the raw response:

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
     * @param \DarkGhostHunter\Captchavel\ReCaptcha $reCaptcha
     * @return \Illuminate\Http\Response
     * @throws \DarkGhostHunter\Captchavel\Exceptions\RecaptchaNotResolvedException
     */
    public function form(Request $request, ReCaptcha $reCaptcha)
    {
        $request->validate([
            'username' => 'required|string|exists:users,username'
        ]);
        
        if ($reCaptcha->isRobot()) {
            return response()->view('web.user.you-are-a-robot');
        }
        
        return response()->view('web.user.success');
    }
    
    // ...
}
```

The class has handy methods you can use to check the status of the reCAPTCHA information:

* `isResolved()`: Returns if the reCAPTCHA check was made in the current Request
* `isHuman()`: Detects if the Request has been made by a Human (equal or above threshold).
* `isRobot()`: Detects if the Request has been made by a Robot (below threshold).
* `since()`: Returns the time the reCAPTCHA challenge was resolved as a Carbon timestamp.

> If you try to check if the response while the reCAPTCHA wasn't resolved, you will get a `RecaptchaNotResolvedException`.

## Local development and robot requests 

When developing, this package registers a transparent middleware that allows you to work on your application without contacting reCAPTCHA servers ever. Instead, it will always generate a successful "dummy" response with a `1.0` score.

You can override the score to an absolute `0.0` in two ways:

* appending the `is_robot` key to the Request query,

```http request
POST http://myapp.com/login?is_robot
```

* or adding a checkbox with the name `is_robot` checked.

```html
<form action="http://myapp.com/login" method="post" data-recaptcha="true">
    <!-- ... -->
    
    <input id="is_robot" type="checkbox" name="is_robot" checked>
    <label for="is_robot">Filled by a robot</label>
    
    <button type="submit">Login</button>
</form>
```

If you want to connect to the reCAPTCHA servers on `local` environment, you can set the `CAPTCHAVEL_LOCAL=true` in your `.env` file.

> The transparent middleware also registers itself on testing environment, so you can test your application using requests made by a robot and made by a human just adding an empty `_recaptcha` input. Sweet!

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
    'key' => env('RECAPTCHA_V3_KEY'),
    'secret' => env('RECAPTCHA_V3_SECRET'),
    'threshold' => 0.5,
    'request_method' => null,
];
```

### Mode

Captchavel works painlessly once installed. You can modify the behaviour with just changing the `CAPTCHAVEL_MODE` to `auto` or `manual`, since the config file just picks up the environment file values.

```dotenv
CAPTCHAVEL_MODE=auto
```

#### `auto`

The `auto` option leverages the frontend work from you. Just add the `data-recaptcha="true"` attribute to the forms where you want to check for reCAPTCHA.

```blade
<form action="/login" method="post" data-recaptcha="true">
    @csrf
    <input type="text" class="form-control" name="username" placeholder="Username">
    <input type="password" class="form-control" name="password" placeholder="Password">
    <button type="submit" class="btn btn-primary">Log in</button>
</form>
```

Captchavel will inject the Google reCAPTCHA v3 as a deferred script before `<head>` tag, in every response (except JSON, AJAX or anything non-HTML), so it can have more analytics about how users interact with your site.

To override the script that gets injected, take a look in the [editing the script view](#editing-the-script-view) section.

#### `manual`

This will disable the global middleware that injects the Google reCAPTCHA script in your frontend. You should check out the [Google reCAPTCHA documentation](https://developers.google.com/recaptcha/docs/v3) on how to implement it yourself.

Since the frontend won't have nothing injected, this mode it gives you freedom to:

* manually include the `recaptcha-inject` middleware only in the routes you want,
* or include the `recaptcha::script` blade template in your layouts you want. 

> The manual mode is very handy if your responses have a lot of data and want better performance, because the middleware won't look into the responses.

### Enable on Local Environment

By default, this package is transparent on `local`  and `testing` environments, so you can develop without requiring to use reCAPTCHA anywhere.

For troubleshooting, you can forcefully enable Captchavel setting `enable_local` to `true`, or better, using your environment `.env` file and setting `CAPTCHAVEL_LOCAL` to `true`.

```php
<?php

return [
    'enable_local' => env('CAPTCHAVEL_LOCAL', false),
];
```

### Key and Secret

These parameters are self-explanatory. One is the reCAPTCHA Site Key, which is shown publicly in your views, and the Secret, which is used to recover the user interaction information privately inside your application.

If you don't have them, use the [Google reCAPTCHA Admin console](https://g.co/recaptcha/admin) to create a pair. 

### Threshold

Google reCAPTCHA v3 returns a *score* for interactions. Lower scores means the Request has been probably made by a robot, while high scores mean a more human-like interaction.

By default, this package uses a score of 0.5, which is considered *sane* in most of cases, but you can override it using the`CAPTCHAVEL_THRESHOLD` key with float values between 0.1 and 1.0.
 
```dotenv
CAPTCHAVEL_THRESHOLD=0.7
```

Aside from that, you can also override the score using a parameter within the `recaptha` middleware, which will take precedence over the default score (set or not). For example, you can set it lower for comments, but higher for product reviews.

```php
<?php

use Illuminate\Support\Facades\Route;

Route::post('{product}/comments')
    ->uses('Product/CommentController@create')
    ->middleware('recaptcha:0.3');

Route::post('{product}/review')
    ->uses('Product/ReviewController@create')
    ->middleware('recaptcha:0.8');
```

### Request Method

The Google reCAPTCHA library underneath allows to make the request to the reCAPTCHA servers using a custom "Request Method". The `request_method` key accepts the Class you want to instance.

The default `null` value is enough for any normal application, but you're free to, for example, create your own logic or use the classes included in the [ReCaptcha package](https://github.com/google/recaptcha/tree/master/src/ReCaptcha/RequestMethod) (that this package requires). 

```php
<?php

return [
    
    // ...
    
    'request_method' => 'App\Http\ReCaptcha\GuzzleRequestMethod',
];
```

You can mimic this next example were we will use Guzzle.

#### Example implementation

First, we will create our `GuzzleRequestMethod` with the `submit()` method as required. This method will return the reCAPTCHA response from the external server using the Guzzle Client.

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
     * @param RequestParameters $params     Request parameters
     * @return string                       Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params) 
    {
        return (new \GuzzleHttp\Client)->post($params->toQueryString())
            ->getBody()
            ->getContents();
    }
}
```

Then, we will add the class to the `request_method` key in our configuration:

`config/captchavel.php`
```php
<?php

return [
    
    // ...
    
    'request_method' => 'App\Http\ReCaptcha\GuzzleRequestMethod',
];
```

Finally, we will tell the Service Container to give our `GuzzleRequestMethod` to the underneath `ReCaptcha` class when Captchavel tries to instance it, using the Service Container [Contextual Binding](https://laravel.com/docs/container#contextual-binding).

`app\Providers\AppServiceProvider.php`
```php
<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\ReCaptcha\GuzzleRequestMethod;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;

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
        // ...
        
        // Tell the Service Container to pass our custom Request Method to the ReCaptcha client 
        $this->app->when(ReCaptcha::class)
            ->needs(RequestMethod::class)
            ->give(function () {
                return new GuzzleRequestMethod;
            });
    }
}
```

We're leaving the Contextual Binding to you, since your *requester* may need some logic that a simple `app()->make(MyRequester::class)` may not be sufficient.

### Editing the Script view

You can edit the script Blade view under by just creating a Blade template in `resources/vendor/captchavel/script.blade.php`.

This blade view contains the reCAPTCHA script of the package. The view receives the `$key` variable witch is just the reCAPTCHA v3 Site Key. 

There you can edit how the script is downloaded from Google, and how it checks for forms to link with the backend, if the default script isn't enough for you. 

### AJAX Requests

Depending on the application, AJAX Requests won't include the reCAPTCHA token. This may be for various reasons:

* Using virtual DOM frameworks like Vue and React.
* Creating a form after the page loaded with JavaScript.
* An AJAX Requests being done entirely in JavaScript.

In any of these scenarios, you may want disable the injection script and [use the reCAPATCHA v3 scripts directly](https://developers.google.com/recaptcha/docs/v3) or your [custom script](#editing-the-script-view).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
