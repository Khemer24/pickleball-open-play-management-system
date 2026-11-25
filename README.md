# RallyCourt

RallyCourt is an open-play manager for pickleball clubs. Approved Organizers own events and assign approved Managers to handle check-ins, courts, and scores. Players can view their sessions, match history, and personal results.

The responsive interface uses Tailwind CSS 4 with a deep-green, warm-white, and lime design system, plus Bootstrap Icons.

## Requirements

- Laragon with PHP 8.2+, MySQL 8+, Composer, and Node.js/npm
- PHP extensions required by Laravel, including `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, and `zip`

## Laragon setup

1. Copy the project into `C:\laragon\www\RallyCourt` (or add this folder as a Laragon virtual host).
2. Start Apache/Nginx and MySQL from Laragon. Create a MySQL database named `rallycourt` in HeidiSQL or phpMyAdmin.
3. In the project folder run:

   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   ```

4. Confirm the `.env` MySQL settings (`DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`) match your local database. Use `APP_URL=http://rallycourt.test` when using Laragon's automatic virtual hosts.
5. Install front-end assets, migrate, seed, and expose uploaded profile photos:

   ```powershell
   npm install
   php artisan migrate --seed
   php artisan storage:link
   npm run build
   ```

6. Open the Laragon project URL, or run `php artisan serve` and visit `http://127.0.0.1:8000`. For frontend development run `npm run dev`.

The seeded demo password is `password` for all demo accounts:

| Role | Email |
| --- | --- |
| Admin | `admin@rallycourt.test` |
| Approved manager | `manager@rallycourt.test` |
| Approved organizer | `organizer@rallycourt.test` |
| Players | `player01@rallycourt.test` through `player12@rallycourt.test` |

Email verification notifications use Laravel's `log` mail driver by default; inspect `storage/logs/laravel.log` for the local verification URL. Configure an SMTP mailer in `.env` for real email delivery.

## How it works

- New accounts verify email before accessing the app. Manager and Organizer applications are approved from **Role approvals** by an administrator.
- Organizers create events with a venue, date/time, skill level, singles or doubles format, optional player limit, named courts, and rules. They can add players or guests, assign approved Managers, correct scores before finalization, and lock the final standings.
- Assigned Managers can access their event workspaces to check players in or out, mark fees, run courts, and submit match scores. Organizers can pause the queue to finish an event without starting more matches.
- Matchups remain queued when there are no available courts. Adding a court starts the oldest queued matchup. A player can only occupy one queued or active match at a time.
- Managers create singles or doubles sessions, choose named courts, add registered players or guests, edit their session details, and can save player groups for reuse.
- Players can search sessions by name or creator. Sessions added to their roster open a read-only Players, Courts, and Ranks & History workspace; selecting a player shows their match history.
- Checking players in fills free courts automatically. Queue priority is fewest completed games, then earliest check-in; doubles partner/opponent history is used to rotate combinations.
- Score submission is transaction-locked and accepts a final, non-tied score once. It releases that court and fills newly available courts without changing other active matches. Finalized sessions cannot accept scores or roster changes.
- **Players played with** searches, sorts, and paginates only the signed-in user’s completed match history. Organizer accounts can also be added to rosters and see their own results.
- The public live board is available at the session's unique **Share live** URL. Player dashboards and event standings are derived from persisted completed match results.

The seeded Organizer event starts with five checked-in doubles players and no courts. Four players have an **Up next** matchup while one waits on the bench; adding a court starts that match.

## Tests

Run the application tests with:

```powershell
php artisan test
```

Feature tests cover player session access and match history, the 12-player, two-doubles-court manager workflow, and the Organizer acceptance flow: five checked-in players queue a doubles match without a court, adding a court starts it, and one score updates its four players exactly once.

## Vercel deployment (Container Runtime beta)

The repository includes a FrankenPHP container, Caddy configuration, and Vercel routing config. Import the GitHub repository into a Vercel project with Container Runtime access enabled. The container is stateless, so configure an external MySQL database and object storage before serving real users.

Set these Vercel environment variables for Production:

| Variable | Value |
| --- | --- |
| `APP_NAME` | `RallyCourt` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate with `php artisan key:generate --show`; add the output only in Vercel |
| `APP_URL` | Your deployed HTTPS URL |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Values from a managed MySQL provider (Aiven MySQL is one option) |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `FILESYSTEM_DISK` | `s3` |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | Cloudflare R2 S3 API credentials |
| `AWS_DEFAULT_REGION` | `auto` |
| `AWS_BUCKET` | Your R2 bucket name |
| `AWS_ENDPOINT` | `https://<account-id>.r2.cloudflarestorage.com` |
| `AWS_URL` | Public custom domain or public R2 bucket URL for profile photos |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` |
| `FILESYSTEM_THROW` | `true` |
| `LOG_CHANNEL` | `stderr` |

Enable public reads for the R2 bucket through a custom domain or the R2 public development URL; keep the S3 API credentials private. Profile uploads use the configured default filesystem disk. Do not run `php artisan db:seed` in production: the demo seeder uses public sample credentials.

After adding the production environment variables, run migrations once from a trusted checkout connected to the production database (`php artisan migrate --force`). Do not run migrations at container startup, and never commit `.env` or paste secrets into chat. Configure SMTP before enabling production email verification; until then Laravel's default `log` mailer does not deliver email, so production launch remains paused.
