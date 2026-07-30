# Deploy — Hostel Samobor

Target: client's Croatian shared hosting (Apache + cPanel), as an **addon
domain** on a plan that already runs a WordPress site on the primary domain plus
two other sites. Domain `hostel-samobor.hr`, registrar access confirmed.

## What to upload

Everything goes into the addon domain's document root — **not** `public_html`
itself, which belongs to the WordPress site. cPanel creates the folder when you
add the domain, typically `public_html/hostel-samobor.hr/` (some versions use
`public_html/hostel-samobor/`). Check the exact path in cPanel → Domains before
uploading.

If the cPanel version lets you edit the document root when creating the addon
domain, set it **outside** `public_html` — e.g. `/home/<user>/hostel-samobor` —
rather than accepting the default. That removes the duplicate-URL problem in
step 2 entirely and stops the WordPress site's directory tree from containing
this one. Worth the extra field.

Keep the folder structure:

```
.htaccess
*.html          (26 files: index.html, en.html + 24 detail pages)
send.php
css/            style.css, swiper-bundle.min.css
js/             7 files
images/         7.7 MB
php/            .htaccess, mail-config.php, PHPMailer/
```

## What NOT to upload

| Path | Why |
|------|-----|
| `.git/` | Exposes full source history over HTTP if webroot-served |
| `.superpowers/` | Local scratch |
| `docs/` | Plans and specs, not site content |
| `REDESIGN-PLAN.md`, `DEPLOY.md` | Internal notes |
| `php/mail-config.example.php` | Template only; the real file is `mail-config.php` |

## Gotcha: mail-config.php is not in git

`php/mail-config.php` is gitignored (it holds the live Gmail App Password). If
you deploy by cloning or downloading the repo, **that file will be missing** and
the contact form will fatal-error on line 11 of `send.php`. Upload it manually
from your local copy, once, over SFTP.

## Steps

1. **Create the addon domain** — cPanel → Domains → Create A Domain. Note the
   document root it assigns (or override it, see above).
2. **PHP version, per domain** — cPanel → **MultiPHP Manager**, select
   `hostel-samobor.hr` only, set **8.0+** (`send.php` uses
   `declare(strict_types=1)` and typed params, so 7.x fatals). Do **not** use
   "Select PHP Version" to change the account-wide default — that would move the
   WordPress site's PHP version too and can take it down. Upgrading every site on
   the account is a separate job with its own order of operations: see
   `PHP-UPGRADE.md`.
3. **Upload** the set above into the addon document root. SFTP (FileZilla/WinSCP)
   or cPanel File Manager → upload a zip → Extract. Use SFTP/FTPS, not plain FTP
   — plain FTP sends the password in clear text.
4. **File permissions** — directories `755`, files `644`. Most hosts do this
   automatically; check `php/mail-config.php` is not `777`.
5. **TLS certificate** — cPanel → SSL/TLS Status. AutoSSL must issue a cert
   covering `hostel-samobor.hr` *and* `www.hostel-samobor.hr`. It can only do
   that after DNS points here, so if it fails, do step 6 first and re-run
   AutoSSL. Until the cert exists, the `.htaccess` HTTPS redirect sends visitors
   into a certificate warning.
6. **DNS** — at the registrar, point the A records for `hostel-samobor.hr` and
   `www` at the host's IP (cPanel sidebar → Shared IP Address). Propagation up
   to 24h, usually minutes.
7. **Verify the WordPress site still works** — load the primary domain and a
   couple of its inner pages. The new `.htaccess` should not affect it, but
   confirm rather than assume.
8. **Check the duplicate URL is gone** — visit
   `https://<primary-domain>/hostel-samobor.hr/` and confirm it 301s to
   `https://hostel-samobor.hr/`. If it does not, the addon docroot is nested in
   `public_html` and the canonical rule in `.htaccess` is not matching — check
   the hostname spelling in that rule.
9. **Test the contact form** — the only part that cannot be tested locally (no
   PHP on the dev machine). Submit a real inquiry from `/index.html#kontakt` and
   confirm it lands in `hostel.samobor@gmail.com`. Then repeat from
   `en.html#contact`.

## If the form fails

`send.php` swallows all errors and redirects to `?sent=0`, so the browser tells
you nothing. To see the real cause, check the host's PHP error log (cPanel →
Errors), then in order:

- **Missing config** → `mail-config.php` was not uploaded (see gotcha above).
- **SMTP connect failure** → many shared hosts firewall outbound port 587. Ask
  the provider to open it, or switch to their own SMTP: set `smtp_host` to
  `mail.hostel-samobor.hr`, port/credentials from the provider, and change
  `from_email` to a mailbox on that domain.
- **Gmail auth rejected** → App Password revoked, or 2-step verification turned
  off on `hostel.samobor@gmail.com`. Generate a new App Password.
- **Mail sends but lands in spam** → expected with Gmail SMTP sending as Gmail
  from a hosting IP. Fix is the host's own SMTP on the domain (above) plus SPF
  and DKIM records at the registrar.

## Shared-account note

All four sites live in one cPanel account, one Unix user. A compromised
WordPress plugin on the primary domain can read and write files in this addon
folder, including `php/mail-config.php` and its Gmail App Password. Nothing in
this deploy can prevent that. Keep WordPress core and plugins current, and if
that site is ever found compromised, rotate the App Password as part of cleanup.

## Known gaps (not blockers)

- No favicon.
- No `canonical` or Open Graph tags — link previews on Facebook/WhatsApp will be
  bare.
- No `hreflang` pair between `index.html` and `en.html` (the visible HR/EN
  switch works; this is the machine-readable one for search engines).
- No 404 page — visitors hitting a bad URL get the host's default Apache page.
