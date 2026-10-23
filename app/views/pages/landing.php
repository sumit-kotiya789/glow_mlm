<?php

use App\Models\PlanTerm;
use App\Models\Setting;

/**
 * @var list<array> $terms
 * @var list<float> $levelPcts
 */
$n = static fn (string $key): float => Setting::num($key);
$dailyRate = $terms ? PlanTerm::dailyRate($terms[0]) : 5;
$feePct = $n('withdraw_fee_pct');
$register = e(url('/register'));
$login = e(url('/login'));
$maxLevel = max($levelPcts) ?: 1;
$banners = [
    ['b1', 'Fixed Deposit', 'Lock it. Let it grow.', 'Choose 25, 50 or 100 days at ' . pct($dailyRate) . ' a day. Everything pays out at maturity.', 'layers'],
    ['b2', 'Compound Plan', 'Reinvest daily, stack faster.', 'Compound each day\'s part back into principal, or move it to your wallet whenever you like.', 'trending'],
    ['b3', 'Team Income', 'Ten levels deep.', pct($n('direct_pct')) . ' direct sponsor income plus level income and daily ROI-on-ROI from your network.', 'network'],
    ['b4', 'Monthly Salary', money($n('salary_amount')) . ' x ' . (int) $n('salary_months') . ' months.', 'Hit the qualifying targets in your first ' . (int) $n('salary_window_days') . ' days and get paid every month.', 'wallet'],
    ['b5', 'Lucky Draw', money($n('lucky_prize')) . ' for ' . (int) $n('lucky_winners') . ' winners.', 'Active investors enter every admin-run draw automatically.', 'trophy'],
];
$tags = [25 => 'Starter term', 50 => 'Balanced', 100 => 'Longest term'];
?>
<div class="lp-bg" aria-hidden="true">
    <span class="lp-bg__grid"></span>
    <span class="lp-bg__orb lp-bg__orb--a"></span>
    <span class="lp-bg__orb lp-bg__orb--b"></span>
    <span class="lp-bg__orb lp-bg__orb--c"></span>
</div>

<header class="lp-nav" id="lp-nav">
    <a class="lp-nav__brand" href="<?= e(url('/')) ?>" aria-label="Digital Money Earn"><img src="<?= e(asset('img/logo.png')) ?>" alt="Digital Money Earn" width="64" height="58"></a>
    <nav class="lp-nav__links" aria-label="Sections">
        <a href="#how">How it works</a>
        <a href="#plans">Plans</a>
        <a href="#income">Income</a>
        <a href="#salary">Salary</a>
        <a href="#faq">FAQ</a>
    </nav>
    <a class="btn btn--ghost lp-nav__login" href="<?= $login ?>">Login</a>
    <a class="btn btn--gold lp-nav__reg" href="<?= $register ?>">Register</a>
</header>

<section class="lp-ticker ticker" aria-label="Live prices of top cryptocurrencies" data-ticker>
    <div class="ticker__head">
        <h2 class="ticker__title"><span class="lp-live" aria-hidden="true"></span> Live Crypto Prices</h2>
        <span class="ticker__status" data-ticker-status>Loading…</span>
    </div>
    <div class="ticker__row"><div class="ticker__track" data-ticker-track></div></div>
</section>

<main id="main">
    <section class="lp-hero">
        <div class="lp-hero__copy">
            <p class="lp-eyebrow"><span class="lp-pulse" aria-hidden="true"></span> Digital Money Earn</p>
            <h1>Grow your crypto with a <span class="lp-shine">transparent</span> wealth network.</h1>
            <p class="lp-lead">Pick a plan, watch daily payouts land in your wallet, and build income from a 10-level referral team, all from one simple dashboard.</p>
            <div class="lp-cta">
                <a class="btn btn--gold btn--lg lp-btn-glow" href="<?= $register ?>">Start earning</a>
                <a class="btn btn--ghost btn--lg" href="#plans">View plans</a>
            </div>
            <ul class="lp-trust" aria-label="Highlights">
                <li><?= icon('check-circle', 18) ?> USDT (BEP-20) deposits</li>
                <li><?= icon('check-circle', 18) ?> Daily payouts, 00:00 IST</li>
                <li><?= icon('check-circle', 18) ?> Full wallet ledger</li>
            </ul>
        </div>
        <div class="lp-hero__art" aria-hidden="true">
            <span class="lp-ring lp-ring--1"></span><span class="lp-ring lp-ring--2"></span>
            <img class="lp-hero__logo" src="<?= e(asset('img/logo.png')) ?>" alt="" width="480" height="436">
            <span class="lp-chip lp-chip--a"><?= icon('trending', 16) ?> Daily ROI</span>
            <span class="lp-chip lp-chip--b"><?= icon('network', 16) ?> 10 levels</span>
            <span class="lp-chip lp-chip--c"><?= icon('dollar', 16) ?> USDT</span>
            <span class="lp-coin lp-coin--1">$</span><span class="lp-coin lp-coin--2">$</span><span class="lp-coin lp-coin--3">$</span><span class="lp-coin lp-coin--4">$</span>
        </div>
    </section>

    <section class="lp-stats" aria-label="Key numbers">
        <div data-reveal style="--i:0"><b data-count="<?= e($dailyRate) ?>" data-suffix="%" data-dec="<?= fmod($dailyRate, 1) ? 1 : 0 ?>"><?= e(pct($dailyRate)) ?></b><span>Daily plan rate</span></div>
        <div data-reveal style="--i:1"><b data-count="3">3</b><span>Plan terms</span></div>
        <div data-reveal style="--i:2"><b data-count="<?= e($n('min_invest')) ?>" data-prefix="$"><?= e(money($n('min_invest'))) ?></b><span>Minimum investment</span></div>
        <div data-reveal style="--i:3"><b data-count="10">10</b><span>Referral levels</span></div>
    </section>

    <section class="lp-section lp-section--tight" aria-label="Featured offers">
        <div class="lp-slider" data-slider data-autoplay="5500" aria-roledescription="carousel" aria-label="Featured offers">
            <div class="lp-slider__track" data-track>
                <?php foreach ($banners as $i => [$cls, $tag, $head, $text, $ico]) : ?>
                    <article class="lp-banner lp-banner--<?= e($cls) ?>" aria-roledescription="slide" aria-label="<?= e($i + 1) ?> of <?= e(count($banners)) ?>">
                        <div class="lp-banner__copy">
                            <p class="lp-banner__tag"><?= e($tag) ?></p>
                            <h3><?= e($head) ?></h3>
                            <p><?= e($text) ?></p>
                            <a class="btn btn--gold" href="<?= $register ?>">Join now</a>
                        </div>
                        <div class="lp-banner__art" aria-hidden="true">
                            <span class="lp-banner__glow"></span>
                            <?= icon($ico, 150, 'icon lp-banner__icon') ?>
                            <span class="lp-orbit lp-orbit--1"></span><span class="lp-orbit lp-orbit--2"></span>
                            <span class="lp-coin lp-coin--s1">$</span><span class="lp-coin lp-coin--s2">$</span>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
            <button class="lp-slider__btn lp-slider__btn--prev" type="button" data-prev aria-label="Previous slide"><?= icon('chevron-left', 22) ?></button>
            <button class="lp-slider__btn lp-slider__btn--next" type="button" data-next aria-label="Next slide"><?= icon('chevron-right', 22) ?></button>
            <div class="lp-slider__dots" data-dots role="tablist" aria-label="Choose slide"></div>
        </div>
    </section>

    <section class="lp-section" id="how">
        <p class="lp-kicker" data-reveal>Getting started</p>
        <h2 data-reveal>How it works</h2>
        <ol class="lp-steps">
            <li data-reveal style="--i:0"><span class="lp-steps__n">1</span><span class="lp-steps__ico"><?= icon('user', 24) ?></span><h3>Register</h3><p>Sign up with a sponsor's code and get your member ID in seconds. <?php if ($n('signup_bonus_enabled')) : ?>Your first approved deposit earns a <?= e(money($n('signup_bonus_amount'))) ?> signup bonus.<?php endif ?></p></li>
            <li data-reveal style="--i:1"><span class="lp-steps__n">2</span><span class="lp-steps__ico"><?= icon('download', 24) ?></span><h3>Deposit USDT</h3><p>Send USDT (BEP-20) to the deposit address, submit the amount and hash, and an admin approves it. Minimum <?= e(money($n('min_deposit'))) ?>.</p></li>
            <li data-reveal style="--i:2"><span class="lp-steps__n">3</span><span class="lp-steps__ico"><?= icon('layers', 24) ?></span><h3>Choose a plan</h3><p>Invest <?= e(money($n('min_invest'))) ?> to <?= e(money($n('max_invest'))) ?> per plan in Compound or Fixed Deposit for 25, 50 or 100 days.</p></li>
            <li data-reveal style="--i:3"><span class="lp-steps__n">4</span><span class="lp-steps__ico"><?= icon('wallet', 24) ?></span><h3>Earn &amp; withdraw</h3><p>Daily payouts and team income reach your wallets. Request a withdrawal and an admin sends it.</p></li>
        </ol>
    </section>

    <section class="lp-section" id="plans">
        <p class="lp-kicker" data-reveal>Investment plans</p>
        <h2 data-reveal>Choose your term</h2>
        <div class="lp-slider lp-slider--plans" data-slider aria-roledescription="carousel" aria-label="Plan terms">
            <div class="lp-slider__track" data-track>
                <?php foreach ($terms as $i => $term) :
                    $rate = PlanTerm::dailyRate($term);
                    $days = (int) $term['days']; ?>
                    <article class="lp-plan<?= $days === 50 ? ' is-featured' : '' ?>" data-tilt data-reveal style="--i:<?= e($i) ?>" aria-roledescription="slide">
                        <?php if (isset($tags[$days])) : ?><span class="lp-plan__tag"><?= e($tags[$days]) ?></span><?php endif ?>
                        <p class="lp-plan__days"><b><?= e($days) ?></b> days</p>
                        <p class="lp-plan__rate"><?= e(pct($rate)) ?> <small>per day</small></p>
                        <svg class="lp-plan__spark" viewBox="0 0 200 50" preserveAspectRatio="none" aria-hidden="true"><path d="M0 44 C30 42 40 30 70 32 S110 18 135 14 S180 8 200 3"/></svg>
                        <ul>
                            <li><?= icon('check-circle', 16) ?> <?= e(pct((float) $term['profit_rate'])) ?> profit part daily</li>
                            <li><?= icon('check-circle', 16) ?> <?= e(pct((float) $term['principal_rate'])) ?> principal part daily</li>
                            <li><?= icon('check-circle', 16) ?> From <?= e(money($n('min_invest'))) ?> to <?= e(money($n('max_invest'))) ?></li>
                            <li><?= icon('check-circle', 16) ?> Compound or Fixed</li>
                        </ul>
                        <a class="btn <?= $days === 50 ? 'btn--gold' : 'btn--ghost' ?> btn--block" href="<?= $register ?>">Choose <?= e($days) ?> days</a>
                    </article>
                <?php endforeach ?>
            </div>
            <div class="lp-slider__dots lp-slider__dots--mobile" data-dots role="tablist" aria-label="Choose plan"></div>
        </div>

        <div class="lp-split">
            <article class="lp-card" data-reveal>
                <h3><?= icon('trending', 20) ?> Compound</h3>
                <p>Each day <?= e(pct($dailyRate)) ?> of your principal is credited: 1% profit goes straight to your ROI wallet, the rest builds up as pending balance.</p>
                <ul>
                    <li>Compound the latest day's part back into principal.</li>
                    <li>Or move any pending amount to your wallet and withdraw it.</li>
                    <li>At maturity, all pending plus anything compounded on top is released.</li>
                </ul>
            </article>
            <article class="lp-card" data-reveal style="--i:1">
                <h3><?= icon('layers', 20) ?> Fixed Deposit</h3>
                <p>Lock your amount for the term at the same daily rate. ROI accrues every day and stays locked until the plan matures.</p>
                <ul>
                    <li>Simple and hands-off, nothing to manage.</li>
                    <li>Everything is paid to your FD ROI wallet at maturity.</li>
                    <li>Example: $100 for 25 days at <?= e(pct($dailyRate)) ?> pays $<?= e(number_format(100 + 100 * $dailyRate / 100 * 25, 0)) ?>.</li>
                </ul>
            </article>
        </div>

        <form class="lp-calc" id="lp-calc" data-reveal data-rate="<?= e($dailyRate) ?>" onsubmit="return false">
            <div class="lp-calc__form">
                <h3>Fixed Deposit estimate</h3>
                <label>Amount (USDT)<input class="input" id="lp-amt" type="number" min="<?= e($n('min_invest')) ?>" max="<?= e($n('max_invest')) ?>" step="1" value="100" inputmode="decimal"></label>
                <label>Term<select class="input" id="lp-days">
                    <?php foreach ($terms as $term) : ?><option value="<?= e($term['days']) ?>"><?= e($term['days']) ?> days</option><?php endforeach ?>
                </select></label>
                <p class="lp-calc__out" aria-live="polite">You would receive <b id="lp-out">$225.00</b> at maturity.</p>
                <p class="lp-note">Illustration only, not a promise of returns.</p>
            </div>
            <div class="lp-calc__chart" aria-hidden="true">
                <svg viewBox="0 0 320 160" preserveAspectRatio="none">
                    <defs><linearGradient id="lp-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d4af37" stop-opacity=".45"/><stop offset="1" stop-color="#d4af37" stop-opacity="0"/></linearGradient></defs>
                    <g class="lp-calc__grid"><path d="M0 40H320M0 80H320M0 120H320"/></g>
                    <path id="lp-area-p" fill="url(#lp-area)" d=""/>
                    <path id="lp-line-p" class="lp-calc__line" fill="none" d=""/>
                </svg>
                <span class="lp-calc__lbl lp-calc__lbl--l">Day 0</span><span class="lp-calc__lbl lp-calc__lbl--r" id="lp-end">Day 25</span>
            </div>
        </form>
    </section>

    <section class="lp-section" id="income">
        <p class="lp-kicker" data-reveal>Referral network</p>
        <h2 data-reveal>Six ways to earn</h2>
        <div class="lp-feats">
            <?php foreach ([
                ['activity', 'Plan ROI', 'Daily payouts on every running plan for its full term.'],
                ['user-circle', 'Direct sponsor', pct($n('direct_pct')) . ' of every investment your direct referrals make.'],
                ['users', 'Level income', pct($n('level_deposit_pct')) . ' of investments from levels 2 to 10 of your team.'],
                ['network', 'ROI-on-ROI', 'A daily share of your team\'s compound-plan payouts, down 10 levels.'],
                ['wallet', 'Monthly salary', money($n('salary_amount')) . ' a month for ' . (int) $n('salary_months') . ' months once you qualify.'],
                ['trophy', 'Lucky draw', 'Admin-run draws pay a ' . money($n('lucky_prize')) . ' prize to ' . (int) $n('lucky_winners') . ' winners.'],
            ] as $i => [$ico, $h, $t]) : ?>
                <article class="lp-feat" data-tilt data-reveal style="--i:<?= e($i) ?>"><span class="lp-feat__ico"><?= icon($ico, 26) ?></span><h3><?= e($h) ?></h3><p><?= e($t) ?></p></article>
            <?php endforeach ?>
        </div>

        <h3 class="lp-sub" data-reveal>ROI-on-ROI by level</h3>
        <div class="lp-bars" data-reveal>
            <?php foreach ($levelPcts as $i => $p) : ?>
                <div class="lp-bar" style="--w:<?= e(round($p / $maxLevel * 100)) ?>%; --i:<?= e($i) ?>">
                    <span class="lp-bar__lbl">Level <?= e($i + 1) ?></span>
                    <span class="lp-bar__track"><i></i></span>
                    <b><?= e(pct($p)) ?></b>
                    <small>needs <?= e($i + 1) ?> active direct<?= $i ? 's' : '' ?></small>
                </div>
            <?php endforeach ?>
        </div>
        <p class="lp-note">To earn from level N you need your own running plan and at least N active directs (referrals with a running plan). Income you miss is not passed up the line.</p>
    </section>

    <section class="lp-section" id="salary">
        <p class="lp-kicker" data-reveal>Monthly salary</p>
        <h2 data-reveal>Qualify once, get paid for a year</h2>
        <p class="lp-lead" data-reveal>Meet all three conditions within <?= e((int) $n('salary_window_days')) ?> days of registering.</p>
        <div class="lp-rings">
            <?php foreach ([
                [money($n('salary_min_self')), 'Your own investment', 'Invest at least this much yourself.'],
                [(string) (int) $n('salary_directs'), 'Active directs', 'Personally refer members who have a running plan.'],
                [(string) (int) $n('salary_level2'), 'Active level-2 members', 'Your team\'s referrals who also have running plans.'],
            ] as $i => [$val, $h, $t]) : ?>
                <div class="lp-ringcard" data-reveal style="--i:<?= e($i) ?>">
                    <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="lp-ringcard__bg" cx="60" cy="60" r="52"/><circle class="lp-ringcard__fg" cx="60" cy="60" r="52" pathLength="100"/></svg>
                    <b><?= e($val) ?></b>
                    <h3><?= e($h) ?></h3><p><?= e($t) ?></p>
                </div>
            <?php endforeach ?>
        </div>
        <p class="lp-note"><?= e(money($n('salary_amount'))) ?> per month for <?= e((int) $n('salary_months')) ?> months, paid on the same day for everyone, even if you later withdraw your investments.</p>
    </section>

    <section class="lp-section" id="wallets">
        <p class="lp-kicker" data-reveal>Your money</p>
        <h2 data-reveal>Four wallets, full clarity</h2>
        <div class="lp-feats lp-feats--4">
            <?php foreach ([
                ['download', 'Deposit', 'Approved deposits. Used for investing, or sent to another member\'s Deposit wallet.'],
                ['trending', 'Compound ROI', 'Profit and maturity payouts from Compound plans. Withdrawable.'],
                ['layers', 'FD ROI', 'Fixed Deposit maturity payouts. Withdrawable.'],
                ['dollar', 'Income', 'Referral, salary, lucky draw and bonus income. Withdrawable.'],
            ] as $i => [$ico, $h, $t]) : ?>
                <article class="lp-feat" data-tilt data-reveal style="--i:<?= e($i) ?>"><span class="lp-feat__ico"><?= icon($ico, 26) ?></span><h3><?= e($h) ?></h3><p><?= e($t) ?></p></article>
            <?php endforeach ?>
        </div>
        <ul class="lp-list" data-reveal>
            <li><?= icon('check-circle', 18) ?> Withdraw <?= e(money($n('min_withdraw'))) ?> to <?= e(money($n('max_withdraw'))) ?> per request<?= $feePct > 0 ? ', with a ' . e(pct($feePct)) . ' fee' : ', with no fee' ?>.</li>
            <li><?= icon('check-circle', 18) ?> Your daily withdrawal limit follows your total active investment.</li>
            <li><?= icon('check-circle', 18) ?> Move any ROI or Income wallet into your Deposit wallet instantly to reinvest, no fee.</li>
            <li><?= icon('check-circle', 18) ?> Every credit and debit is recorded in your ledger so you can trace it.</li>
        </ul>
    </section>

    <section class="lp-section" id="faq">
        <p class="lp-kicker" data-reveal>Questions</p>
        <h2 data-reveal>FAQ</h2>
        <div class="lp-faq">
            <details data-reveal><summary>When are payouts made?</summary><p>Every day at 00:00 (Asia/Kolkata), weekends included.</p></details>
            <details data-reveal style="--i:1"><summary>Do I get my invested amount back?</summary><p>On a Compound plan the invested amount itself is not returned: you receive the pending daily principal parts plus anything you compounded. On a Fixed Deposit you receive the principal plus the locked ROI at maturity.</p></details>
            <details data-reveal style="--i:2"><summary>What makes my ID active?</summary><p>Your first investment. Active status also counts toward your sponsor's level income and salary conditions.</p></details>
            <details data-reveal style="--i:3"><summary>How are deposits and withdrawals processed?</summary><p>An admin reviews each request. Deposits are credited on approval; withdrawals are sent by the admin and marked Paid, or returned to your wallet if rejected.</p></details>
            <details data-reveal style="--i:4"><summary>Can I send money to another member?</summary><p>Yes. Members with at least one approved deposit can send from their Deposit wallet to another member's by User ID. The signup bonus can be invested but not sent.</p></details>
            <details data-reveal style="--i:5"><summary>Are returns guaranteed?</summary><p>No. Figures on this page are plan rules and illustrations. Only invest what you can afford to lose.</p></details>
        </div>
    </section>

    <section class="lp-final" data-reveal>
        <span class="lp-coin lp-coin--f1" aria-hidden="true">$</span><span class="lp-coin lp-coin--f2" aria-hidden="true">$</span>
        <h2>Ready to start?</h2>
        <p class="lp-lead">Create your account in under a minute.</p>
        <div class="lp-cta">
            <a class="btn btn--gold btn--lg lp-btn-glow" href="<?= $register ?>">Register now</a>
            <a class="btn btn--ghost btn--lg" href="<?= $login ?>">I already have an account</a>
        </div>
    </section>
</main>

<footer class="lp-foot">
    <img src="<?= e(asset('img/logo.png')) ?>" alt="" width="64" height="58">
    <p>&copy; <?= date('Y') ?> Digital Money Earn. Returns are not guaranteed; participate only with money you can afford to lose.</p>
</footer>

<div class="lp-mcta" id="lp-mcta">
    <a class="btn btn--ghost" href="<?= $login ?>">Login</a>
    <a class="btn btn--gold" href="<?= $register ?>">Register</a>
</div>
