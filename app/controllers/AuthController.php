<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Models\Setting;
use App\Models\User;

/**
 * Login, register, forgot-password stub, logout.
 */
final class AuthController
{
    public function showLogin(): void
    {
        view('login', ['title' => 'Login'], 'guest');
    }

    public function login(): void
    {
        $code = strtoupper(input('user_code'));
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];
        if ($code === '') {
            $errors['user_code'] = 'User ID is required.';
        }
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }
        if (!$errors && !Auth::attempt($code, $password, !empty($_POST['remember']))) {
            $errors['password'] = 'Invalid User ID or password.';
        }
        if ($errors) {
            fail('/login', $errors);
        }
        toast('Welcome back, ' . Auth::user()['name'] . '!');
        redirect('/dashboard');
    }

    public function showRegister(): void
    {
        view('register', ['title' => 'Register', 'countries' => countries()], 'guest');
    }

    public function register(): void
    {
        $data = [
            'sponsor_code' => strtoupper(input('sponsor_code')),
            'name' => input('name'),
            'country' => input('country'),
            'email' => input('email'),
            'mobile' => preg_replace('/[\s-]/', '', input('mobile')),
            'wallet' => input('wallet'),
            'password' => (string) ($_POST['password'] ?? ''),
            'txn_password' => password_input('txn_password'),
        ];

        $errors = [];
        $sponsor = preg_match('/^GL\d{7}$/', $data['sponsor_code']) ? User::findByCode($data['sponsor_code']) : null;
        if (!preg_match('/^GL\d{7}$/', $data['sponsor_code'])) {
            $errors['sponsor_code'] = 'Sponsor ID looks like GL0000001.';
        } elseif ($sponsor === null || (int) $sponsor['is_blocked'] === 1) {
            $errors['sponsor_code'] = 'Sponsor ID not found. Try GL0000001.';
        }
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 80) {
            $errors['name'] = 'Enter your full name (2-80 characters).';
        }
        if (!in_array($data['country'], countries(), true)) {
            $errors['country'] = 'Select your country.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 120) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (!preg_match('/^\+?\d{7,15}$/', $data['mobile'])) {
            $errors['mobile'] = 'Enter 7-15 digits, optional leading +.';
        }
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $data['wallet'])) {
            $errors['wallet'] = 'Wallet must be 0x followed by 40 hex characters.';
        }
        if (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if (strlen($data['txn_password']) < 6 || strlen($data['txn_password']) > 64) {
            $errors['txn_password'] = 'Transaction password must be 6-64 characters.';
        } elseif ($data['txn_password'] === $data['password']) {
            $errors['txn_password'] = 'Transaction password must be different from your login password.';
        }
        if (empty($_POST['terms'])) {
            $errors['terms'] = 'Please accept the terms of service.';
        }
        if ($errors) {
            fail('/register', $errors);
        }

        [$code, $bonus] = Database::transaction(static function () use ($data, $sponsor): array {
            $code = User::create($data, (int) $sponsor['id']);
            return [$code, User::grantSignupBonus((int) User::findByCode($code)['id'])];
        });
        flash('old', ['user_code' => $code]);
        toast('Account created. Your User ID is ' . $code
            . ($bonus ? '. Welcome bonus ' . money(Setting::num('signup_bonus_amount')) . ' added to your Deposit wallet.' : ''));
        redirect('/login');
    }

    public function forgot(): void
    {
        view('forgot-password', ['title' => 'Forgot Password'], 'guest');
    }

    public function logout(): void
    {
        Auth::logout();
        toast('You have been logged out.');
        redirect('/login');
    }
}
