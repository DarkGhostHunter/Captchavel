![Franck V. - Unsplash (UL) #JjGXjESMxOY](https://images.unsplash.com/photo-1535378620166-273708d44e4c?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/captchavel.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/captchavel) [![License](https://poser.pugx.org/darkghosthunter/captchavel/license)](https://packagist.org/packages/darkghosthunter/larapoke)
![](https://img.shields.io/packagist/php-v/darkghosthunter/captchavel.svg)
 ![](https://github.com/DarkGhostHunter/Captchavel/workflows/PHP%20Composer/badge.svg) [![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Captchavel/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Captchavel?branch=master) [![Maintainability](https://api.codeclimate.com/v1/badges/9571f57106069b5f3aac/maintainability)](https://codeclimate.com/github/DarkGhostHunter/Captchavel/maintainability)

# Captchavel

Integrate reCAPTCHA into your Laravel app better than the Big G itself!

It uses your Laravel HTTP Client and **HTTP/2**, making your app **fast**. You only need a couple of lines to integrate.

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Set Up](#set-up)
* [Usage](#usage)
  - [Checkbox, Invisible and Android challenges](#checkbox-invisible-and-android-challenges)
  - [Score driven interaction](#score-driven-interaction)
* [Frontend integration](#frontend-integration)
* [Advanced configuration](#advanced-configuration)
* [Testing with Captchavel](#testing-with-captchavel)
* [Security](#security)
* [License](#license)

## Requirements

* Laravel 8.x
* PHP 8.0

> If you need support for old versions, consider sponsoring or donating.

## Installation

You can install the package via composer:

```bash
composer require darkghosthunter/captchavel
```

## Set up

Add the reCAPTCHA keys for your site to the environment file of your project. You can add each of them for reCAPTCHA v2 **checkbox**, **invisible**, **Android**, and **v3** (score).

If you don't have one, generate it in your [reCAPTCHA Admin panel](https://www.google.com/recaptcha/admin/).

```dotenv
RECAPTCHA_V2_CHECKBOX_SECRET=6t5geA1UAAAAAN...
RECAPTCHA_V2_CHECKBOX_KEY=6t5geA1UAAAAAN...

RECAPTCHA_V2_INVISIBLE_SECRET=6t5geA2UAAAAAN...
RECAPTCHA_V2_INVISIBLE_KEY=6t5geA2UAAAAAN...

RECAPTCHA_V2_ANDROID_SECRET=6t5geA3UAAAAAN...
RECAPTCHA_V2_ANDROID_KEY=6t5geA3UAAAAAN...

RECAPTCHA_V3_SECRET=6t5geA4UAAAAAN...
RECAPTCHA_V3_KEY=6t5geA4UAAAAAN...
```

This allows you to check different reCAPTCHA mechanisms using the same application, in different environments.

> Captchavel already comes with v2 keys for local development. For v3, you will need to create your own set of credentials.

## Usage

After you integrate reCAPTCHA into your frontend or Android app, set the Captchavel middleware in the routes you want:

* `recaptcha.v2` for Checkbox, Invisible and Android challenges.
* `recaptcha.v3` for Score driven interaction.

### Checkbox, Invisible and Android challenges

Add the `recaptcha.v2` middleware to your `POST` routes. The middleware will catch the `g-recaptcha-response` input and check if it's valid.

* `recaptcha.v2:checkbox` for explicitly rendered checkbox challenges.
* `recaptcha.v2:invisible` for invisible challenges.
* `recaptcha.v2:android` for Android app challenges.

When the validation fails, the user will be redirected back to the form route, or a JSON response will be returned with the validation errors.

```php
Route::post('login', 'LoginController@login')
    ->middleware('recaptcha.v2:checkbox');
``` 

> You can change the input name from `g-recaptcha-response` to a custom using a second parameter, like `recaptcha.v2:checkbox,_recaptcha`.

### Score driven interaction

The reCAPTCHA v3 middleware works differently from v2. This is a score-driven challenge between `0.0` and `1.0` where robots will get lower scores than humans. The default threshold is `0.5`.

Simply add the `recaptcha.v3` middleware to your route:

```php
Route::post('comment', 'CommentController@store')
    ->middleware('recaptcha.v3');
```

Once the challenge has been received, you will have access to two methods from the Request instance: `isHuman()` and `isRobot()`, which return `true` or `false`:

```php
public function store(Request $request, Post $post)
{
    $request->validate([
        'body' => 'required|string|max:255'
    ]);
    
    $comment = $post->comment()->make($request->only('body'));
    
    // Flag the comment as "moderated" if it was a written by robot. 
    $comment->moderated = $request->isRobot();
    
    $comment->save();
    
    return view('post.comment.show', ['comment' => $comment]);
}
```

#### Threshold, action and input name

The middleware accepts three parameters in the following order:

1. Threshold: Values **above or equal** are considered human.
2. Action: The action name to optionally check against.
3. Input: The name of the reCAPTCHA input to verify.

```php
Route::post('comment', 'CommentController@store')
    ->middleware('recaptcha.v3:0.7,login,custom-recaptcha-input');
```

> When checking the action name, ensure your Frontend action matches 

#### Faking robot and human scores 

You can easily fake a reCAPTCHA v3 response in your local development by setting `CAPTCHAVEL_FAKE` to `true`.

```dotenv
CAPTCHAVEL_FAKE=true
```

Then, you can fake a low-score response by adding an `is_robot` a checkbox, respectively.

```blade
<form id='login' method="POST">
  <input type="email" name="email">
  <input type="password" name="password">
  <input type="checkbox" name="is_robot" checked>
  <button class="g-recaptcha" data-sitekey="{{ captchavel('invisible') }}" data-callback='onSubmit'>Login</button>
  <br/>
</form>
```

## Frontend integration

[Check the official reCAPTCHA documentation](https://developers.google.com/recaptcha/intro) to integrate the reCAPTCHA script in your frontend, or inside your Android application.

You can use the `captchavel()` helper to output the site key depending on the challenge version you want to render: `checkbox`,  `invisible`, `android` or `score` (v3).

```blade
<form id='login' method="POST">
  <input type="email" name="email">
  <input type="password" name="password">
  
  <button class="g-recaptcha" data-sitekey="{{ captchavel('invisible') }}" data-callback='onSubmit'>Login</button>
  <br/>
</form>
```

> You can also retrieve the key using `android` for Android apps.

## Advanced configuration

Captchavel is intended to work out-of-the-box, but you can publish the configuration file for fine-tuning and additional reCAPTCHA verification.

```bash
php artisan vendor:publish --provider="DarkGhostHunter\Captchavel\CaptchavelServiceProvider"
```

You will get a config file with this array:

```php
<?php

return [
    'enable' => env('CAPTCHAVEL_ENABLE',  false),
    'fake' => env('CAPTCHAVEL_FAKE', false),
    'hostname' => env('RECAPTCHA_HOSTNAME'),
    'apk_package_name' => env('RECAPTCHA_APK_PACKAGE_NAME'),
    'threshold' => 0.5,
    'credentials' => [
        // ...
    ]
];
``` 

### Enable Switch

```dotenv
CAPTCHAVEL_ENABLE=true
```

The main switch to enable or disable Captchavel middleware. This can be handy to enable on some local environments to check real interaction using the included localhost test keys.

When switched off, the `g-recaptcha-response` won't be validated in the Request input, so you can safely disregard any frontend script or reCAPTCHA tokens or boxes.

### Fake responses

```dotenv
CAPTCHAVEL_FAKE=true
```

Setting this to true will allow your application to [fake v3-score responses from reCAPTCHA servers](#faking-robot-and-human-scores).

> This is automatically set to `true` when [running unit tests](#testing-with-captchavel).

### Hostname and APK Package Name

```dotenv
RECAPTCHA_HOSTNAME=myapp.com
RECAPTCHA_APK_PACKAGE_NAME=my.package.name
```

If you are not verifying the Hostname or APK Package Name in your [reCAPTCHA Admin Panel](https://www.google.com/recaptcha/admin/), you will have to issue the strings in the environment file. 

When the reCAPTCHA response from the servers is retrieved, it will be checked against these values when present. In case of mismatch, a validation exception will be thrown.

### Threshold

```php
return [
    'threshold' => 0.4
];
```

Default threshold to check against reCAPTCHA v3 challenges. Values **equal or above** will be considered as human.

If you're not using reCAPTCHA v3, or you're fine with the default, leave this alone. You can still [override the default in a per-route basis](#threshold-action-and-input-name).

### Credentials

```php
return [
    'credentials' => [
        // ...
    ]
];
```

Here is the full array of [reCAPTCHA credentials](#set-up) to use depending on the version. Do not change the array unless you know what you're doing.

## Testing with Captchavel

When unit testing your application, this package [automatically fakes reCAPTCHA responses](#fake-responses).

> When mocking requests, there is no need to add any reCAPTCHA token or secrets in your tests.

When using reCAPTCHA v3 (score), you can fake a response made by a human or robot by simply using the `fakeHuman()` and `fakeRobot()` methods, which will score `1.0` or `0.0` respectively.

```php
<?php

use DarkGhostHunter\Captchavel\Facades\Captchavel;

// Let the user login normally.
Captchavel::fakeHuman();

$this->post('login', [
    'email' => 'test@test.com',
    'password' => '123456',
])->assertRedirect('user.welcome');

// ... but if it's a robot, force him to use 2FA.
Captchavel::fakeRobot();

$this->post('login', [
    'email' => 'test@test.com',
    'password' => '123456',
])->assertViewIs('login.2fa');
```

Alternatively, `fakeScore()` method that will fake any score you set.

> Fake responses don't come with actions, hostnames or APK package names.

### Events

When a reCAPTCHA challenge is resolved, whatever result is received, the `ReCaptchaResponseReceived` event fires with the HTTP Request instance and the reCAPTCHA response.

### Using your own reCAPTCHA middleware

You may want to create your own reCAPTCHA middleware. Instead of doing one from scratch, you can extend the `BaseReCaptchaMiddleware`.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use DarkGhostHunter\Captchavel\Http\Middleware\BaseReCaptchaMiddleware;

class MyReCaptchaMiddleware extends BaseReCaptchaMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $input = 'g-recaptcha-response';
        
        $this->validateRequest($request, $input);

        $response = $this->retrieve($request, $input, 2, 'checkbox');

        if ($response->isInvalid()) {
            throw $this->validationException($input, 'Complete the reCAPTCHA challenge');
        }

        return $next($request);
    }
}
```

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
