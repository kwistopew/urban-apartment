URBAN NEST - VERCEL + SUPABASE

This version is prepared for a PHP deployment on Vercel with Supabase PostgreSQL.

LOCAL DEVELOPMENT
1. Install PHP 8.3+ with the PostgreSQL PDO extension (pdo_pgsql).
2. Create a Supabase project.
3. Open Supabase SQL Editor and run database/supabase_schema.sql.
4. Set DATABASE_URL to your Supabase Postgres connection string, or set the DB_* variables described in VERCEL_DEPLOYMENT.md.
5. Start PHP locally from this folder, or continue using your preferred local PHP server.

VERCEL
See VERCEL_DEPLOYMENT.md for the exact environment variables and deployment steps.

EMAIL
SMTP_USERNAME and SMTP_PASSWORD must be configured as Vercel environment variables. Do not put Gmail credentials in source code.

IMPORTANT
Vercel container storage is not persistent. Apartment images uploaded into uploads/apartments are not guaranteed to survive redeployments or instance changes. For production image storage, use Supabase Storage (or another persistent object store).
