<?php
/**
 * mail-config.example.php
 *
 * TEMPLATE FILE — safe to commit to GitHub, has no real secrets in it.
 *
 * HOW TO USE:
 * 1. Copy this file and rename the copy to: mail-config.php
 * 2. Fill in your real Hostinger SMTP details below in mail-config.php
 * 3. Add "mail-config.php" to your .gitignore so the real one is NEVER
 *    committed to GitHub (only this .example file should be in Git).
 * 4. Upload mail-config.php to Hostinger manually (via File Manager/FTP),
 *    separately from your GitHub deployment.
 */

return [
    // Hostinger SMTP server address — check Hostinger's hPanel > Emails > Email Accounts
    // for the exact hostname (usually something like "smtp.hostinger.com").
    'smtp_host' => 'smtp.hostinger.com',

    // The email account you created on Hostinger to send from
    // e.g. contact@axys3d.com
    'smtp_username' => 'your-email@yourdomain.com',

    // The password for that email account (NOT your main Hostinger login password)
    'smtp_password' => 'your-email-account-password',

    // Usually 465 (SSL) or 587 (TLS) — Hostinger typically uses 465
    'smtp_port' => 465,

    // 'ssl' for port 465, 'tls' for port 587
    'smtp_secure' => 'ssl',

    // Where the enquiry emails should be delivered to
    'recipient_email' => 'eman.niazqureshi@gmail.com',
    'recipient_name' => 'Eman Niaz Qureshi',

    // What shows as the "From" name in the received email
    'from_name' => 'Axys3D Website',
];