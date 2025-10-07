<?php
/**
 * Shared helper functions for the TMW SEO Autopilot 100 plugin.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('tmw_sa100_view')) {
    /**
     * Safely load a view file.
     *
     * @param string $view File name without extension.
     * @param array  $data Optional data to extract for the template.
     *
     * @return void
     */
    function tmw_sa100_view($view, array $data = [])
    {
        $file = plugin_dir_path(TMW_SA100_PLUGIN_FILE) . 'admin/views/' . $view . '.php';

        if (! file_exists($file)) {
            return;
        }

        if (! empty($data)) {
            extract($data, EXTR_SKIP);
        }

        include $file;
    }
}

if (! function_exists('tmw_sa100_is_connected')) {
    /**
     * Simple connectivity check using WordPress HTTP API.
     *
     * @param string $url URL to ping.
     *
     * @return bool
     */
    function tmw_sa100_is_connected($url)
    {
        $response = wp_remote_head($url, [
            'timeout' => 5,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $status = wp_remote_retrieve_response_code($response);

        return $status >= 200 && $status < 400;
    }
}
