<?php
namespace TMW\SA100\Core;

use TMW\SA100\Classes\OpenAI_Client;
use TMW\SA100\Classes\Serper_Client;
use TMW\SA100\Classes\Settings;
use TMW\SA100\Classes\Task_Runner;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin controller.
 */
class Plugin
{
    /**
     * Plugin singleton instance.
     *
     * @var Plugin
     */
    protected static $instance;

    /**
     * Plugin settings controller.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Task runner for SEO automation routines.
     *
     * @var Task_Runner
     */
    protected $task_runner;

    /**
     * Retrieve singleton instance.
     *
     * @return Plugin
     */
    public static function get_instance()
    {
        if (! isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Constructor registers hooks.
     */
    protected function __construct()
    {
        $this->settings = new Settings();

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_init', [$this, 'handle_integrations_save']);
        add_action('admin_notices', [$this, 'maybe_show_integrations_notice']);
        add_action('wp_ajax_tmw_sa100_test_connections', [$this, 'handle_test_connections']);

        $this->task_runner = new Task_Runner(
            new Serper_Client($this->settings),
            new OpenAI_Client($this->settings),
            new Keyword_Engine($this->settings)
        );

        add_filter('plugin_action_links_' . plugin_basename(TMW_SA100_PLUGIN_FILE), [$this, 'plugin_action_links']);
    }

    /**
     * Activate hook - ensure cron and default options exist.
     *
     * @return void
     */
    public static function activate()
    {
        Settings::activate();
    }

    /**
     * Deactivate hook - clean up scheduled tasks.
     *
     * @return void
     */
    public static function deactivate()
    {
        Settings::deactivate();
    }

    /**
     * Register the plugin admin menu.
     *
     * @return void
     */
    public function register_admin_menu()
    {
        add_menu_page(
            __('SEO Autopilot', 'tmw-seo-autopilot-100'),
            __('SEO Autopilot', 'tmw-seo-autopilot-100'),
            'manage_options',
            'tmw-seo-autopilot-100',
            [$this, 'render_dashboard'],
            'dashicons-admin-site-alt3'
        );

        add_submenu_page(
            'tmw-seo-autopilot-100',
            __('Keyword Engine', 'tmw-seo-autopilot-100'),
            __('Keyword Engine', 'tmw-seo-autopilot-100'),
            'manage_options',
            'tmw-sa100-keyword-engine',
            [$this, 'render_keyword_engine']
        );

        add_submenu_page(
            'tmw-seo-autopilot-100',
            __('Integrations', 'tmw-seo-autopilot-100'),
            __('Integrations', 'tmw-seo-autopilot-100'),
            'manage_options',
            'tmw-sa100-integrations',
            [$this, 'render_integrations']
        );

        add_submenu_page(
            'tmw-seo-autopilot-100',
            __('Diagnostics', 'tmw-seo-autopilot-100'),
            __('Diagnostics', 'tmw-seo-autopilot-100'),
            'manage_options',
            'tmw-sa100-diagnostics',
            [$this, 'render_diagnostics']
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @return void
     */
    public function enqueue_assets($hook_suffix)
    {
        if (strpos($hook_suffix, 'tmw-seo-autopilot-100') === false) {
            return;
        }

        wp_enqueue_style(
            'tmw-sa100-admin',
            plugins_url('admin/assets/admin.css', TMW_SA100_PLUGIN_FILE),
            [],
            TMW_SA100_VERSION
        );

        wp_enqueue_script(
            'tmw-sa100-admin',
            plugins_url('admin/assets/admin.js', TMW_SA100_PLUGIN_FILE),
            ['jquery'],
            TMW_SA100_VERSION,
            true
        );

        wp_localize_script('tmw-sa100-admin', 'tmwSA100', [
            'nonce'      => wp_create_nonce('wp_rest'),
            'restUrl'    => rest_url('tmw-sa100/v1'),
            'hasSerper'  => (bool) $this->settings->get('serper_api_key'),
            'hasOpenAI'  => (bool) $this->settings->get('openai_api_key'),
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'testNonce'  => wp_create_nonce('tmw_sa100_test_connections'),
            'cachedTest' => get_transient('tmw_sa100_last_test') ?: null,
            'i18nSerperOk'    => __('Serper connected', 'tmw-seo-autopilot-100'),
            'i18nSerperFail'  => __('Serper failed', 'tmw-seo-autopilot-100'),
            'i18nOpenAIOk'    => __('OpenAI connected', 'tmw-seo-autopilot-100'),
            'i18nOpenAIFail'  => __('OpenAI failed', 'tmw-seo-autopilot-100'),
            'i18nNoticeSuccess' => __('✅ API connections tested successfully', 'tmw-seo-autopilot-100'),
            'i18nNoticeFail'    => __('❌ API connection test failed', 'tmw-seo-autopilot-100'),
            'i18nGenericError'  => __('Unable to complete the connection test. Please try again.', 'tmw-seo-autopilot-100'),
        ]);
    }

    /**
     * Register custom REST routes for automation endpoints.
     *
     * @return void
     */
    public function register_rest_routes()
    {
        Rest_Routes::register($this->task_runner, $this->settings);
    }

    /**
     * Render dashboard page.
     *
     * @return void
     */
    public function render_dashboard()
    {
        tmw_sa100_view('dashboard', [
            'settings' => $this->settings,
        ]);
    }

    /**
     * Render keyword engine page.
     *
     * @return void
     */
    public function render_keyword_engine()
    {
        tmw_sa100_view('keyword-engine', [
            'task_runner' => $this->task_runner,
        ]);
    }

    /**
     * Render integrations page.
     *
     * @return void
     */
    public function render_integrations()
    {
        tmw_sa100_view('integrations', [
            'settings' => $this->settings,
        ]);
    }

    /**
     * Handle POST submissions from the integrations form.
     *
     * @return void
     */
    public function handle_integrations_save()
    {
        if (! is_admin()) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== 'tmw-sa100-integrations') {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('tmw_sa100_integrations_action', 'tmw_sa100_integrations_nonce');

        $serper_key = isset($_POST['serper_api_key']) ? sanitize_text_field(wp_unslash($_POST['serper_api_key'])) : '';
        $openai_key = isset($_POST['openai_api_key']) ? sanitize_text_field(wp_unslash($_POST['openai_api_key'])) : '';
        $openai_org = isset($_POST['openai_org_id']) ? sanitize_text_field(wp_unslash($_POST['openai_org_id'])) : '';
        $openai_project = isset($_POST['openai_project_id']) ? sanitize_text_field(wp_unslash($_POST['openai_project_id'])) : '';

        $locale = isset($_POST['default_locale']) ? sanitize_key(wp_unslash($_POST['default_locale'])) : Settings::DEFAULT_LOCALE;
        if (! in_array($locale, Settings::ALLOWED_LOCALES, true)) {
            $locale = Settings::DEFAULT_LOCALE;
        }

        $this->settings->update_many([
            'serper_api_key' => $serper_key,
            'openai_api_key' => $openai_key,
            'openai_org_id' => $openai_org,
            'openai_project_id' => $openai_project,
            'default_locale' => $locale,
        ]);

        delete_transient('tmw_sa100_last_test');

        $redirect_url = add_query_arg('updated', 'true', admin_url('admin.php?page=tmw-sa100-integrations'));
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Display a success notice after integrations are saved.
     *
     * @return void
     */
    public function maybe_show_integrations_notice()
    {
        if (! is_admin()) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== 'tmw-sa100-integrations') {
            return;
        }

        $updated = isset($_GET['updated']) ? sanitize_text_field(wp_unslash($_GET['updated'])) : '';
        if ($updated !== 'true') {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('✅ Integrations saved successfully.', 'tmw-seo-autopilot-100') . '</p></div>';
    }

    /**
     * AJAX handler to test Serper and OpenAI connections.
     *
     * @return void
     */
    public function handle_test_connections()
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to run this test.', 'tmw-seo-autopilot-100')], 403);
        }

        check_ajax_referer('tmw_sa100_test_connections', 'nonce');

        $cached = get_transient('tmw_sa100_last_test');
        if (is_array($cached)) {
            wp_send_json($cached);
        }

        $serper_ok = $this->test_serper_connection();
        $openai_check = $this->test_openai_connection();
        $openai_ok = $openai_check['success'];

        $response = [
            'serper_status' => $serper_ok ? 'ok' : 'fail',
            'openai_status' => $openai_ok ? 'ok' : 'fail',
            'openai_http_code' => $openai_check['status_code'],
        ];

        if (! empty($openai_check['error'])) {
            $response['openai_error'] = $openai_check['error'];
        }

        set_transient('tmw_sa100_last_test', $response, 10 * MINUTE_IN_SECONDS);

        wp_send_json($response);
    }

    /**
     * Execute a lightweight Serper connectivity check.
     *
     * @return bool
     */
    protected function test_serper_connection()
    {
        $api_key = $this->settings->get('serper_api_key');

        if (empty($api_key)) {
            error_log('[TMW SA100] Serper API key missing during connection test.');

            return false;
        }

        $endpoint = add_query_arg('q', 'SEO demo', 'https://google.serper.dev/search');

        $response = wp_remote_get($endpoint, [
            'headers' => [
                'X-API-KEY' => $api_key,
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('[TMW SA100] Serper test request failed: ' . $response->get_error_message());

            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code !== 200) {
            error_log('[TMW SA100] Serper test returned HTTP ' . $code);

            return false;
        }

        $data = json_decode($body, true);

        if (! is_array($data)) {
            error_log('[TMW SA100] Serper test response was not JSON.');

            return false;
        }

        return ! empty($data);
    }

    /**
     * Execute a lightweight OpenAI connectivity check.
     *
     * @return array{success:bool,status_code:?int,error:string}
     */
    protected function test_openai_connection()
    {
        $api_key = $this->settings->get('openai_api_key');

        if (empty($api_key)) {
            error_log('[TMW SA100] OpenAI API key missing during connection test.');

            return [
                'success' => false,
                'status_code' => null,
                'error' => 'missing_key',
            ];
        }

        $headers = $this->build_openai_headers($api_key);

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => $headers,
            'body'    => wp_json_encode([
                'model'    => 'gpt-5',
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => 'ping',
                    ],
                ],
            ]),
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            error_log('[TMW SA100] OpenAI test request failed: ' . $response->get_error_message());

            return [
                'success' => false,
                'status_code' => null,
                'error' => $response->get_error_message(),
            ];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $result = [
            'success' => false,
            'status_code' => $code,
            'error' => '',
        ];

        if (in_array($code, [400, 401], true)) {
            $org_header = isset($headers['OpenAI-Organization']) ? $headers['OpenAI-Organization'] : '(not set)';
            $project_header = isset($headers['OpenAI-Project']) ? $headers['OpenAI-Project'] : '(not set)';
            error_log('[TMW SA100] OpenAI headers used - Organization: ' . $org_header . ', Project: ' . $project_header);
        }

        if ($code === 401) {
            error_log('[TMW SA100] OpenAI test returned HTTP 401 (unauthorized).');
            error_log('[TMW SA100] OpenAI test response body: ' . $body);

            $result['error'] = 'unauthorized';

            return $result;
        }

        if ($code === 400) {
            error_log('[TMW SA100] OpenAI test returned HTTP 400 (bad request).');

            $result['error'] = 'http_400';

            return $result;
        }

        if ($code !== 200) {
            error_log('[TMW SA100] OpenAI test returned HTTP ' . $code);

            $result['error'] = 'http_' . $code;

            return $result;
        }

        $data = json_decode($body, true);

        if (! is_array($data) || empty($data['choices'])) {
            error_log('[TMW SA100] OpenAI test response missing choices payload.');

            $result['error'] = 'invalid_payload';

            return $result;
        }

        $result['success'] = true;
        $result['error'] = '';

        return $result;
    }

    /**
     * Prepare headers for OpenAI requests, including project support.
     *
     * @param string $api_key Stored OpenAI API key.
     *
     * @return array
     */
    protected function build_openai_headers($api_key)
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

    /**
     * Render diagnostics page.
     *
     * @return void
     */
    public function render_diagnostics()
    {
        $openai_check = $this->test_openai_connection();

        tmw_sa100_view('diagnostics', [
            'settings' => $this->settings,
            'connection_checks' => [
                'serper' => tmw_sa100_is_connected('https://serper.dev/'),
                'openai' => $openai_check['success'],
                'openai_status_code' => $openai_check['status_code'],
                'wordpress' => tmw_sa100_is_connected(home_url()),
            ],
        ]);
    }

    /**
     * Provide quick links from plugins list.
     *
     * @param array $links Current links.
     *
     * @return array
     */
    public function plugin_action_links(array $links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=tmw-sa100-integrations')) . '">' . __('Settings', 'tmw-seo-autopilot-100') . '</a>';
        array_unshift($links, $settings_link);

        return $links;
    }
}
