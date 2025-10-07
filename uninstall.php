<?php
/**
 * Cleanup routine for TMW SEO Autopilot 100.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('tmw_sa100_settings');
