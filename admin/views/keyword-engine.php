<?php
/** @var TMW\SA100\Classes\Task_Runner $task_runner */
?>
<div class="wrap tmw-sa100-wrap">
    <h1><?php esc_html_e('Keyword Engine', 'tmw-seo-autopilot-100'); ?></h1>

    <div class="tmw-sa100-card">
        <p><?php esc_html_e('Generate keyword clusters from Serper data and export them to your editorial pipeline.', 'tmw-seo-autopilot-100'); ?></p>

        <form id="tmw-sa100-keyword-form" data-loading="<?php esc_attr_e('Crunching keywords…', 'tmw-seo-autopilot-100'); ?>">
            <?php wp_nonce_field('tmw-sa100-keyword', 'tmw_sa100_keyword_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="tmw-sa100-keywords"><?php esc_html_e('Seed Keywords', 'tmw-seo-autopilot-100'); ?></label></th>
                    <td>
                        <textarea name="keywords" id="tmw-sa100-keywords" rows="5" class="large-text" placeholder="seo automation\nwordpress ai"></textarea>
                        <p class="description"><?php esc_html_e('Enter one keyword per line for best results.', 'tmw-seo-autopilot-100'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tmw-sa100-locale"><?php esc_html_e('Locale', 'tmw-seo-autopilot-100'); ?></label></th>
                    <td>
                        <select name="locale" id="tmw-sa100-locale">
                            <option value="us">US</option>
                            <option value="uk">UK</option>
                            <option value="de">DE</option>
                            <option value="es">ES</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Generate Plan', 'tmw-seo-autopilot-100')); ?>
        </form>

        <pre class="tmw-sa100-output"></pre>
    </div>
</div>
