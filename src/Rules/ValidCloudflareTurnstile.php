<?php

namespace KalprajSolutions\LaravelTurnstile\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ValidCloudflareTurnstile implements ValidationRule
{
    protected $secret;
    protected $client;

    public function __construct()
    {
        $this->secret = config('services.cloudflare.secret');
        $this->client = new Client();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value)) {
            $fail('Blank CAPTCHA. You need to prove you are human.');
        }

        // Request data
        $data = [
            'secret' => \config('services.cloudflare.secret_key'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ];

        // Make the request to Cloudflare
        $response = $this->client->post(\config('services.cloudflare.endpoint'), [
            'form_params' => $data,
        ]);

        // Parse the response
        $result = json_decode($response->getBody(), true);

        if (isset($result['error-codes']) && count($result['error-codes']) > 0) {
            Log::error('Cloudflare Turnstile check failed', [
                'error-codes' => $result['error-codes'],
                'ip-address'  => request()->ip(),
            ]);

            $fail('Invalid CAPTCHA. You need to prove you are human.');
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid CAPTCHA. You need to prove you are human.';
    }
}