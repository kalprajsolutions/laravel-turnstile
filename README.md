# Laravel Turnstile

[![License](https://img.shields.io/packagist/l/kalprajsolutions/laravel-turnstile.svg)](https://packagist.org/packages/kalprajsolutions/laravel-turnstile)
[![Total Downloads](https://img.shields.io/packagist/dt/kalprajsolutions/laravel-turnstile.svg)](https://packagist.org/packages/kalprajsolutions/laravel-turnstile)
[![Latest Version](https://img.shields.io/packagist/v/kalprajsolutions/laravel-turnstile.svg)](https://packagist.org/packages/kalprajsolutions/laravel-turnstile)
[![Laravel](https://img.shields.io/badge/Laravel-8%2B-orange.svg)](https://laravel.com)

A Laravel package for easy integration of Cloudflare Turnstile CAPTCHA into your forms. Provides a simple Blade component with both standard and lazy loading modes for optimal user experience.

## Description

Laravel Turnstile provides a seamless way to integrate Cloudflare's Turnstile CAPTCHA into your Laravel applications. Unlike traditional CAPTCHAs, Turnstile is user-friendly and doesn't require users to solve puzzles, making it invisible to most legitimate users while still protecting your forms from bots.

### Features

- **Easy Integration** - Simple Blade component to add Turnstile to any form
- **Standard Mode** - Widget loads immediately, buttons disabled until verification
- **Lazy Mode** - Widget appears only when user attempts to submit
- **Multiple Widgets** - Support for multiple Turnstile widgets on the same page
- **Livewire Support** - Built-in integration with Livewire components
- **Server-side Validation** - Multiple ways to validate the Turnstile response

## Installation

Install the package via Composer:

```bash
composer require kalprajsolutions/laravel-turnstile
```

The package will automatically register its service provider. If you're using Laravel 10 or below, you may need to manually add the service provider to your `config/app.php`.

### Publish Component (Optional)

If you need to customize the configuration, publish the view file:

```bash
php artisan vendor:publish --provider="KalprajSolutions\LaravelTurnstile\TurnstileServiceProvider"
```

## Configuration

### Environment Variables

Add your Cloudflare Turnstile credentials to your `.env` file:

```env
CLOUDFLARE_SITE_KEY=your_site_key_here
CLOUDFLARE_SECRET_KEY=your_secret_key_here
```

### Services Configuration

The package uses Laravel's `config/services.php` structure. Add the following to your `config/services.php` file:

```php
'cloudflare' => [
    'site_key' => env('CLOUDFLARE_SITE_KEY'),
    'secret_key' => env('CLOUDFLARE_SECRET_KEY'),
    'endpoint' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
],
```

### Getting Your Cloudflare Credentials

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Navigate to **Turnstile** under the **Security** section
3. Create a new site and get your Site Key and Secret Key
4. Make sure to add your domain to the allowed domains list

## Usage

### Basic Usage (Standard Mode)

In standard mode, the Turnstile widget loads immediately when the page loads. Form buttons are disabled until the user completes the verification.

```blade
<form action="/contact" method="POST">
    @csrf
    
    <div>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
    </div>
    
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    
    <!-- Turnstile Widget -->
    <x-turnstile :button-ids="['submit-btn']" />
    
    <button type="submit" id="submit-btn">Send Message</button>
</form>
```

The component automatically includes a hidden input named `cf-turnstile-response` containing the verification token.

### Lazy Mode

In lazy mode, the widget remains hidden until the user attempts to submit the form. This provides a cleaner user experience by only showing the CAPTCHA when needed.

```blade
<form action="/newsletter" method="POST" id="newsletter-form">
    @csrf
    
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit" id="subscribe-btn">Subscribe</button>
</form>

<!-- Place Turnstile outside the form for lazy mode -->
<x-turnstile 
    lazy 
    form-id="newsletter-form" 
    :button-ids="['subscribe-btn']" 
/>
```

### Customizing Appearance

```blade
<x-turnstile
    theme="dark"
    size="normal"
    container-id="custom-container"
    :button-ids="['submit-btn']"
/>
```

### Multiple Widgets on Same Page

The component automatically handles multiple instances with unique IDs:

```blade
<!-- Login Form -->
<form action="/login" method="POST" id="login-form">
    @csrf
    <x-turnstile form-id="login-form" :button-ids="['login-btn']" />
    <button type="submit" id="login-btn">Login</button>
</form>

<!-- Register Form -->
<form action="/register" method="POST" id="register-form">
    @csrf
    <x-turnstile form-id="register-form" :button-ids="['register-btn']" />
    <button type="submit" id="register-btn">Register</button>
</form>
```

### Custom JavaScript Callbacks

```blade
<x-turnstile 
    callback="onTurnstileSuccess" 
    :button-ids="['submit-btn']"
/>

<script>
function onTurnstileSuccess(token) {
    console.log('Verification successful:', token);
    // Custom logic here - e.g., enable additional fields
}
</script>
```

### Livewire Integration

For Livewire components, use the callback to bind the token to a Livewire property:

```blade
<div>
    <form wire:submit.prevent="submitForm">
        <input type="email" wire:model="email" required>
        
        <x-turnstile 
            callback="onTurnstileSuccess" 
            :button-ids="['submit-btn']"
        />
        
        <button type="submit" id="submit-btn">Submit</button>
    </form>

    <script>
    function onTurnstileSuccess(token) {
        console.log('Verification successful:', token);
        // Bind token to Livewire
        @this.set('turnstileToken', token);
    }
    </script>
</div>
```

```php
<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ContactForm extends Component
{
    public $email;
    public $turnstileToken;

    public function submitForm()
    {
        $this->validate([
            'email' => 'required|email',
            'turnstileToken' => 'required|turnstile',
        ]);

        // Your submission logic here
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
```

## Server-side Validation

Always validate the Turnstile token on the server side to prevent spam and ensure security.

### Method 1: Using 'turnstile' Rule (Recommended)

The package registers a custom validation rule that you can use in your validation array:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'cf-turnstile-response' => ['required', 'turnstile'],
        ]);

        // Process the form submission
        // ...
        
        return redirect()->back()->with('success', 'Form submitted successfully!');
    }
}
```

### Method 2: Using the Facade

You can use the Turnstile facade to create validation rules programmatically:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Turnstile;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'cf-turnstile-response' => ['required', 'string', Turnstile::validate()],
        ]);

        // Process the form submission
    }
}
```

### Method 3: Using the Rule Class Directly

For more control, you can use the validation rule class directly:

```php
<?php

use KalprajSolutions\LaravelTurnstile\Rules\ValidCloudflareTurnstile;
use Illuminate\Support\Facades\Validator;

$validator = Validator::make($request->all(), [
    'cf-turnstile-response' => ['required', new ValidCloudflareTurnstile()],
]);

if ($validator->fails()) {
    // Handle validation failure
}
```

### Additional Facade Methods

The Turnstile facade provides additional helper methods:

```php
// Get the site key from configuration
$siteKey = Turnstile::getSiteKey();

// Get the secret key from configuration
$secretKey = Turnstile::getSecretKey();

// Check if Turnstile is properly configured
if (Turnstile::isConfigured()) {
    // Turnstile is configured
}
```

## API Reference

### Component Parameters

#### Core Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `containerId` | string | `'cf-turnstile-container'` | HTML ID for the widget container element |
| `inputName` | string | `'cf-turnstile-response'` | Name attribute for the hidden input containing the token |
| `theme` | string | `'auto'` | Widget theme: `'auto'`, `'light'`, or `'dark'` |
| `size` | string | `'flexible'` | Widget size: `'normal'` or `'flexible'` |

#### Behavior Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `buttonIds` | array | `[]` | Array of button IDs to disable during verification |
| `buttonId` | string | `null` | Single button ID (alternative to `buttonIds`) |
| `callback` | string | `null` | Name of custom JavaScript callback function |
| `lazy` | boolean | `false` | Enable lazy/deferred loading mode |
| `formId` | string | `null` | Form ID required for lazy mode |

#### Livewire Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `livewire` | boolean | `false` | Enable Livewire integration ( usefor future) |
| `model` | string | `null` | Livewire property name for token binding (for future use) |

## Troubleshooting

### Widget Not Appearing

- **Verify your site key** is correctly set in `.env` and matches your domain in Cloudflare dashboard
- **Check browser console** for JavaScript errors
- **Ensure stacks are included** in your layout:
  ```blade
  @stack('custom_js_plugins')
  @stack('custom_js')
  ```

### Token Validation Failing

- **Confirm your secret key** is set in `.env` as `CLOUDFLARE_SECRET_KEY`
- **Check the hidden input name** matches your validation (default: `cf-turnstile-response`)
- **Verify Cloudflare's response** format in your server logs
- **Check error logs** - the package logs Turnstile error codes for debugging

### Lazy Mode Issues

- **Ensure `formId`** matches the form's `id` attribute exactly
- **Check `buttonIds`** contains the correct button IDs
- **Verify form submission** is not prevented by other JavaScript
- **Lazy mode requires** the component to be placed outside the form element

### Multiple Widgets on Same Page

- The component automatically generates unique IDs, but avoid custom `containerId` conflicts
- Each widget should have its own `inputName` if needed for separate validation
- In lazy mode, each widget needs its own unique `form-id`

### Network/Loading Issues

- Turnstile loads from Cloudflare's CDN; ensure no ad blockers or firewalls are interfering
- Check network tab for failed requests to `challenges.cloudflare.com`
- Some corporate networks may block the Turnstile CDN

### Common Error Codes

If you see error codes in your logs, refer to [Cloudflare's Turnstile documentation](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/):

- `missing-input-secret` - The secret key is missing
- `invalid-input-secret` - The secret key is invalid
- `missing-input-response` - The response token is missing
- `invalid-input-response` - The response token is invalid or malformed
- `bad-request` - The request was rejected
- `timeout-or-duplicate` - The response has expired or has been used

## Security Considerations

1. **Always validate server-side** - Never rely solely on client-side verification
2. **Keep your secret key secure** - Never expose it in client-side code
3. **Use HTTPS** - Cloudflare Turnstile requires HTTPS in production
4. **Rate limiting** - Consider implementing additional rate limiting on your forms
5. **Token expiration** - Tokens expire after a certain time; always validate immediately

## Credits

- [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) - The underlying CAPTCHA service
- [Laravel Framework](https://laravel.com/) - The PHP framework this package integrates with

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
