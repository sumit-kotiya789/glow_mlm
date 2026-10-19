# GLOW — Business Rules (locked 15-Oct-2026)

no real payments or blockchain calls. Deposit address is a placeholder, tx hashes are stored
but never verified, withdrawals are "paid" by an admin clicking a status button.
Every number marked (cfg) is an admin setting.

## 1. Wallets

| Wallet  | Receives                                                    | Used for       |
| ------- | ----------------------------------------------------------- | -------------- |
| Deposit | Admin-approved deposits                                     | Investing only |
| ROI     | Daily 1% profit, plan maturity payouts                      | Withdrawal     |
| Income  | Direct, level, ROI-on-ROI, lucky draw, salary, signup bonus | Withdrawal     |

Allowed moves are in section 9a. Nothing else moves between wallets or users.

## 2. Deposit

1. User sees admin USDT (BEP-20) address + QR (cfg), submits amount + tx hash -> **Pending**.
2. Admin approves (credit Deposit wallet) or rejects (with reason).
3. Signup bonus $5 (cfg) on the user's **first approved deposit**, only while admin switch is ON.

## 3. Investing

- User picks plan type (**Compound** or **Fixed Deposit**), term (**25 / 50 / 100 days**, cfg) and amount
  (**$5–$1000 per investment**, cfg). Multiple plans may run at once. Paid from Deposit wallet.
- Daily rate per term (cfg, default 5% = 4% principal part + 1% profit), same for both plan types.
- User ID becomes **active** at their first investment.

## 4. Compound plan

Each daily payout credits 5% of current principal:

- **1% profit** -> ROI wallet automatically.
- **4% principal part** -> plan's pending balance. User may:
  - **Compound** only the **latest day's** part, until the next payout -> principal += part.
  - **Withdraw** any/all pending -> creates a withdrawal request directly; principal -= amount.
  - Do nothing -> principal unchanged, pending keeps growing.
- No compounding on the final day.
- At maturity: remaining principal + all pending -> ROI wallet, plan closes.

Example ($100): day 1 credit 4 + 1. Compound -> principal 104. Withdraw 4 -> principal 96.
Nothing for 3 days -> pending 12; can compound only today's 4 (-> 104, 8 stays withdraw-only)
or withdraw 12 (-> 88).

## 5. Fixed deposit

- Locked for the term, same daily rate. ROI accrues daily but stays locked.
- At maturity everything -> ROI wallet. $100 x 25 days @ 5% = $225.

## 6. Incomes

| Income         | Trigger                                                | Amount (cfg)              | Paid                       |
| -------------- | ------------------------------------------------------ | ------------------------- | -------------------------- |
| Direct sponsor | My level-1 downline **invests**                        | 10% of investment         | Every investment           |
| Level deposit  | My level 2–10 downline **invests**                     | 1% per level              | Every investment           |
| ROI-on-ROI     | Level 1–10 downline's daily 4% principal part credited | % per level (10 settings) | Daily, compound plans only |
| Lucky draw     | Admin runs a draw                                      | Prize + winner count      | When admin distributes     |
| Salary         | All salary conditions met                              | $100 / month x 12         | Monthly, same day for all  |
| Signup bonus   | First approved deposit while switch ON                 | $5                        | Once                       |

ROI-on-ROI is paid on the daily credit regardless of what the downline does with it.

## 7. Salary

Conditions, all met within **15 days of registration** (all cfg):

- Own investment >= $50
- 5 direct referrals with **active** IDs
- 10 level-2 referrals with **active** IDs

Payout $100 per month for 12 months on one admin-set day of month for all users.
Keeps paying even if the user later withdraws investments.

## 8. Lucky draw

1. Admin searches users and hand-picks favourites (e.g. 10).
2. System randomly fills the remaining slots (e.g. 90) from users with an **active investment**, no duplicates.
3. Admin clicks Distribute -> each winner gets the prize in Income wallet.

## 9. Withdrawal

- From ROI wallet, Income wallet, or a compound plan's pending principal.
- $1–$1000 per request (cfg). Daily total limit = user's total active investment.
  With no running plan, the limit = the plans that matured most recently (same maturity day), so a member
  whose plans finished can still withdraw up to that amount per day. Never invested = $0.
- Fee 0% (cfg).
- Request is **Pending**; admin sends funds manually and marks **Paid** (optional payout hash) or
  **Rejected** (amount returned to where it came from).

## 9a. Transfers (no fee, min $1 (cfg))

- **P2P:** a member sends from their **Deposit wallet** to another member's **Deposit wallet** by entering the
  receiver's User ID (e.g. GL0000001). The form shows the receiver's masked name to confirm the ID.
  Only members with **at least one approved deposit** may send (stops signup-bonus farming with fake accounts).
  Anyone can receive. Not to yourself, not to a blocked user.
  The **signup bonus can be invested but never sent**: sendable = Deposit wallet - unspent bonus
  (investments spend the bonus first). Approved deposits, received P2P and ROI/Income moved into
  the Deposit wallet are all sendable.
- **Wallet move:** a member moves their own **ROI** or **Income** wallet into their own **Deposit** wallet
  (to reinvest). Deposit wallet cannot move back out except by investing or P2P.
- Not a withdrawal: no daily-limit use, no admin approval, instant. Each move is two ledger rows:
  P2P sent / P2P received, Transfer out / Transfer in.

## 10. Daily payout

- Runs every day at 00:00 Asia/Kolkata (time cfg), weekends included.
- Admin "Run due payouts" button. Each date pays at most once.
- Testing mode (setting payout_period = hour): every "day" above becomes one hour (ROI hourly,
  25-day plan matures after 25 hours). Salary and the daily withdrawal limit stay on calendar days.
