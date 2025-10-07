<?php
namespace TMW\SA100\Classes;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Manages plugin configuration values stored in the options table.
 */
class Settings
{
    const OPTION_KEY = 'tmw_sa100_settings';
    const DEFAULT_LOCALE = 'en';
    const ALLOWED_LOCALES = ['en', 'es', 'de', 'fr'];

    /**
     * Cached settings array.
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Constructor loads settings.
     */
    public function __construct()
    {
        $defaults = [
            'serper_api_key' => '',
            'openai_api_key' => '',
            'openai_org_id' => '',
            'openai_project_id' => '',
            'default_locale' => static::DEFAULT_LOCALE,
        ];

        $stored = get_option(static::OPTION_KEY, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        $this->settings = wp_parse_args($stored, $defaults);

        $serper_option = get_option('tmw_sa100_serper_key', null);
        if ($serper_option !== null && $serper_option !== false) {
            $this->settings['serper_api_key'] = (string) $serper_option;
        }

        $openai_option = get_option('tmw_sa100_openai_key', null);
        if ($openai_option !== null && $openai_option !== false) {
            $this->settings['openai_api_key'] = (string) $openai_option;
        }

        $openai_org = get_option('tmw_sa100_openai_org_id', null);
        if ($openai_org !== null && $openai_org !== false) {
            $this->settings['openai_org_id'] = (string) $openai_org;
        }

        $openai_project = get_option('tmw_sa100_openai_project_id', null);
        if ($openai_project !== null && $openai_project !== false) {
            $this->settings['openai_project_id'] = (string) $openai_project;
        }

        $locale_option = get_option('tmw_sa100_locale', null);
        if ($locale_option !== null && $locale_option !== false) {
            $this->settings['default_locale'] = (string) $locale_option;
        }
    }

    /**
     * Activation routine ensures defaults exist.
     *
     * @return void
     */
    public static function activate()
    {
        if (! get_option(static::OPTION_KEY)) {
            add_option(static::OPTION_KEY, [
                'serper_api_key' => '',
                'openai_api_key' => '',
                'openai_org_id' => '',
                'openai_project_id' => '',
                'default_locale' => static::DEFAULT_LOCALE,
            ]);
        }

        add_option('tmw_sa100_serper_key', '');
        add_option('tmw_sa100_openai_key', '');
        add_option('tmw_sa100_openai_org_id', '');
        add_option('tmw_sa100_openai_project_id', '');
        add_option('tmw_sa100_locale', static::DEFAULT_LOCALE);
    }

    /**
     * Deactivation clean-up routine.
     *
     * @return void
     */
    public static function deactivate()
    {
        // Intentionally left for future scheduled cleanup.
    }

    /**
     * Retrieve all settings.
     *
     * @return array
     */
    public function all()
    {
        return $this->settings;
    }

    /**
     * Retrieve a single setting value.
     *
     * @param string $key Setting key.
     *
     * @return mixed|null
     */
    public function get($key)
    {
        return $this->settings[$key] ?? null;
    }

    /**
     * Persist many values at once.
     *
     * @param array $values Key/value pairs to update.
     *
     * @return void
     */
    public function update_many(array $values)
    {
        $sanitized = [];

        if (array_key_exists('serper_api_key', $values)) {
            $serper_key = $values['serper_api_key'];
            if (! is_string($serper_key)) {
                $serper_key = '';
            }

            $serper_key = sanitize_text_field($serper_key);
            $sanitized['serper_api_key'] = $serper_key;
            update_option('tmw_sa100_serper_key', $serper_key);
        }

        if (array_key_exists('openai_api_key', $values)) {
            $openai_key = $values['openai_api_key'];
            if (! is_string($openai_key)) {
                $openai_key = '';
            }

            $openai_key = sanitize_text_field($openai_key);
            $sanitized['openai_api_key'] = $openai_key;
            update_option('tmw_sa100_openai_key', $openai_key);
        }

        if (array_key_exists('openai_org_id', $values)) {
            $org_id = $values['openai_org_id'];
            if (! is_string($org_id)) {
                $org_id = '';
            }

            $org_id = sanitize_text_field($org_id);
            $sanitized['openai_org_id'] = $org_id;
            update_option('tmw_sa100_openai_org_id', $org_id);
        }

        if (array_key_exists('openai_project_id', $values)) {
            $project_id = $values['openai_project_id'];
            if (! is_string($project_id)) {
                $project_id = '';
            }

            $project_id = sanitize_text_field($project_id);
            $sanitized['openai_project_id'] = $project_id;
            update_option('tmw_sa100_openai_project_id', $project_id);
        }

        if (array_key_exists('default_locale', $values)) {
            $locale = $values['default_locale'];
            if (! is_string($locale)) {
                $locale = static::DEFAULT_LOCALE;
            }

            $locale = sanitize_key($locale);
            if (! in_array($locale, static::ALLOWED_LOCALES, true)) {
                $locale = static::DEFAULT_LOCALE;
            }

            $sanitized['default_locale'] = $locale;
            update_option('tmw_sa100_locale', $locale);
        }

        if (! empty($sanitized)) {
            $this->settings = wp_parse_args($sanitized, $this->settings);
            update_option(static::OPTION_KEY, $this->settings);
        }
    }
}
