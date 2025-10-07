<?php
namespace TMW\SA100\Core;

use TMW\SA100\Classes\Settings;
use TMW\SA100\Classes\Task_Runner;
use WP_REST_Request;
use WP_Error;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST API routes for the plugin automation endpoints.
 */
class Rest_Routes
{
    /**
     * Register the namespace routes.
     *
     * @param Task_Runner $runner   Task runner instance.
     * @param Settings    $settings Settings controller.
     *
     * @return void
     */
    public static function register(Task_Runner $runner, Settings $settings)
    {
        register_rest_route('tmw-sa100/v1', '/keyword-plan', [
            'methods'             => 'POST',
            'callback'            => function (WP_REST_Request $request) use ($runner) {
                $keywords = $request->get_param('keywords');
                $locale   = $request->get_param('locale');

                if (empty($keywords)) {
                    return new WP_Error('tmw_sa100_missing_keywords', __('Keywords are required.', 'tmw-seo-autopilot-100'), [
                        'status' => 400,
                    ]);
                }

                $result = $runner->generate_keyword_plan((array) $keywords, $locale);

                return new WP_REST_Response($result, 200);
            },
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('tmw-sa100/v1', '/content-draft', [
            'methods'             => 'POST',
            'callback'            => function (WP_REST_Request $request) use ($runner) {
                $topic   = $request->get_param('topic');
                $outline = $request->get_param('outline');

                if (empty($topic)) {
                    return new WP_Error('tmw_sa100_missing_topic', __('A topic is required for content drafts.', 'tmw-seo-autopilot-100'), [
                        'status' => 400,
                    ]);
                }

                $result = $runner->generate_content_outline($topic, (array) $outline);

                return new WP_REST_Response($result, 200);
            },
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('tmw-sa100/v1', '/settings', [
            'methods'             => ['GET', 'POST'],
            'callback'            => function (WP_REST_Request $request) use ($settings) {
                if ($request->get_method() === 'GET') {
                    return new WP_REST_Response($settings->all(), 200);
                }

                $params = $request->get_json_params();
                $settings->update_many([
                    'serper_api_key' => $params['serper_api_key'] ?? '',
                    'openai_api_key' => $params['openai_api_key'] ?? '',
                    'default_locale' => $params['default_locale'] ?? Settings::DEFAULT_LOCALE,
                ]);

                return new WP_REST_Response($settings->all(), 200);
            },
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }
}
