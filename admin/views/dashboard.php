<?php
/** @var TMW\SA100\Classes\Settings $settings */
?>
<div class="wrap tmw-sa100-wrap">
    <h1><?php esc_html_e('TMW SEO Autopilot 100', 'tmw-seo-autopilot-100'); ?></h1>

    <div class="tmw-sa100-card">
        <span class="tmw-sa100-badge"><?php esc_html_e('Phase 1', 'tmw-seo-autopilot-100'); ?></span>
        <h2><?php esc_html_e('Serper + OpenAI Automation', 'tmw-seo-autopilot-100'); ?></h2>
        <p><?php esc_html_e('Automate your keyword research and AI-assisted outline creation with streamlined workflows.', 'tmw-seo-autopilot-100'); ?></p>
    </div>

    <div class="tmw-sa100-grid">
        <div class="tmw-sa100-card">
            <h3><?php esc_html_e('Keyword Intelligence', 'tmw-seo-autopilot-100'); ?></h3>
            <p><?php esc_html_e('Serper analysis converts live SERP data into actionable keyword clusters.', 'tmw-seo-autopilot-100'); ?></p>
        </div>

        <div class="tmw-sa100-card">
            <h3><?php esc_html_e('OpenAI Content Drafts', 'tmw-seo-autopilot-100'); ?></h3>
            <p><?php esc_html_e('Jumpstart content briefs with contextual outlines ready for editorial refinement.', 'tmw-seo-autopilot-100'); ?></p>
        </div>

        <div class="tmw-sa100-card">
            <h3><?php esc_html_e('Integrations Dashboard', 'tmw-seo-autopilot-100'); ?></h3>
            <p><?php esc_html_e('Manage API credentials, locales, and workflow defaults in one interface.', 'tmw-seo-autopilot-100'); ?></p>
        </div>
    </div>

    <div class="tmw-sa100-card">
        <h3><?php esc_html_e('Quick Actions', 'tmw-seo-autopilot-100'); ?></h3>
        <ul>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=tmw-sa100-keyword-engine')); ?>"><?php esc_html_e('Launch Keyword Engine', 'tmw-seo-autopilot-100'); ?></a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=tmw-sa100-integrations')); ?>"><?php esc_html_e('Configure Integrations', 'tmw-seo-autopilot-100'); ?></a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=tmw-sa100-diagnostics')); ?>"><?php esc_html_e('Run Diagnostics', 'tmw-seo-autopilot-100'); ?></a></li>
        </ul>
    </div>
</div>
