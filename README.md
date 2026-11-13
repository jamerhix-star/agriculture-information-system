# Agriculture Information System (AP3 Midterm)

Plain PHP 8 + PDO (`pdo_pgsql`) + Supabase PostgreSQL + Bootstrap 5 (CDN). No framework.

> **Status:** the code was written but has **not been executed or tested by its author.**
> Follow the checks in "7. Test it" and fix anything that shows up in `logs/app.log`.

## 1. Requirements on your laptop
- PHP 8.0 or newer with the extensions **pdo_pgsql** and **pgsql** enabled.
  - Check: `php -m` must list `pdo_pgsql`.
  - XAMPP/Windows: open `php.ini` and remove the `;` in front of `extension=pdo_pgsql` and `extension=pgsql`, then restart the terminal.
- Git, and a free Supabase project.
- Sublime Text is only your editor; you run the app from a terminal.

## 2. Create the database (Supabase)
1. Supabase -> **SQL Editor** -> paste all of `database/schema.sql` -> Run.
2. Paste all of `database/seed.sql` -> Run.
3. Supabase -> **Connect** -> choose **Session pooler** and copy host, port (5432) and user (`postgres.<project-ref>`).
   The *direct connection* is IPv6-only on the free plan and often fails from home networks, so use the Session pooler.

## 3. Configure secrets (never commit them)
```
cp .env.example .env      # Windows: copy .env.example .env
```
Edit `.env` with your Supabase values. `.env` is in `.gitignore`.

## 4. Test the connection and create the login users
```
php scripts/test_connection.php
php scripts/create_users.php YourAdminPass123 YourStaffPass123
```
This creates `admin` (Administrator) and `staff` (Staff). Passwords are hashed with `password_hash()`.

## 5. Run
```
php -S localhost:8000 -t public
```
Open http://localhost:8000 and log in.

## 6. Roles
- **Administrator**: everything, including Delete.
- **Staff**: view, add, edit, search, reports; no Delete.

## 7. Test it (minimum)
- Login with a wrong password -> friendly error. Open `/farmers/index.php` while logged out -> redirected to login.
- Add a farmer with empty fields -> messages under each field. Add a valid one -> green success message.
- Search, filter, sort and page through Farmers, Farms, Crops, Plantings, Harvests.
- As admin, delete a farmer that has farms -> blocked with a message. Delete a farmer with no farms -> confirmation, then success.
- Log in as `staff` -> no Delete buttons; opening a delete URL redirects with an error.
- Reports: try every tab with and without a date range; try From later than To.
- Stop your internet / break `.env` -> you see a friendly error page, details only in `logs/app.log`.

## 8. Git and GitHub
```
git init
git checkout -b dev
git add .
git commit -m "feat: initial agriculture information system"
git remote add origin https://github.com/<you>/<repo>.git
git push -u origin dev

# at a milestone: merge to main and tag
git checkout main     # first time: git checkout -b main
git merge dev
git tag -a v1.0 -m "Midterm submission"
git push origin main --tags
```
Check with `git status` that `.env` is NOT listed before every commit.

## 9. What came from the project document and what was added
From the document: the 5 tables and their column names/types, the relationships, the sample data of farmers (15), farms (15), crops (10), harvests (20) and plantings 11-28, the harvest-remarks lookup values 1-5, all feature requirements.
Added by the author of this code (not in the document):
- Lookup tables for soil type, irrigation type, crop category, season, planting status, quality grade (values taken from the sample data) and the extra statuses "Planted" and "Failed".
- `users` and `roles` tables, the audit columns (`created_by`, `updated_by`) and the `updated_at` trigger.
- Plantings 1-10 and 29-30 (the screenshots did not show them); generated from the pattern of rows 11-28.
- Sample harvest remarks mapped to the 1-5 list ("High quality harvest"/"Good harvest" -> Good Harvest, "Acceptable quality" -> Acceptable Quality).
- Rules: planted area <= farm area, harvest date >= planting date, farm 14 name completed as "Castillo Agricultural Farm" (it was cut off in the screenshot).
- Row Level Security enabled on all tables so the public Supabase API key cannot read them.

## 10. Not included
User-management screen, Docker, online hosting, the written documentation (ERD, normalization, data dictionary, screenshots, test table).
