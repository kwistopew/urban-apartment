# Urban Nest — Supabase Local Connection

1. Copy `.env.example` to `.env`.
2. In Supabase, open **Project Settings → Database → Connection string** and copy the **URI** connection string.
3. Put that URI in `.env` as `DATABASE_URL=...`.
4. Keep the `.env` file in the same folder as `index.php`.
5. Put the project under XAMPP `htdocs`, for example `htdocs/apartment-rental/`.
6. Start Apache in XAMPP. MySQL does not need to be running for this project when using Supabase.
7. Open `http://localhost/apartment-rental/`.

The PHP connection file automatically reads the local `.env`. On Vercel, `.env` is not used; set the same variables in **Vercel → Settings → Environment Variables**.

## If Supabase says the password is wrong
Reset the database password in **Supabase → Project Settings → Database** and replace the password in `.env`. Do not paste the password into GitHub or chat.

## Important
Use the Supabase PostgreSQL URI, not a MySQL URI. It should start with `postgresql://`.
