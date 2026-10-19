<?php

declare(strict_types=1);

namespace App;

/**
 * Exact-match router with auth/guest guards and CSRF check on POST.
 */
final class Router
{
    /** @var array<string, array<string, array{0: array{0: class-string, 1: string}, 1: string}>> */
    private array $routes = [];

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param string $access 'auth' | 'guest' (members), 'admin' | 'admin-guest' (admins) or 'any'
     */
    public function get(string $path, array $handler, string $access = 'auth'): void
    {
        $this->routes['GET'][$path] = [$handler, $access];
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $path, array $handler, string $access = 'auth'): void
    {
        $this->routes['POST'][$path] = [$handler, $access];
    }

    public function dispatch(string $method, string $path): void
    {
        $route = $this->routes[$method === 'HEAD' ? 'GET' : $method][$path] ?? null;
        if ($route === null) {
            http_response_code(404);
            $admin = str_starts_with($path, '/admin');
            view('404', ['title' => 'Not Found'], match (true) {
                $admin && AdminAuth::check() => 'admin',
                !$admin && Auth::check() => 'app',
                default => 'guest',
            });
            return;
        }

        [[$class, $action], $access] = $route;

        if ($access === 'auth' && !Auth::check()) {
            redirect('/login');
        }
        if ($access === 'guest' && Auth::check()) {
            redirect('/dashboard');
        }
        if ($access === 'admin' && !AdminAuth::check()) {
            redirect('/admin/login');
        }
        if ($access === 'admin-guest' && AdminAuth::check()) {
            redirect('/admin');
        }
        if ($method === 'POST' && !Csrf::verify()) {
            toast('Session expired, please try again.', 'error');
            redirect(isset($this->routes['GET'][$path]) ? $path : (str_starts_with($path, '/admin') ? '/admin' : '/dashboard'));
        }

        (new $class())->$action();
    }
}
