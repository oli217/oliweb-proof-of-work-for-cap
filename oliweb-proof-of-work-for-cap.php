<?php
/**
 * Plugin Name:       OliWeb Proof-of-Work for Cap
 * Plugin URI:        https://github.com/oli217/oliweb-proof-of-work-for-cap
 * Description:       Integrates Cap (self-hosted proof-of-work CAPTCHA) into WordPress comments, login, registration, and WooCommerce checkout.
 * Version:           1.3.2
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Author:            OliWeb
 * Author URI:        https://oliweb.ch
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       oliweb-proof-of-work-for-cap
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('TPOW_VERSION', '1.3.2');
define('TPOW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TPOW_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TPOW_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$pucChecker = PucFactory::buildUpdateChecker(
    'https://github.com/oli217/oliweb-proof-of-work-for-cap/',
    __FILE__,
    'oliweb-proof-of-work-for-cap'
);
$pucChecker->getVcsApi()->enableReleaseAssets();

require_once TPOW_PLUGIN_DIR . 'includes/class-cap-verifier.php';
require_once TPOW_PLUGIN_DIR . 'includes/class-cap-settings.php';
require_once TPOW_PLUGIN_DIR . 'includes/class-cap-widget.php';
require_once TPOW_PLUGIN_DIR . 'includes/class-cap-integrations.php';

(new Tpow_Settings())->init();
(new Tpow_Widget())->init();
(new Tpow_Integrations())->init();
