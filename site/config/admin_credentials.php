<?php
/**
 * config/admin_credentials.php — demo admin login for this portfolio build.
 *
 * Default: username "admin", password "sphynx-admin-demo" — change both
 * before using this anywhere beyond a local demo. The password is stored
 * as a bcrypt hash, never in plain text; regenerate it with:
 *
 *   php -r "echo password_hash('your-new-password', PASSWORD_BCRYPT), PHP_EOL;"
 */

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2b$10$A4kJ953XljZlbSfhyHosWulxQMgSqUnnKzRxb4TtGG3KKI0N69wF6');
