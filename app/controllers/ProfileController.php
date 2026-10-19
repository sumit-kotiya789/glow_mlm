<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Ticket;

/**
 * Profile and support tickets.
 */
final class ProfileController
{
    public function show(): void
    {
        $user = Auth::user();
        view('profile', [
            'title' => 'My Profile',
            'items' => [
                ['Sponsor ID', $user['sponsor_code'] ?? '—'],
                ['My Name', $user['name']],
                ['My User ID', $user['user_code']],
                ['Status', $user['activated_at'] ? 'Active since ' . fmt_date($user['activated_at']) : 'Inactive (no investment yet)'],
                ['Joining', fmt_date($user['created_at'])],
                ['Email', $user['email']],
                ['Mobile', $user['mobile']],
                ['Country', $user['country']],
            ],
            'wallet' => $user['wallet'],
        ]);
    }

    public function support(): void
    {
        $pager = Ticket::paginateForUser((int) Auth::id(), page_param());
        $rows = array_map(static fn ($t) => [
            'id' => '#' . str_pad((string) $t['id'], 4, '0', STR_PAD_LEFT),
            'date' => fmt_date($t['created_at'], 'd-M-Y H:i'),
            'subject' => $t['subject'],
            'status' => [$t['status'], status_class($t['status'])],
            'reply' => $t['admin_reply'] ?? '—',
        ], $pager['rows']);

        view('support', [
            'title' => 'Support',
            'columns' => ['id' => 'Ticket', 'date' => 'Date', 'subject' => 'Subject', 'status' => 'Status', 'reply' => 'Reply'],
            'rows' => $rows,
            'pager' => $pager,
        ]);
    }

    public function createTicket(): void
    {
        $subject = input('subject');
        $message = input('message');
        $errors = [];
        if (mb_strlen($subject) < 3 || mb_strlen($subject) > 100) {
            $errors['subject'] = 'Subject must be 3-100 characters.';
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 1000) {
            $errors['message'] = 'Message must be 10-1000 characters.';
        }
        if ($errors) {
            fail('/support', $errors);
        }
        Ticket::create((int) Auth::id(), $subject, $message);
        toast('Ticket created. The admin will reply here.');
        redirect('/support');
    }
}
