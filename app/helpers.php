<?php

/**
 * Bootstrap (autoloader, config, timezone) and global view/format helpers.
 */

declare(strict_types=1);

defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__));

// App\Foo => app/Foo.php, App\Controllers\Foo => app/controllers/Foo.php
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $parts = explode('\\', substr($class, 4));
    $name = array_pop($parts);
    $dir = $parts ? strtolower(implode('/', $parts)) . '/' : '';
    $file = APP_ROOT . '/app/' . $dir . $name . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/**
 * Read a config value using dot notation, e.g. config('db.dsn').
 */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    $config ??= require APP_ROOT . '/config.php';
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

date_default_timezone_set((string) config('timezone', 'UTC'));

/** Escape for HTML output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL prefix when served from a sub folder (Apache); empty under `php -S`. */
function base_url(): string
{
    static $base = null;
    return $base ??= rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
}

function url(string $path = '/'): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/** Asset URL with mtime cache-busting. */
function asset(string $path): string
{
    $file = APP_ROOT . '/public/assets/' . $path;
    return url('assets/' . $path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** Request path relative to the app base, e.g. "/dashboard". */
function current_path(): string
{
    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $base = base_url();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . trim($path, '/');
    return $path === '/index.php' ? '/' : $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Render a page inside a layout. */
function view(string $page, array $data = [], string $layout = 'app'): void
{
    $content = render('pages/' . $page, $data);
    echo render('layouts/' . $layout, $data + ['content' => $content]);
}

/** Render a template under app/views to a string. */
function render(string $template, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_ROOT . '/app/views/' . $template . '.php';
    return (string) ob_get_clean();
}

function partial(string $name, array $data = []): void
{
    echo render('partials/' . $name, $data);
}

/** Store a value for the next request only. */
function flash(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

/** Read a value flashed by the previous request. */
function flashed(string $key, mixed $default = null): mixed
{
    return $GLOBALS['__flash'][$key] ?? $default;
}

function toast(string $message, string $type = 'success'): void
{
    $_SESSION['_flash']['toasts'][] = ['message' => $message, 'type' => $type];
}

/** Previous input after a failed validation. */
function old(string $key, string $default = ''): string
{
    $value = flashed('old', [])[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

function error_for(string $key): string
{
    return (string) (flashed('errors', [])[$key] ?? '');
}

/** Inline error slot used by both server and client validation. */
function field_error(string $key): string
{
    return '<p class="field-error" id="err-' . e($key) . '" aria-live="polite">' . e(error_for($key)) . '</p>';
}

/** aria-invalid attribute for a field with a server error. */
function invalid_attr(string $key): string
{
    return error_for($key) !== '' ? ' aria-invalid="true"' : '';
}

/** Redirect back with errors and old input. */
function fail(string $path, array $errors): never
{
    flash('errors', $errors);
    flash('old', array_diff_key($_POST, array_flip(['password', '_csrf'])));
    toast('Please fix the highlighted fields.', 'error');
    redirect($path);
}

function input(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** A query-string value restricted to $allowed (falls back to $default). */
function query_choice(string $param, array $allowed, string $default): string
{
    $value = $_GET[$param] ?? $default;
    return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
}

/**
 * Filter tabs for list pages.
 *
 * @param array<string, string> $options value => label
 * @return list<array{label: string, href: string, active: bool}>
 */
function filter_tabs(string $path, array $options, string $current, string $param = 'status'): array
{
    $tabs = [];
    foreach ($options as $value => $label) {
        $tabs[] = [
            'label' => $label,
            'href' => url($path) . '?' . http_build_query([$param => $value]),
            'active' => $current === (string) $value,
        ];
    }
    return $tabs;
}

function page_param(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

/** 0.25 -> "0.25%", 10 -> "10%" */
function pct(float $value): string
{
    return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') . '%';
}

function money(mixed $amount): string
{
    $value = (float) $amount;
    return ($value < 0 ? '-$' : '$') . number_format(abs($value), 2);
}

function fmt_date(?string $datetime, string $format = 'd-M-Y'): string
{
    return $datetime ? date($format, (int) strtotime($datetime)) : '—';
}

/** Current timestamp (or the frozen one, see freeze_clock()). */
function now(): string
{
    return $GLOBALS['__clock'] ?? date('Y-m-d H:i:s');
}

/**
 * Freeze now() at $at (null = real time again). Used by the daily payout so its ledger rows carry
 * the payout date, and by the seeder to backdate history. Returns the previous frozen value.
 */
function freeze_clock(?string $at): ?string
{
    $previous = $GLOBALS['__clock'] ?? null;
    $GLOBALS['__clock'] = $at;
    return $previous;
}

function today(): string
{
    return substr(now(), 0, 10);
}

/** CSS class for a status word. */
function status_class(string $status): string
{
    return match ($status) {
        'Success', 'Active', 'Paid', 'Approved', 'Matured', 'Credited', 'Resolved', 'Replied', 'Distributed' => 'is-green',
        'Pending', 'Open', 'Simulated' => 'is-gold',
        default => 'is-red',
    };
}

/** Inline Lucide-style line icon. */
function icon(string $name, int $size = 20, string $class = 'icon'): string
{
    static $paths = [
        'home' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
        'activity' => 'M22 12h-4l-3 9L9 3l-3 9H2',
        'trending' => 'M22 7l-8.5 8.5-5-5L2 17M16 7h6v6',
        'user-circle' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6.2 19c1-2.4 3.2-3.5 5.8-3.5s4.8 1.1 5.8 3.5',
        'user' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
        'zap' => 'M13 2 3 14h9l-1 8 10-12h-9z',
        'box' => 'M21 8 12 3 3 8v8l9 5 9-5zM3 8l9 5 9-5M12 13v8',
        'layers' => 'M12 2 2 7l10 5 10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
        'history' => 'M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7v5l3 2',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
        'network' => 'M12 3a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM5 15a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM19 15a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM12 9v3M5 15v-3h14v3',
        'chart' => 'M3 3v18h18M7 16v-4M12 16V8M17 16v-7',
        'trophy' => 'M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3',
        'gift' => 'M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7z',
        'download' => 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3',
        'transfer' => 'M17 3l4 4-4 4M21 7H3M7 21l-4-4 4-4M3 17h18',
        'wallet' => 'M19 7V5a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4M21 12h-4a2 2 0 0 0 0 4h4zM3 6v12a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-3',
        'card' => 'M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zM2 10h20',
        'dollar' => 'M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
        'headset' => 'M3 18v-6a9 9 0 0 1 18 0v6M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z',
        'ticket' => 'M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2zM13 5v2M13 17v2M13 11v2',
        'calculator' => 'M6 2h12a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM8 6h8v4H8zM8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01',
        'logout' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'x' => 'M18 6 6 18M6 6l12 12',
        'copy' => 'M9 9h11v11H9zM5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3',
        'clock' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 6v6l4 2',
        'check-circle' => 'M22 11.1V12a10 10 0 1 1-5.9-9.1M22 4 12 14l-3-3',
        'circle' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z',
        'inbox' => 'M22 12h-6l-2 3h-4l-2-3H2M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.7 1.1z',
        'chevron-left' => 'm15 18-6-6 6-6',
        'chevron-right' => 'm9 18 6-6-6-6',
        'alert' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 8v4M12 16h.01',
        'link' => 'M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7',
        'sparkle' => 'M12 3l1.9 5.6L19.5 10.5l-5.6 1.9L12 18l-1.9-5.6L4.5 10.5l5.6-1.9zM19 3v4M21 5h-4',
    ];
    return sprintf(
        '<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<path d="%s"/></svg>',
        e($class),
        $size,
        $size,
        $paths[$name] ?? $paths['alert']
    );
}

/** @return list<string> */
function countries(): array
{
    return [
        'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia',
        'Australia', 'Austria', 'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium',
        'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria',
        'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia', 'Cameroon', 'Canada', 'Central African Republic', 'Chad',
        'Chile', 'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica', "Cote d'Ivoire", 'Croatia', 'Cuba', 'Cyprus',
        'Czechia', 'Democratic Republic of the Congo', 'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic',
        'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia', 'Fiji',
        'Finland', 'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala',
        'Guinea', 'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran',
        'Iraq', 'Ireland', 'Israel', 'Italy', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati',
        'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein',
        'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta',
        'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia', 'Moldova', 'Monaco', 'Mongolia',
        'Montenegro', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'New Zealand',
        'Nicaragua', 'Niger', 'Nigeria', 'North Korea', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau',
        'Palestine', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar',
        'Romania', 'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines',
        'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles',
        'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa',
        'South Korea', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Sweden', 'Switzerland', 'Syria',
        'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago',
        'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine', 'United Arab Emirates',
        'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela',
        'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe',
    ];
}
