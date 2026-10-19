<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Ticket;

/**
 * Support tickets: read and reply (member sees the reply on their Support page).
 */
final class TicketController
{
    private const TABS = ['Open' => 'Open', 'Replied' => 'Replied', 'all' => 'All'];

    public function index(): void
    {
        $status = query_choice('status', array_keys(self::TABS), 'Open');
        view('admin/tickets', [
            'title' => 'Tickets',
            'tabs' => filter_tabs('/admin/tickets', self::TABS, $status),
            'pager' => Ticket::paginate($status === 'all' ? null : $status, page_param()),
        ], 'admin');
    }

    public function reply(): void
    {
        $reply = input('reply');
        if (mb_strlen($reply) < 2 || mb_strlen($reply) > 1000) {
            toast('Reply must be 2-1000 characters.', 'error');
        } else {
            Ticket::reply((int) input('id'), $reply);
            toast('Reply sent.');
        }
        redirect('/admin/tickets');
    }
}
