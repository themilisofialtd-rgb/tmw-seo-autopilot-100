<?php
namespace TMW\SA100\Classes;

use TMW\SA100\Core\Keyword_Engine;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Coordinates keyword research and content planning tasks.
 */
class Task_Runner
{
    /**
     * @var Serper_Client
     */
    protected $serper;

    /**
     * @var OpenAI_Client
     */
    protected $openai;

    /**
     * @var Keyword_Engine
     */
    protected $engine;

    /**
     * Constructor.
     */
    public function __construct(Serper_Client $serper, OpenAI_Client $openai, Keyword_Engine $engine)
    {
        $this->serper = $serper;
        $this->openai = $openai;
        $this->engine = $engine;
    }

    /**
     * Generate a keyword plan for a set of keywords.
     *
     * @param array  $keywords Seed keywords.
     * @param string $locale   Locale string.
     *
     * @return array|WP_Error
     */
    public function generate_keyword_plan(array $keywords, $locale = 'us')
    {
        $plan = [];

        foreach ($keywords as $keyword) {
            $keyword = sanitize_text_field($keyword);

            if (empty($keyword)) {
                continue;
            }

            $results = $this->serper->search($keyword, $locale);

            if (is_wp_error($results)) {
                return $results;
            }

            $plan[] = $this->engine->build_clusters($results, $keyword);
        }

        return [
            'locale'   => $locale,
            'keywords' => $plan,
        ];
    }

    /**
     * Generate an OpenAI content outline.
     *
     * @param string $topic   Article topic.
     * @param array  $outline Optional outline hints.
     *
     * @return string|WP_Error
     */
    public function generate_content_outline($topic, array $outline = [])
    {
        $messages = [
            [
                'role'    => 'system',
                'content' => 'You are an SEO strategist generating concise outlines with title, intro bullet, and section list.',
            ],
            [
                'role'    => 'user',
                'content' => wp_json_encode([
                    'topic'   => sanitize_text_field($topic),
                    'outline' => array_map('sanitize_text_field', $outline),
                ]),
            ],
        ];

        return $this->openai->chat($messages);
    }
}
