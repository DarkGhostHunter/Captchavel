![Franck V. - Unsplash (UL) #JjGXjESMxOY](https://images.unsplash.com/photo-1535378620166-273708d44e4c?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/captchavel.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/captchavel) [![License](https://poser.pugx.org/darkghosthunter/captchavel/license)](https://packagist.org/packages/darkghosthunter/larapoke) ![](https://img.shields.io/packagist/php-v/darkghosthunter/captchavel.svg) ![](https://github.com/DarkGhostHunter/Captchavel/workflows/PHP%20Composer/badge.svg) [![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Captchavel/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Captchavel?branch=master) [![Maintainability](https://api.codeclimate.com/v1/badges/9571f57106069b5f3aac/maintainability)](https://codeclimate.com/github/DarkGhostHunter/Captchavel/maintainability) [![Laravel Octane Compatible](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://github.com/laravel/octane)

# Captchavel

Integrate reCAPTCHA into your Laravel app better than the Big G itself!

It uses your Laravel HTTP Client **async HTTP/2**, making your app **fast**. You only need a couple of lines to integrate.

## Requirements

* Laravel 8.x, or later
* PHP 8.0 or later

> If you need support for old versions, consider sponsoring or donating.

## Installation

You can install the package via Composer:

```bash
composer require darkghosthunter/captchavel
```

## Set up

Add the reCAPTCHA keys for your site to the environment file of your project. You can add each of them for reCAPTCHA v2 **checkbox**, **invisible**, **Android**, and **score**.

If you don't have one, generate it in your [reCAPTCHA Admin panel](https://www.google.com/recaptcha/admin/).

```dotenv
RECAPTCHA_CHECKBOX_SECRET=6t5geA1UAAAAAN...
RECAPTCHA_CHECKBOX_KEY=6t5geA1UAAAAAN...

RECAPTCHA_INVISIBLE_SECRET=6t5geA2UAAAAAN...
RECAPTCHA_INVISIBLE_KEY=6t5geA2UAAAAAN...

RECAPTCHA_ANDROID_SECRET=6t5geA3UAAAAAN...
RECAPTCHA_ANDROID_KEY=6t5geA3UAAAAAN...

RECAPTCHA_SCORE_SECRET=6t5geA4UAAAAAN...
RECAPTCHA_SCORE_KEY=6t5geA4UAAAAAN...
```

This allows you to check different reCAPTCHA mechanisms using the same application, in different environments.

> Captchavel already comes with v2 keys for local development. For v3, you will need to create your own set of credentials.

## Usage

Usage differs based on if you're using checkbox, invisible, or Android challenges, or the v3 score-driven challenge.

### Checkbox, invisible and Android challenges

After you integrate reCAPTCHA into your frontend or Android app, set the Captchavel middleware in the `POST` routes where a form with reCAPTCHA is submitted. The middleware will catch the `g-recaptcha-response` input (you can change it later) and check if it's valid.

* `recaptcha:checkbox` for explicitly rendered checkbox challenges.
* `recaptcha:invisible` for invisible challenges.
* `recaptcha:android` for Android app challenges.

```php
use App\Http\Controllers\Auth\LoginController;

Route::post('login', [LoginController::class, 'login'])->middleware('recaptcha:checkbox');
```

When the validation fails, the user will be redirected back, or a JSON response will be returned with the validation errors.

> You can change the input name from `g-recaptcha-response` to a custom using a second parameter, like `recaptcha.checkbox:my_input_name`.

### Score-driven challenge

The reCAPTCHA v3 middleware works differently from v2. This is a score-driven response is _always_ a success, but the challenge scores between `0.0` and `1.0`. Human-like interaction will be higher, while robots will score lower. The default threshold is `0.5`, but this can be changed globally or per-route.

To start using it, simply add the `recaptcha.score` middleware to your route:

```php
use App\Http\Controllers\CommentController;

Route::post('comment', [CommentController::class, 'create'])->middleware('recaptcha.score');
```

Once the challenge has been received, you will have access to two methods from the Request class or instance: `isHuman()` and `isRobot()`, which return `true` or `false`:

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

You can also have access to the response from reCAPTCHA using the `response()` method of the `Captchavel` facade:

```php
use DarkGhostHunter\Captchavel\Facades\Captchavel;

$response = Captchavel::response();

if ($response->score > 0.2) {
    return 'Try again!';
}
```

#### Threshold, action and input name

The middleware accepts three additional parameters in the following order:

1. Threshold: Values **above or equal** are considered human.
2. Action: The action name to optionally check against.
3. Input: The name of the reCAPTCHA input to verify.

```php
use App\Http\Controllers\CommentController;

Route::post('comment', [CommentController::class, 'create'])
    ->middleware('recaptcha.score:0.7,login,custom-recaptcha-input');
```

> When checking the action name, ensure your frontend action matches with the expected in the middleware.

#### Bypassing on authenticated users

Sometimes you may want to bypass reCAPTCHA checks on authenticated user, or automatically receive it as a "human" on score-driven challenges. While in your frontend you can programmatically disable reCAPTCHA when the user is authenticated, on the backend you can specify the guards to check as the last middleware parameters.

Since having a lot of arguments on a middleware can quickly become spaghetti code, use the `ReCaptcha` helper to declare it using fluid methods.

```php
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MessageController;
use DarkGhostHunter\Captchavel\ReCaptcha;
use Illuminate\Support\Facades\Route

Route::post('message/send', [MessageController::class, 'send'])
    ->middleware(ReCaptcha::invisible()->except('user')->toString());

Route::post('comment/store', [CommentController::class, 'store'])
    ->middleware(ReCaptcha::score(0.7)->action('comment.store')->except('admin', 'moderator')->toString());
```

> Ensure you set the middleware as `->toString()` when using the helper to declare the middleware.

#### Faking reCAPTCHA scores 

You can easily fake a reCAPTCHA response scores in your local development by setting `CAPTCHAVEL_FAKE` to `true`.

```dotenv
CAPTCHAVEL_FAKE=true
```

This environment variable changes the reCAPTCHA Factory for a fake one, which will fake successful responses from reCAPTCHA, instead of resolving real challenges.

From there, you can fake a robot response by filling the `is_robot` input in your form.

```blade
<form id="comment" method="post">
    <textarea name="body"></textarea>
    @env('local', 'testing')
        <input type="checkbox" name="is_robot" checked>
    @endenv
    <button class="g-recaptcha" data-sitekey="{{ captchavel('invisible') }}" data-callback='onSubmit'>Login</button>
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

Captchavel is intended to work out-of-the-box, but you can publish the configuration file for fine-tuning the reCAPTCHA verification.

```bash
php artisan vendor:publish --provider="DarkGhostHunter\Captchavel\CaptchavelServiceProvider" --tag="config"
```

You will get a config file with this array:

```php
<?php

use DarkGhostHunter\Captchavel\Captchavel;

return [
    'enable'            => env('CAPTCHAVEL_ENABLE',  false),
    'fake'              => env('CAPTCHAVEL_FAKE', false),
    'hostname'          => env('RECAPTCHA_HOSTNAME'),
    'apk_package_name'  => env('RECAPTCHA_APK_PACKAGE_NAME'),
    'threshold'         => 0.5,
    'credentials'       => [
        // ...
    ]
];
``` 

### Enable Switch

```php
<?php

return [
    'enable' => env('CAPTCHAVEL_ENABLE', false),
];
```

By default, Captchavel is disabled, so it doesn't check reCAPTCHA challenges, and on score-driven routes, it will always resolve as human interaction.

You can forcefully enable it with the `CAPTCHAVEL_ENABLE` environment variable.

```dotenv
CAPTCHAVEL_ENABLE=true
```

This can be handy to enable on some local or development environments to check real interaction using the included _localhost_ test keys, which only work on `localhost`.

> When switched off, the reCAPTCHA v2 challenges are not validated in the Request input, so you can safely disregard any frontend script or reCAPTCHA tokens or boxes.

### Fake responses

```dotenv
CAPTCHAVEL_FAKE=true
```

If Captchavel is [enabled](#enable-switch), setting this to true will allow your application to [fake v3-score responses from reCAPTCHA servers](#faking-recaptcha-scores).

> This is automatically set to `true` when [running unit tests](#testing-score-with-captchavel).

### Hostname and APK Package Name

```dotenv
RECAPTCHA_HOSTNAME=myapp.com
RECAPTCHA_APK_PACKAGE_NAME=my.package.name
```

If you are not verifying the Hostname or APK Package Name in your [reCAPTCHA Admin Panel](https://www.google.com/recaptcha/admin/), you will have to issue them in the environment file. 

When the reCAPTCHA response from the servers is retrieved, it will be checked against these values when present. In case of mismatch, a validation exception will be thrown.

### Threshold

```php
return [
    'threshold' => 0.4
];
```

Default threshold to check against reCAPTCHA v3 challenges. Values **equal or above** will be considered "human".

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

## Testing Score with Captchavel

On testing, when Captchavel is disabled, routes set with the v2 middleware won't need to input the challenge in their body as it will be not verified.

On the other hand, reCAPTCHA v3 (score) responses [are always faked](#fake-responses) as humans, even if [Captchavel is disabled](#enable-switch). This guarantees you can always access the response in your controller.

To modify the score in your tests, you should [enable faking](#fake-responses) on your tests through the `.env.testing` environment file, or in [PHPUnit environment section](https://phpunit.readthedocs.io/en/9.5/configuration.html?highlight=environment#the-env-element). If you use another testing framework, refer to its documentation.

```xml
<phpunit>
    <!-- ... -->
    <php>
        <env name="CAPTCHAVEL_FAKE" value="true"/>
    </php>
</phpunit>
```

Alternatively, you can change the configuration before your unit test:

```php
public function test_this_route()
{
    config()->set('captchavel.fake', true);
    
    // ...
}
```

> When faking challenges, there is no need to add any reCAPTCHA token or secrets in your tests.

When using reCAPTCHA v3 (score), you can fake a response made by a human or robot by simply using the `fakeHuman()` and `fakeRobot()` methods, which will score `1.0` or `0.0` respectively for all subsequent requests.

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

> Fake responses don't come with actions, hostnames or APK package names, so these are not validated.

### Faking Scores manually

Alternatively, `fakeScore()` method will fake responses with any score you set.

```php
<?php

use DarkGhostHunter\Captchavel\Facades\Captchavel;

// A human comment should be public.
Captchavel::fakeScore(0.7);

$this->post('comment', [
    'body' => 'This comment was made by a human',
])->assertSee('Your comment has been posted!');

// A robot should have its comment moderated.
Captchavel::fakeScore(0.4);

$this->post('comment', [
    'body' => 'Comment made by robot.',
])->assertSee('Your comment will be reviewed before publishing.');
```

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
