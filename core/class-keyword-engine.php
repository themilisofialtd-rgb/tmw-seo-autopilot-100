<?php
namespace TMW\SA100\Core;

use TMW\SA100\Classes\Settings;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Core keyword engine utilities for SERP discovery and AI outline generation.
 */
class Keyword_Engine
{
    /**
     * Plugin settings accessor.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Constructor.
     *
     * @param Settings|null $settings Optional settings instance for dependency injection.
     */
    public function __construct(?Settings $settings = null)
    {
        $this->settings = $settings ?: new Settings();
    }

    /**
     * Fetch SERP data for a keyword via Serper.dev.
     *
     * @param string $keyword Target keyword.
     *
     * @return array|WP_Error
     */
    public function tmw_sa100_fetch_serp($keyword)
    {
        $keyword = sanitize_text_field($keyword);

        if ($keyword === '') {
            return new WP_Error('tmw_sa100_empty_keyword', __('Keyword is required for SERP lookup.', 'tmw-seo-autopilot-100'));
        }

        $api_key = $this->settings->get('serper_api_key');

        if (empty($api_key)) {
            error_log('[TMW SA100] Serper API key missing when fetching SERP data.');

            return new WP_Error('tmw_sa100_missing_serper_key', __('Serper API key is missing.', 'tmw-seo-autopilot-100'));
        }

        $url = 'https://google.serper.dev/search?q=' . rawurlencode($keyword);

        $response = wp_remote_get($url, [
            'headers' => [
                'X-API-KEY' => $api_key,
            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            error_log('[TMW SA100] Serper request failed: ' . $response->get_error_message());

            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            error_log('[TMW SA100] Serper returned HTTP ' . $code . ' for keyword ' . $keyword);

            return new WP_Error('tmw_sa100_serper_http_error', __('Unexpected Serper response code.', 'tmw-seo-autopilot-100'));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (! is_array($data)) {
            error_log('[TMW SA100] Serper response could not be decoded for keyword ' . $keyword);

            return new WP_Error('tmw_sa100_serper_decode_error', __('Unable to decode Serper response.', 'tmw-seo-autopilot-100'));
        }

        $results = [];

        if (! empty($data['organic']) && is_array($data['organic'])) {
            foreach ($data['organic'] as $item) {
                $results[] = [
                    'title'   => isset($item['title']) ? sanitize_text_field($item['title']) : '',
                    'url'     => isset($item['link']) ? esc_url_raw($item['link']) : '',
                    'snippet' => isset($item['snippet']) ? sanitize_text_field($item['snippet']) : '',
                ];
            }
        }

        return $results;
    }

    /**
     * Generate an SEO outline suggestion from SERP intelligence via OpenAI.
     *
     * @param string $keyword   Seed keyword/topic.
     * @param array  $serp_data SERP data from tmw_sa100_fetch_serp().
     *
     * @return string|WP_Error
     */
    public function tmw_sa100_generate_outline($keyword, array $serp_data)
    {
        $keyword = sanitize_text_field($keyword);
        $api_key = $this->settings->get('openai_api_key');

        if (empty($api_key)) {
            error_log('[TMW SA100] OpenAI API key missing when generating outline.');

            return new WP_Error('tmw_sa100_missing_openai_key', __('OpenAI API key is missing.', 'tmw-seo-autopilot-100'));
        }

        $serp_summary = array_slice(array_filter(array_map(function ($row) {
            $title = isset($row['title']) ? sanitize_text_field($row['title']) : '';
            $snippet = isset($row['snippet']) ? sanitize_text_field($row['snippet']) : '';

            if ($title === '' && $snippet === '') {
                return null;
            }

            return trim($title . ' — ' . $snippet);
        }, $serp_data)), 0, 5);

        $messages = [
            [
                'role'    => 'system',
                'content' => 'You are an SEO strategist who creates structured outlines with headings and bullet lists.',
            ],
            [
                'role'    => 'user',
                'content' => wp_json_encode([
                    'keyword' => $keyword,
                    'insights' => $serp_summary,
                ]),
            ],
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode([
                'model'    => 'gpt-5',
                'messages' => $messages,
                'temperature' => 0.7,
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            error_log('[TMW SA100] OpenAI outline request failed: ' . $response->get_error_message());

            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            error_log('[TMW SA100] OpenAI returned HTTP ' . $code . ' while generating outline.');

            return new WP_Error('tmw_sa100_openai_http_error', __('Unexpected OpenAI response code.', 'tmw-seo-autopilot-100'));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (! is_array($data) || empty($data['choices'][0]['message']['content'])) {
            error_log('[TMW SA100] OpenAI outline response missing content.');

            return new WP_Error('tmw_sa100_openai_decode_error', __('OpenAI response did not include content.', 'tmw-seo-autopilot-100'));
        }

        return sanitize_textarea_field($data['choices'][0]['message']['content']);
    }

    /**
     * Preserve legacy clustering helper for keyword plan generation.
     *
     * @param array  $results Serper results payload.
     * @param string $seed    Seed keyword.
     *
     * @return array
     */
    public function build_clusters(array $results, $seed)
    {
        $clusters = [
            'primary'   => [],
            'secondary' => [],
            'questions' => [],
        ];

        if (! empty($results['organic'])) {
            foreach ($results['organic'] as $item) {
                if (! empty($item['title'])) {
                    $clusters['primary'][] = sanitize_text_field($item['title']);
                }

                if (! empty($item['snippet'])) {
                    $clusters['secondary'][] = sanitize_text_field($item['snippet']);
                }
            }
        }

        if (! empty($results['relatedSearches'])) {
            foreach ($results['relatedSearches'] as $search) {
                if (isset($search['query'])) {
                    $clusters['questions'][] = sanitize_text_field($search['query']);
                }
            }
        }

        $clusters['primary']   = array_values(array_unique($clusters['primary']));
        $clusters['secondary'] = array_values(array_unique($clusters['secondary']));
        $clusters['questions'] = array_values(array_unique($clusters['questions']));

        return [
            'seed'     => $seed,
            'clusters' => $clusters,
        ];
    }
}
