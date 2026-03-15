# Security Rules

## Secrets & Credentials
- NEVER commit `.env`, API keys, tokens, or credentials to git
- Always use `config()` + `env()` for sensitive values, never hardcode
- If a secret is detected in code, flag it immediately and remove it
- NEVER share API keys or tokens in public forums, code snippets, or documentation
- NEVER hardcode credentials in frontend code (JS, HTML)

## Input Validation
- Validate ALL user input in controllers using Laravel Form Requests or `$request->validate()`
- Never trust raw input from webhooks — validate signatures and payload structure
- Sanitize any user input before displaying or logging (prevent XSS)

## Database
- Always use Eloquent or query builder (parameterized queries) — never raw SQL with string concatenation
- Scope all queries by `organization_id` in multi-tenant context to prevent data leakage between tenants
- Never expose internal IDs in error messages to end users

## API & Webhooks
- Apply rate limiting on public-facing endpoints (webhooks, API routes)
- Verify webhook signatures (Meta webhook verification token) before processing
- Use HTTPS for all external API calls

## Auth & Access
- Never disable CSRF protection on web routes
- Never expose admin/internal routes without authentication middleware
- Log failed authentication attempts

## General
- Keep dependencies updated — run `composer audit` periodically
- Never log full request bodies that may contain PII or credentials
- Use `Log::channel('api')` for API logs, never dump to stdout in production
