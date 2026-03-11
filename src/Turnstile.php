<?php

namespace KalprajSolutions\LaravelTurnstile;

use KalprajSolutions\LaravelTurnstile\Rules\ValidCloudflareTurnstile;

/**
 * Main class for Laravel Turnstile package.
 *
 * Provides easy access to creating validation rule instances for Turnstile CAPTCHA.
 */
class Turnstile
{
    /**
     * Create a new Turnstile validation rule instance.
     *
     * @return \KalprajSolutions\LaravelTurnstile\Rules\ValidCloudflareTurnstile
     */
    public function rule(): ValidCloudflareTurnstile
    {
        return new ValidCloudflareTurnstile();
    }

    /**
     * Create a new Turnstile validation rule instance (alias of rule).
     *
     * @return \KalprajSolutions\LaravelTurnstile\Rules\ValidCloudflareTurnstile
     */
    public function validate(): ValidCloudflareTurnstile
    {
        return $this->rule();
    }

    /**
     * Get the site key from configuration.
     *
     * @return string|null
     */
    public function getSiteKey(): ?string
    {
        return config('services.cloudflare.site_key');
    }

    /**
     * Get the secret key from configuration.
     *
     * @return string|null
     */
    public function getSecretKey(): ?string
    {
        return config('services.cloudflare.secret_key');
    }

    /**
     * Check if Turnstile is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->getSiteKey()) && !empty($this->getSecretKey());
    }
}
