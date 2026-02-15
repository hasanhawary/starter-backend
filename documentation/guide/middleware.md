---
title: Middleware
description: Your project's custom middleware
---

# Middleware

Your project includes custom middleware for language detection.

## LanguageMiddleware

**Location:** `app/Http/Middleware/LanguageMiddleware.php`

Detects language from `Accept-Language` header and sets application locale.

**Supported Languages:**
- `en` - English
- `ar` - Arabic (default)

**Your Code:**

```php
protected const array ALLOWED_LOCALIZATIONS = ['en', 'ar'];

public function handle(Request $request, Closure $next): Response
{
    $localization = $request->header('Accept-Language');

    $localization = in_array($localization, self::ALLOWED_LOCALIZATIONS, true)
        ? $localization
        : 'ar';

    app()->setLocale($localization);

    return $next($request);
}
```

**Usage:**

```bash
# English
curl -H "Accept-Language: en" http://api.example.com/countries

# Arabic (default)
curl -H "Accept-Language: ar" http://api.example.com/countries
```

**In Your Code:**

```php
$locale = app()->getLocale();  // 'en' or 'ar'

$country = Country::find(1);
echo $country->name;  // Returns name in current language
```

