<?php
/**
 * SMTP configuration for the contact form (send.php).
 *
 * Primary path is Resend's HTTPS API — set api_key and send.php uses it,
 * ignoring every smtp_* value below. Needed because hosts commonly firewall
 * outbound SMTP; HTTPS is never blocked. from_email must sit on a domain
 * verified in Resend.
 *
 * SMTP fallback options, in the order worth trying:
 *  - Empty smtp_host: PHPMailer falls back to PHP's mail(), which hands the
 *    message to the server's local MTA. No credentials. This is what the live
 *    site uses — the host redirects outbound port 587 to its own Exim, so
 *    external SMTP (Gmail, etc.) cannot be reached at all.
 *  - Host's own SMTP: smtp_host 'localhost', port 587, secure 'none',
 *    smtp_user/pass = a mailbox created in cPanel. 'none' because the host's
 *    cert is issued for its hostname and will not validate as 'localhost'.
 *  - External SMTP (Gmail App Password etc.): only on hosts that allow
 *    outbound 587/465. Confirm before relying on it.
 *
 * from_email must be on the site's own domain, or SPF fails and mail lands in
 * spam. Reply-To is set to the visitor's address by send.php.
 */
return [
    'api_key'     => '',

    'smtp_host'   => '',
    'smtp_port'   => 587,
    'smtp_secure' => 'none', // 'tls' (587), 'ssl' (465), 'none' (localhost only)
    'smtp_user'   => '',
    'smtp_pass'   => '',

    'from_email'  => 'web@example.com',
    'from_name'   => 'Hostel Samobor — web',
    'to_email'    => 'hostel.samobor@gmail.com',
];
