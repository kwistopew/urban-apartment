# Urban Nest — Vercel + Supabase

This package uses **Vercel** for the PHP web application and **Supabase PostgreSQL** for the database.

## 1. Create the Supabase database

1. Create a project in Supabase.
2. Open **SQL Editor**.
3. Open `database/supabase_schema.sql` from this project.
4. Run the complete SQL script.
5. Confirm the tables appear under **Table Editor**.

## 2. Get the Supabase connection string

In Supabase, open the database connection settings and copy a PostgreSQL connection string. A pooler connection is usually the easiest option when the environment does not support a direct database connection.

Set this in Vercel as:

`DATABASE_URL=postgresql://...`

The PHP project also supports separate variables if you prefer them:

- `DB_HOST`
- `DB_PORT` (usually `5432` for direct connections or `6543` for Supabase transaction pooler)
- `DB_NAME` (usually `postgres`)
- `DB_USER`
- `DB_PASSWORD`
- `DB_SSLMODE` (default: `require`)

**Use either `DATABASE_URL` or the `DB_*` variables.** `DATABASE_URL` takes priority.

## 3. Configure email in Vercel

Add these Environment Variables:

- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `SMTP_FROM_EMAIL` (optional; defaults to SMTP_USERNAME)
- `SMTP_FROM_NAME` (optional; defaults to Urban Nest)
- `APP_URL` (your deployed Vercel URL, for example `https://your-project.vercel.app`)
- `APP_ENV=production`

For Gmail, use a Google App Password, not the normal Gmail password.

## 4. Deploy to Vercel

1. Upload/push this project to your Git repository.
2. Import the repository into Vercel.
3. Vercel will detect `Dockerfile.vercel` at the project root.
4. Add the environment variables above under the Vercel project settings.
5. Deploy.

## 5. Important: uploaded apartment images

The current application writes uploaded apartment images to `uploads/apartments/`. Vercel container storage is **ephemeral**, so files written there are not reliable persistent storage.

For production, move apartment image uploads to **Supabase Storage** and save the public/signed object path in `apartment_images.image_path`. The database conversion in this package is complete; Supabase Storage integration is a separate storage step.

## 6. Local development

For local PHP development, use PHP with the `pdo_pgsql` extension and point `DATABASE_URL` to your Supabase database. You no longer need the local MySQL `apartment_rental` database for this version.

## Security note

The previous project archive contained an SMTP credential in `includes/mail_config.php`. This converted version removes that credential from source code. **If that credential was real, revoke/rotate the Gmail App Password before deploying.**
