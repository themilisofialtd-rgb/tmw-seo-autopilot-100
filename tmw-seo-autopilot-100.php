<?php
/**
 * Plugin Name:       TMW SEO Autopilot 100
 * Plugin URI:        https://themilisofialtd.com/plugins/tmw-seo-autopilot-100
 * Description:       Phase 1 of the TMW SEO Autopilot suite providing Serper search intelligence and OpenAI content automation.
 * Version:           1.0.0
 * Author:            The Milisofia LTD
 * Author URI:        https://themilisofialtd.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tmw-seo-autopilot-100
 * Domain Path:       /languages
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('TMW_SA100_PLUGIN_FILE')) {
    define('TMW_SA100_PLUGIN_FILE', __FILE__);
}

if (! defined('TMW_SA100_VERSION')) {
    define('TMW_SA100_VERSION', '1.0.0');
}

require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';

/**
 * Basic class autoloader for plugin components.
 *
 * @param string $class Fully qualified class name.
 * @return void
 */
function tmw_sa100_autoload_classes($class)
{
    $map = [
        'TMW\\SA100\\Core\\Plugin'           => 'core/class-plugin.php',
        'TMW\\SA100\\Core\\Rest_Routes'      => 'core/class-rest-routes.php',
        'TMW\\SA100\\Classes\\Settings'      => 'classes/class-settings.php',
        'TMW\\SA100\\Classes\\Serper_Client' => 'classes/class-serper-client.php',
        'TMW\\SA100\\Classes\\OpenAI_Client' => 'classes/class-openai-client.php',
        'TMW\\SA100\\Core\\Keyword_Engine'   => 'core/class-keyword-engine.php',
        'TMW\\SA100\\Classes\\Task_Runner'   => 'classes/class-task-runner.php',
    ];

    if (isset($map[$class])) {
        $file = plugin_dir_path(__FILE__) . $map[$class];
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

spl_autoload_register('tmw_sa100_autoload_classes');

register_activation_hook(__FILE__, '\\TMW\\SA100\\Core\\Plugin::activate');
register_deactivation_hook(__FILE__, '\\TMW\\SA100\\Core\\Plugin::deactivate');

add_action('plugins_loaded', function () {
    \TMW\SA100\Core\Plugin::get_instance();
});
