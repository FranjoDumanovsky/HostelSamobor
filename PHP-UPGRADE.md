# Upgrading all sites on the cPanel account to PHP 8.x

Account runs four sites: WordPress on the primary domain, two other sites, and
Hostel Samobor as an addon domain. Goal is the newest practical PHP 8 across all
of them.

## Which version

| Version | Status (as of mid-2026) | Verdict |
|---------|------------------------|---------|
| 8.1 | End of life Dec 2025 — no security patches | Move off it |
| 8.2 | Security fixes only, until Dec 2026 | Interim only |
| 8.3 | Security fixes only, until Dec 2027 | Safe fallback |
| **8.4** | Supported, security until Dec 2028 | **Recommended** |
| 8.5 | Newest, but WP plugins lag a year+ | Only if tested |

Pick **8.4** for the WordPress site. Hostel Samobor's `send.php` needs only
8.0+, so it can take whatever is newest on the host.

Check what the host actually offers first — Croatian shared hosts often stop at
8.2 or 8.3. cPanel → MultiPHP Manager, open the version dropdown. If 8.4 is not
listed, take the highest that is, and ask the provider when 8.4 lands.

## Before touching anything

1. **Take a backup.** cPanel → Backup → Download a Full Account Backup, or use
   the provider's snapshot feature. Nothing below is guaranteed reversible
   without one — a WordPress site that fataled on upgrade is easy to roll back,
   a plugin that ran a bad DB migration is not.
2. **Note the current version of every domain** — MultiPHP Manager lists them.
   Write them down; this is your rollback target, per domain.
3. **Update WordPress first, while still on the old PHP.** Core, then plugins,
   then theme. Old plugin versions are what break on new PHP; upgrading PHP
   under stale plugins is doing it in the wrong order.
4. **Check WP core's own requirement** — WP admin → Tools → Site Health → Info →
   Server. It will tell you if the PHP version is below what core wants.

## Enable extensions on the target version

Do this before switching any domain.

cPanel → **Select PHP Version** (sometimes "PHP Selector") → set the version
selector to the target (8.4) → **Extensions** tab. Confirm these are checked:

```
mysqli  mysqlnd  curl  mbstring  gd  imagick  zip  intl
exif    dom      xml   json      openssl      fileinfo  iconv
```

WordPress needs `mysqli`, `curl`, `mbstring`, `gd` (or `imagick`), `zip`,
`dom`, `exif`. PHPMailer in Hostel Samobor needs `openssl` for SMTP over TLS —
without it the contact form fails silently.

If cPanel has no Extensions tab, the host manages extensions themselves — open a
ticket asking them to match the 8.4 extension set to the current version's.

## Switch the domains

cPanel → **MultiPHP Manager**. Change **one domain at a time**, least important
first, and load each site after its own switch. Do not tick all four and apply.

Order:

1. Hostel Samobor (nothing to lose — not live yet)
2. Whichever of the two other sites is least critical
3. The remaining other site
4. WordPress on the primary domain, last

For each: tick the domain → pick the version → Apply → load the site's homepage
and two inner pages → for WordPress also load `/wp-admin`, one post edit screen,
and submit a form if it has one.

If a site breaks: set that one domain back to its previous version in MultiPHP
Manager. That is the whole rollback — it takes effect immediately, and the other
domains are unaffected because MultiPHP is per-domain.

## After

- **Flush OPcache.** Cached bytecode from the old version can produce bizarre
  half-broken behavior. Some hosts expose a button; otherwise the version switch
  usually resets the pool. If a site misbehaves right after switching and then
  fixes itself, this was why.
- **Check error logs** — cPanel → Errors, and WordPress `wp-content/debug.log`
  if enabled. Deprecation notices are expected and harmless; `Fatal error` is
  not.
- **Expect deprecation noise from WordPress on 8.4.** PHP 8.4 deprecated
  implicitly nullable parameter types, which a lot of older plugin code uses. It
  logs, it does not break. Make sure `WP_DEBUG_DISPLAY` is `false` in
  `wp-config.php` so visitors never see it.
- **Re-test the Hostel Samobor contact form** if you switched its version after
  first testing it.

## If WordPress will not run on 8.4

Leave the primary domain on 8.3 and put the others on 8.4. MultiPHP is per
domain — there is no requirement that they match. A WordPress site on a
supported 8.3 is a fine outcome; forcing 8.4 under plugins that cannot take it
is not.
