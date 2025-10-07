<?php
namespace TMW\SA100\Classes;

use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Minimal OpenAI REST client for generating marketing content.
 */
class OpenAI_Client
{
    const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * Settings handler.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Constructor.
     *
     * @param Settings $settings Settings class instance.
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Request a chat completion response.
     *
     * @param array  $messages Conversation messages.
     * @param string $model    Model identifier.
     *
     * @return array|WP_Error
     */
    public function chat(array $messages, $model = 'gpt-4o-mini')
    {
        $api_key = $this->settings->get('openai_api_key');

        if (empty($api_key)) {
            return new WP_Error('tmw_sa100_missing_openai_key', __('OpenAI API key is missing.', 'tmw-seo-autopilot-100'));
        }

        $response = wp_remote_post(static::API_ENDPOINT, [
            'headers' => $this->build_headers($api_key),
            'body'    => wp_json_encode([
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.4,
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (! is_array($data) || empty($data['choices'][0]['message']['content'])) {
            return new WP_Error('tmw_sa100_invalid_openai_response', __('Unexpected OpenAI response.', 'tmw-seo-autopilot-100'));
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Prepare the HTTP headers for an OpenAI request.
     *
     * @param string $api_key API key pulled from settings.
     *
     * @return array
     */
    protected function build_headers($api_key)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ];

        if (strpos($api_key, 'sk-proj-') === 0) {
            $org_id = get_option('tmw_sa100_openai_org_id', 'org-yourid');
            $project_id = get_option('tmw_sa100_openai_project_id', 'proj_default');

            $headers['OpenAI-Organization'] = $org_id;
            $headers['OpenAI-Project'] = $project_id;
        }

        return $headers;
    }
}
