<?php
namespace TMW\SA100\Classes;

use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Lightweight client for the Serper search API.
 */
class Serper_Client
{
    const API_ENDPOINT = 'https://google.serper.dev/search';

    /**
     * Plugin settings handler.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Constructor.
     *
     * @param Settings $settings Settings object.
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Execute a keyword search.
     *
     * @param string $keyword Keyword to search.
     * @param string $locale  Optional locale.
     *
     * @return array|WP_Error
     */
    public function search($keyword, $locale = 'us')
    {
        $api_key = $this->settings->get('serper_api_key');

        if (empty($api_key)) {
            return new WP_Error('tmw_sa100_missing_serper_key', __('Serper API key is missing.', 'tmw-seo-autopilot-100'));
        }

        $response = wp_remote_post(static::API_ENDPOINT, [
            'headers' => [
                'X-API-KEY'    => $api_key,
                'Content-Type' => 'application/json',
            ],
            'body'    => wp_json_encode([
                'q'      => $keyword,
                'gl'     => $locale,
                'hl'     => substr($locale, 0, 2),
                'num'    => 10,
            ]),
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (! is_array($data)) {
            return new WP_Error('tmw_sa100_invalid_serper_response', __('Unexpected Serper response.', 'tmw-seo-autopilot-100'));
        }

        return $data;
    }
}
