<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\LuckyDraw;
use App\Models\Setting;
use App\Models\User;
use DomainException;

/**
 * Lucky draws: create, hand-pick favourites, random fill, distribute. Rules: docs/RULES.md section 8.
 */
final class LuckyDrawController
{
    public function index(): void
    {
        view('admin/lucky-draws', [
            'title' => 'Lucky Draws',
            'pager' => LuckyDraw::paginate(page_param()),
            'defaultPrize' => Setting::get('lucky_prize'),
            'defaultWinners' => Setting::get('lucky_winners'),
        ], 'admin');
    }

    public function create(): void
    {
        $title = input('title');
        $prize = input('prize');
        $count = input('winners_count');
        $errors = [];
        if (mb_strlen($title) < 3 || mb_strlen($title) > 80) {
            $errors['title'] = 'Title must be 3-80 characters.';
        }
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $prize) || (float) $prize <= 0) {
            $errors['prize'] = 'Prize must be above $0.';
        }
        if (!preg_match('/^\d{1,4}$/', $count) || (int) $count < 1) {
            $errors['winners_count'] = 'Winners must be 1-9999.';
        }
        if ($errors) {
            fail('/admin/lucky-draws', $errors);
        }
        $id = LuckyDraw::create($title, (float) $prize, (int) $count);
        toast('Draw created. Pick your favourites, then fill the rest randomly.');
        redirect('/admin/lucky-draw?id=' . $id);
    }

    public function show(): void
    {
        $draw = LuckyDraw::find((int) ($_GET['id'] ?? 0));
        if ($draw === null) {
            http_response_code(404);
            view('404', ['title' => 'Not Found'], 'admin');
            return;
        }
        $query = trim((string) ($_GET['q'] ?? ''));
        view('admin/lucky-draw', [
            'title' => $draw['title'],
            'draw' => $draw,
            'winners' => LuckyDraw::winners((int) $draw['id']),
            'query' => $query,
            'results' => $query !== '' && $draw['status'] === 'Draft' ? array_slice(User::search($query, 1)['rows'], 0, 8) : [],
        ], 'admin');
    }

    public function add(): void
    {
        $drawId = (int) input('draw_id');
        $this->act(static fn () => LuckyDraw::addWinner($drawId, (int) input('user_id')), 'Winner added.');
        redirect('/admin/lucky-draw?' . http_build_query(['id' => $drawId, 'q' => input('q')]));
    }

    public function remove(): void
    {
        $drawId = (int) input('draw_id');
        $this->act(static fn () => LuckyDraw::removeWinner($drawId, (int) input('user_id')), 'Winner removed.');
        redirect('/admin/lucky-draw?id=' . $drawId);
    }

    public function fill(): void
    {
        $drawId = (int) input('draw_id');
        try {
            $added = LuckyDraw::fillRandom($drawId);
            toast($added ? "$added winners picked at random from active investors." : 'No slots left or no eligible members.');
        } catch (DomainException $e) {
            toast($e->getMessage(), 'error');
        }
        redirect('/admin/lucky-draw?id=' . $drawId);
    }

    public function distribute(): void
    {
        $drawId = (int) input('draw_id');
        try {
            $paid = LuckyDraw::distribute($drawId);
            toast("Prize paid to $paid winners' income wallets.");
        } catch (DomainException $e) {
            toast($e->getMessage(), 'error');
        }
        redirect('/admin/lucky-draw?id=' . $drawId);
    }

    private function act(callable $fn, string $success): void
    {
        try {
            $fn();
            toast($success);
        } catch (DomainException $e) {
            toast($e->getMessage(), 'error');
        }
    }
}
