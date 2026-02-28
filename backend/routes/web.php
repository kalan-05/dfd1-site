<?php

use App\Http\Controllers\Api\ReviewsController;
use App\Models\SiteSetting;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\Route;

$replaceTag = static function (string $html, string $pattern, string $replacement): string {
    return preg_replace($pattern, $replacement, $html, 1) ?? $html;
};

$applySeo = static function (string $html) use ($replaceTag): string {
    $settings = SiteSetting::query()->first();

    if (! $settings) {
        return $html;
    }

    $title = trim((string) ($settings->seo_title ?: $settings->site_name ?: 'ОФД №1'));
    $description = trim((string) ($settings->seo_description ?: ''));
    $keywords = trim((string) ($settings->seo_keywords ?: ''));
    $siteName = trim((string) ($settings->site_name ?: $title));
    $canonicalUrl = url('/');
    $ogImage = MediaUrl::toUrl($settings->og_image) ?: url('/favicon/android-chrome-512x512.png');
    $primaryPhone = trim((string) ($settings->phone_1 ?: $settings->phone_2 ?: ''));
    $email = trim((string) ($settings->email ?: ''));
    $address = trim((string) ($settings->address_main ?: ''));

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'MedicalOrganization',
        'name' => $siteName,
        'url' => $canonicalUrl,
        'telephone' => $primaryPhone,
        'email' => $email,
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'RU',
            'streetAddress' => $address,
        ],
    ];

    $html = $replaceTag($html, '#<title>.*?</title>#s', '<title>' . e($title) . '</title>');
    $html = $replaceTag($html, '#<meta\s+name="description"\s+content=".*?"\s*/?>#', '<meta name="description" content="' . e($description) . '" />');
    $html = $replaceTag($html, '#<meta\s+name="keywords"\s+content=".*?"\s*/?>#', '<meta name="keywords" content="' . e($keywords) . '" />');
    $html = $replaceTag($html, '#<meta\s+name="author"\s+content=".*?"\s*/?>#', '<meta name="author" content="' . e($siteName) . '" />');
    $html = $replaceTag($html, '#<meta\s+name="publisher"\s+content=".*?"\s*/?>#', '<meta name="publisher" content="' . e($siteName) . '" />');
    $html = $replaceTag($html, '#<meta\s+property="og:site_name"\s+content=".*?"\s*/?>#', '<meta property="og:site_name" content="' . e($siteName) . '" />');
    $html = $replaceTag($html, '#<meta\s+property="og:title"\s+content=".*?"\s*/?>#', '<meta property="og:title" content="' . e($title) . '" />');
    $html = $replaceTag($html, '#<meta\s+property="og:description"\s+content=".*?"\s*/?>#', '<meta property="og:description" content="' . e($description) . '" />');
    $html = $replaceTag($html, '#<meta\s+property="og:url"\s+content=".*?"\s*/?>#', '<meta property="og:url" content="' . e($canonicalUrl) . '" />');
    $html = $replaceTag($html, '#<meta\s+property="og:image"\s+content=".*?"\s*/?>#', '<meta property="og:image" content="' . e($ogImage) . '" />');
    $html = $replaceTag($html, '#<meta\s+name="twitter:title"\s+content=".*?"\s*/?>#', '<meta name="twitter:title" content="' . e($title) . '" />');
    $html = $replaceTag($html, '#<meta\s+name="twitter:description"\s+content=".*?"\s*/?>#', '<meta name="twitter:description" content="' . e($description) . '" />');
    $html = $replaceTag($html, '#<meta\s+name="twitter:image"\s+content=".*?"\s*/?>#', '<meta name="twitter:image" content="' . e($ogImage) . '" />');
    $html = $replaceTag($html, '#<link\s+rel="canonical"\s+href=".*?"\s*/?>#', '<link rel="canonical" href="' . e($canonicalUrl) . '" />');

    $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    return $replaceTag(
        $html,
        '#<script type="application/ld\+json">.*?</script>#s',
        "<script type=\"application/ld+json\">\n{$schemaJson}\n    </script>"
    );
};

$renderLegacyFrontend = function () use ($applySeo) {
    $legacyIndex = public_path('legacy-index.html');

    if (! is_file($legacyIndex)) {
        return view('welcome');
    }

    $html = file_get_contents($legacyIndex);
    $html = $applySeo($html);

    $captchaConfig = [
        'siteKey' => (string) config('services.hcaptcha.site_key', ''),
        'required' => (bool) config('services.hcaptcha.required', false),
    ];

    $bootstrapScript = '<script>window.__REVIEW_CAPTCHA__ = '
        . json_encode($captchaConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . ';</script>';

    $runtimeScript = <<<'HTML'
<script>
(function () {
  var cfg = window.__REVIEW_CAPTCHA__ || {};
  if (!cfg.siteKey) {
    return;
  }

  var widgetId = null;
  var scriptPromise = null;

  function loadScript() {
    if (window.hcaptcha && typeof window.hcaptcha.render === 'function') {
      return Promise.resolve(window.hcaptcha);
    }

    if (scriptPromise) {
      return scriptPromise;
    }

    scriptPromise = new Promise(function (resolve, reject) {
      var existing = document.getElementById('hcaptcha-script');
      if (existing) {
        existing.addEventListener('load', function () { resolve(window.hcaptcha); }, { once: true });
        existing.addEventListener('error', reject, { once: true });
        return;
      }

      var script = document.createElement('script');
      script.id = 'hcaptcha-script';
      script.src = 'https://js.hcaptcha.com/1/api.js?render=explicit';
      script.async = true;
      script.defer = true;
      script.onload = function () { resolve(window.hcaptcha); };
      script.onerror = reject;
      document.head.appendChild(script);
    });

    return scriptPromise;
  }

  function ensureWidget() {
    var form = document.getElementById('reviewForm');
    if (!form || form.dataset.hcaptchaReady === '1') {
      return;
    }

    var submitButton = form.querySelector('.form-submit');
    if (!submitButton) {
      return;
    }

    var wrapper = document.createElement('div');
    wrapper.className = 'form-group';
    var container = document.createElement('div');
    container.id = 'review-hcaptcha-widget';
    wrapper.appendChild(container);

    submitButton.parentNode.insertBefore(wrapper, submitButton);

    loadScript()
      .then(function (hcaptcha) {
        if (!hcaptcha || typeof hcaptcha.render !== 'function') {
          throw new Error('hCaptcha API not available');
        }

        widgetId = hcaptcha.render(container, {
          sitekey: cfg.siteKey,
          size: 'normal'
        });

        form.dataset.hcaptchaReady = '1';
      })
      .catch(function (error) {
        console.error('hCaptcha load error', error);
      });

    form.addEventListener('submit', function (event) {
      if (!cfg.required) {
        return;
      }

      var token = '';
      if (window.hcaptcha && typeof window.hcaptcha.getResponse === 'function') {
        try {
          token = widgetId !== null ? window.hcaptcha.getResponse(widgetId) : window.hcaptcha.getResponse();
        } catch (_) {
          token = '';
        }
      }

      if (!token) {
        event.preventDefault();
        event.stopImmediatePropagation();
        alert('Please complete captcha verification.');
        return;
      }

      var hidden = form.querySelector('input[name=\"captcha_token\"]');
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'captcha_token';
        form.appendChild(hidden);
      }
      hidden.value = token;
    }, true);

    form.addEventListener('reset', function () {
      if (window.hcaptcha && typeof window.hcaptcha.reset === 'function' && widgetId !== null) {
        window.hcaptcha.reset(widgetId);
      }
    });
  }

  var observer = new MutationObserver(function () {
    ensureWidget();
  });

  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ensureWidget, { once: true });
  } else {
    ensureWidget();
  }
})();
</script>
HTML;

    $html = str_replace('</body>', $bootstrapScript . $runtimeScript . '</body>', $html);

    return response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Cache-Control' => 'no-cache, private',
    ]);
};

Route::get('/', function () use ($renderLegacyFrontend, $applySeo) {
    $newIndex = public_path('new-index.html');

    if (is_file($newIndex)) {
        $html = file_get_contents($newIndex);
        $html = $applySeo($html);

        $captchaConfig = [
            'siteKey' => (string) config('services.hcaptcha.site_key', ''),
            'required' => (bool) config('services.hcaptcha.required', false),
        ];

        $bootstrapScript = '<script>window.__REVIEW_CAPTCHA__ = '
            . json_encode($captchaConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . ';</script>';

        $html = str_replace('</body>', $bootstrapScript . '</body>', $html);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    return $renderLegacyFrontend();
});

Route::get('/legacy', function () use ($renderLegacyFrontend) {
    return $renderLegacyFrontend();
});

Route::post('/php/send-review.php', [ReviewsController::class, 'legacyStore'])
    ->middleware('throttle:reviews-form');
