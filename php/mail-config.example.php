<?php
/**
 * SMTP configuration for the contact form (send.php).
 *
 * Fill these in once hosting is known:
 *  - Host's own SMTP (recommended): ask the hosting provider for host/port/user/pass.
 *  - Gmail: smtp_host 'smtp.gmail.com', port 587, secure 'tls',
 *    smtp_user 'hostel.samobor@gmail.com', smtp_pass = Google App Password
 *    (requires 2-step verification on the Google account; from_email must
 *    match smtp_user for Gmail).
 *
 * Leave smtp_host empty ('') to fall back to PHP's mail() — works on many
 * shared hosts with no credentials, but deliverability is usually worse.
 */
return [
    'smtp_host'   => '',
    'smtp_port'   => 587,
    'smtp_secure' => 'tls', // 'tls' (port 587) or 'ssl' (port 465)
    'smtp_user'   => '',
    'smtp_pass'   => '',

    'from_email'  => 'hostel.samobor@gmail.com',
    'from_name'   => 'Hostel Samobor — web',
    'to_email'    => 'hostel.samobor@gmail.com',
];
