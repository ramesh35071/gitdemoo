<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'gitdemoo_db' );

/** Database username */
define( 'DB_USER', 'ramesh87636' );

/** Database password */
define( 'DB_PASSWORD', 'C@ttlefeeds1' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         's-X(zD2.,MORtkeh4f(gBz4L6LBivm<P3W:&b*seRoF>~e3;>;WV<Ov!DL?)/?#N' );
define( 'SECURE_AUTH_KEY',  'wkj:,{6p`;!FG<dd`j/^G >:IH7r/h-/AX2f1*$AL<v=b2[J&+&A6[)O_2Cw_oe&' );
define( 'LOGGED_IN_KEY',    '[*-Rja,WGTH+8P[jZ#Se$)CWLvf@_@!:o22W8-T,0=8wz XAyBp8}ERA8RbPW[Nk' );
define( 'NONCE_KEY',        '&k;y/EV-+]6vZGcJ(Y]QFL-bBt%1]$@#svaZ;id[o)I[X}t]s3*4qkA{^@!Df5qG' );
define( 'AUTH_SALT',        'O<vj#Qt(v37RV_zZY{9f6;&@n;Dt&71<qBlFD19Z{s/r<j]4[Cgny]t(lNs<sO=z' );
define( 'SECURE_AUTH_SALT', '8%l]ZHpVUPEBmRfNam0dszwqfLt(Xx68C-K[)lI=a800j`cz%^*(GCZiM-2~LVjd' );
define( 'LOGGED_IN_SALT',   '^[d-kwZgi7Ia@)NJm}{t;~ S-.lqnb%<+{qx}7WDTqUKp=GI,OMN]P*RP| k0XE0' );
define( 'NONCE_SALT',       'nA0<:@{CM)I_dhT-%l9M(-U_|0~/zU69bWoH]0?l8@VTi2%EfK~Jq![0oB!X.ydR' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
