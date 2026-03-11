<?php
/**
 * Cloudflare Turnstile Component
 *
 * STANDARD MODE:
 * <x-turnstile />
 *
 * LAZY/DEFERRED MODE (renders on submit):
 * <x-turnstile lazy form-id="newsletter-form" />
 * <x-turnstile lazy form-id="contact-form" :button-ids="['submit-btn']" />
 */

$containerId = $containerId ?? 'cf-turnstile-container';
$inputName = $inputName ?? 'cf-turnstile-response';
$siteKey = config('services.cloudflare.site_key');
$theme = $theme ?? 'auto';
$size = $size ?? 'flexible';
$buttonId = $buttonId ?? null;
$buttonIds = $buttonIds ?? [];
$callback = $callback ?? null;
$lazy = $lazy ?? false; // NEW: Deferred loading mode
$formId = $formId ?? null; // Required for lazy mode
// Support both single buttonId and buttonIds array
if ($buttonId) {
    $buttonIds = array_merge($buttonIds, [$buttonId]);
}
$buttonIds = array_values(array_filter($buttonIds));

// Generate unique instance ID for multiple widgets on same page
$instanceId = uniqid('ts_');
?>

{{-- Hidden input: Always created, but in lazy mode we'll also add one inside the form --}}
<input type="hidden" name="{{ $inputName }}" id="{{ $inputName }}" value="">

{{-- Container: Hidden initially in lazy mode, visible in standard mode --}}
<div id="{{ $containerId }}" class="cf-turnstile {{ $lazy ? 'turnstile-lazy-hidden' : '' }}"
    data-sitekey="{{ $siteKey }}" data-theme="{{ $theme }}" data-size="{{ $size }}"
    @if ($lazy) style="display: none; margin-top: 10px;" @endif></div>

@if ($lazy)
    {{-- Styles for lazy mode transitions --}}
    <style>
        .turnstile-lazy-hidden {
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .turnstile-lazy-visible {
            display: block !important;
            opacity: 1;
            transform: translateY(0);
        }

        .turnstile-loading {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
@endif

@push('custom_js_plugins')
    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onloadTurnstileCallback_{{ $instanceId }}"
        async defer></script>
@endpush

@push('custom_js')
    @if (strlen(config('services.cloudflare.site_key')) < 2)
        <script>
            (function() {
                const styles = {
                    reset: 'color: inherit; background: inherit; font-weight: normal;',
                    title: 'color: #f59e0b; font-size: 16px; font-weight: bold;',
                    subtitle: 'color: #9ca3af; font-size: 12px;',
                    code: 'color: #22d3ee; background: #1f2937; padding: 2px 6px; border-radius: 4px;',
                    warning: 'color: #fbbf24; font-weight: bold;',
                    success: 'color: #4ade80; font-weight: bold;',
                    link: 'color: #60a5fa; text-decoration: underline;',
                    box: 'padding: 12px; border-radius: 8px; background: linear-gradient(135deg, #451a03 0%, #78350f 100%); border: 2px solid #f59e0b;'
                };

                const siteKey = document.querySelector('[data-sitekey]')?.dataset.sitekey ||
                    (typeof turnstileConfig !== 'undefined' ? turnstileConfig.siteKey : null);

                const isConfigured = siteKey && siteKey !== 'YOUR_SITE_KEY' && siteKey.length > 10;

                console.group('%c 🔒 Cloudflare Turnstile ', styles.box + styles.title);

                if (!isConfigured) {
                    console.log('%c⚠️ Configuration Missing', styles.warning);
                    console.log('%cThe Turnstile component is not properly configured.', styles.subtitle);
                    console.log('');
                    console.log('%cRequired steps:', 'color: #e5e7eb; font-weight: bold;');
                    console.log('%c1.%c Add to %c.env%c:', 'color: #fbbf24;', styles.reset, styles.code, styles.reset);
                    console.log('   CLOUDFLARE_SITE_KEY=your_actual_site_key');
                    console.log('   CLOUDFLARE_SECRET_KEY=your_actual_secret_key');
                    console.log('');
                    console.log('%c2.%c Add to %cconfig/services.php%c:', 'color: #fbbf24;', styles.reset, styles.code,
                        styles.reset);
                    console.log(`   'cloudflare' => [
        'site_key' => env('CLOUDFLARE_SITE_KEY'),
        'secret_key' => env('CLOUDFLARE_SECRET_KEY'),
        'endpoint' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],`);
                    console.log('');
                    console.log('%c📖 Documentation: %chttps://developers.cloudflare.com/turnstile/', styles.subtitle,
                        styles.link);
                } else {
                    console.log('%c✓ Properly Configured', styles.success);
                    console.log('%cSite Key: %c' + siteKey.substring(0, 8) + '...' + siteKey.slice(-4), styles.subtitle,
                        styles.code);
                }

                console.groupEnd();
            })();
        </script>
    @endif
    <script>
        (function() {
            'use strict';

            const instanceId = '{{ $instanceId }}';
            const containerId = @json($containerId);
            const inputName = @json($inputName);
            const siteKey = @json($siteKey);
            const buttonIds = @json($buttonIds);
            const customCallback = @json($callback);
            const isLazy = @json($lazy);
            const formId = @json($formId);

            let widgetId = null;
            let isVerifying = false;
            let pendingSubmit = false;

            function setButtonsDisabled(disabled) {
                buttonIds.forEach(function(buttonId) {
                    const button = document.getElementById(buttonId);
                    if (button) {
                        button.disabled = disabled;
                        button.classList.toggle('turnstile-loading', disabled);
                    }
                });
            }

            function clearToken() {
                const standaloneInput = document.getElementById(inputName);
                if (standaloneInput) standaloneInput.value = '';

                // Clear form-embedded hidden field
                const formHiddenInput = document.getElementById(inputName + '-' + instanceId);
                if (formHiddenInput) formHiddenInput.value = '';

                if (!isLazy) setButtonsDisabled(true);
            }

            function showWidget() {
                const container = document.getElementById(containerId);
                if (container) {
                    container.style.display = 'block';
                    // Trigger reflow for transition
                    void container.offsetWidth;
                    container.classList.remove('turnstile-lazy-hidden');
                    container.classList.add('turnstile-lazy-visible');
                }
            }

            function hideWidget() {
                const container = document.getElementById(containerId);
                if (container) {
                    container.classList.remove('turnstile-lazy-visible');
                    container.classList.add('turnstile-lazy-hidden');
                    setTimeout(() => {
                        if (!container.classList.contains('turnstile-lazy-visible')) {
                            container.style.display = 'none';
                        }
                    }, 300);
                }
            }

            function executeWidget() {
                if (widgetId && typeof turnstile !== 'undefined') {
                    turnstile.execute(widgetId);
                }
            }

            function resetWidget() {
                if (widgetId && typeof turnstile !== 'undefined') {
                    turnstile.reset(widgetId);
                    clearToken();
                }
                isVerifying = false;
                pendingSubmit = false;
            }

            // Global callback for this instance
            window['onloadTurnstileCallback_' + instanceId] = function() {
                const container = document.getElementById(containerId);
                if (!container) {
                    console.error('Turnstile container not found:', containerId);
                    return;
                }

                // Standard mode: disable buttons initially
                if (!isLazy && buttonIds.length > 0) {
                    setButtonsDisabled(true);
                }

                const renderParams = {
                    sitekey: siteKey,
                    theme: @json($theme),
                    size: @json($size),
                    callback: function(token) {
                        // Update both hidden fields - the standalone one and the one inside the form
                        const standaloneInput = document.getElementById(inputName);
                        if (standaloneInput) standaloneInput.value = token;

                        // Update form-embedded hidden field
                        const formHiddenInput = document.getElementById(inputName + '-' + instanceId);
                        if (formHiddenInput) formHiddenInput.value = token;

                        if (!isLazy) {
                            setButtonsDisabled(false);
                        }

                        // Execute custom callback if provided
                        if (customCallback && typeof window[customCallback] === 'function') {
                            window[customCallback](token);
                        }

                        // If lazy mode and form was pending, submit it now
                        if (isLazy && pendingSubmit && formId) {
                            const form = document.getElementById(formId);
                            if (form) {
                                // Remove event listener to prevent loop
                                form.removeEventListener('submit', handleFormSubmit);
                                form.submit();
                            }
                        }
                    },
                    'error-callback': function() {
                        clearToken();
                        isVerifying = false;
                        pendingSubmit = false;
                        console.error('Turnstile verification failed');

                        if (isLazy) {
                            // Keep widget visible for retry
                            setButtonsDisabled(false);
                        }
                    },
                    'expired-callback': function() {
                        clearToken();
                        isVerifying = false;
                        if (isLazy) {
                            hideWidget();
                        }
                    },
                    'timeout-callback': function() {
                        clearToken();
                        isVerifying = false;
                        pendingSubmit = false;
                        console.error('Turnstile verification timed out');

                        if (isLazy) {
                            setButtonsDisabled(false);
                        }
                    }
                };

                // Lazy mode: Add execution parameter
                if (isLazy) {
                    renderParams.execution = 'execute';
                }

                widgetId = turnstile.render('#' + containerId, renderParams);

                // Setup form interception for lazy mode
                if (isLazy && formId) {
                    setupFormInterception();
                }
            };

            function setupFormInterception() {
                const form = document.getElementById(formId);
                if (!form) {
                    console.error('Form not found for lazy Turnstile:', formId);
                    return;
                }

                // Create hidden input inside the form for proper form submission
                // This ensures the token is submitted even when Turnstile is outside the form
                const formHiddenInput = document.createElement('input');
                formHiddenInput.type = 'hidden';
                formHiddenInput.name = inputName;
                formHiddenInput.id = inputName + '-' + instanceId;
                formHiddenInput.value = '';
                form.appendChild(formHiddenInput);

                // Intercept form submission
                form.addEventListener('submit', handleFormSubmit);
            }

            function handleFormSubmit(e) {
                if (isVerifying) {
                    // Already processing, prevent double-submit
                    e.preventDefault();
                    return;
                }

                // Check both hidden fields - standalone and form-embedded
                const standaloneInput = document.getElementById(inputName);
                const formHiddenInput = document.getElementById(inputName + '-' + instanceId);
                const token = standaloneInput ? standaloneInput.value : (formHiddenInput ? formHiddenInput.value : '');

                // If no token yet, show widget and execute
                if (!token) {
                    e.preventDefault();
                    isVerifying = true;
                    pendingSubmit = true;

                    showWidget();
                    setButtonsDisabled(true);

                    // Small delay to allow widget to render before executing
                    setTimeout(() => {
                        executeWidget();
                    }, 100);

                    return;
                }

                // Token exists, allow normal submission
                // But remove this handler to prevent recursion
                const form = document.getElementById(formId);
                form.removeEventListener('submit', handleFormSubmit);
            }

            // Expose global controls for this instance
            window['turnstile_' + instanceId] = {
                reset: resetWidget,
                execute: executeWidget,
                getWidgetId: () => widgetId
            };

            // Cleanup
            window.addEventListener('beforeunload', function() {
                if (widgetId && typeof turnstile !== 'undefined') {
                    turnstile.remove(widgetId);
                }
            });
        })();
    </script>
@endpush
