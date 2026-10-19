<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\AdminAuth;

/**
 * Admin login / logout.
 */
final class AuthController
{
    public function showLogin(): void
    {
        view('admin/login', ['title' => 'Admin Login'], 'guest');
    }

    public function login(): void
    {
        if (!AdminAuth::attempt(input('username'), (string) ($_POST['password'] ?? ''))) {
            fail('/admin/login', ['password' => 'Invalid username or password.']);
        }
        toast('Welcome, ' . AdminAuth::admin()['name'] . '.');
        redirect('/admin');
    }

    public function logout(): void
    {
        AdminAuth::logout();
        toast('Admin logged out.');
        redirect('/admin/login');
    }
}
