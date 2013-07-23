<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'ysfhq');

/** MySQL database username */
define('DB_USER', 'ysfhq');

/** MySQL database password */
define('DB_PASSWORD', 'mtvzsmkcvehv');

/** MySQL hostname */
define('DB_HOST', 'ysfhq-dev.cg1v8w3lirpf.us-east-1.rds.amazonaws.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'B.56{+$Z+PL$k!xP?{?|XE 5|=J:jY-#-waN#!1DV .,bJ)vcVRdt1|F-]AEB7[[');
define('SECURE_AUTH_KEY',  '#J-||5-!`!9_4Cm7J|`-N_v*=ni4fE*kCjfRvcQ4Q(L~=4@ws1gmtJ`V{GE-DPoZ');
define('LOGGED_IN_KEY',    'j+bs1*_7!as>,Xy(/QQ )xEMB++r-49vF=_C|ha(tXK6sM,U/7KO-lV@1`cOP|Vv');
define('NONCE_KEY',        ':]Bp8qFWSpBBE<7b9ia|+|4Y^h<WrRI!wZpRVE?1rl;+-*;t~xV5Q5@vehr?6-f|');
define('AUTH_SALT',        'fN2+?|r S:`36>GLj*c4No2!@Q{7FK5uuU=w4#j*f#`PO;Lh^6;=fN-C)Vj4!VBX');
define('SECURE_AUTH_SALT', '&:&i7KO2SP5Cbd+-b0Ht*dVIJL]w$|O]muK7C}SaOD6|aoTv+7K.+2-W]PCDDUj#');
define('LOGGED_IN_SALT',   'BEUC?+`6*kx2 9==J^SwB-^w5+wCv+U~F3]Lx&+vz]a_/#x5Xf8,$9Rz?,s/f{;8');
define('NONCE_SALT',       ')K[K@O2u2;hWyCcEm*|yc|_J00eV08:e-@:%vT=e0m<_@i#+rzTy>wbWK*PyAe&c');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
define('WP_CACHE', true);
require_once(ABSPATH . 'wp-settings.php');
