<?php
namespace DNS\Frontend;

if (!defined('ABSPATH')) exit;

class Shortcode {

    public function __construct() {
        add_shortcode('notification_system', [$this, 'render']);
    }

    /**
     * Render shortcode
     */
    public function render() {
        if (!is_user_logged_in()) {
            return '<div class="dns-card"><p>You must be logged in to save preferences.</p></div>';
        }

        $user_id = get_current_user_id();

        // --- Get data ---
        $at_biz_dir = get_posts([
            'post_type' => 'at_biz_dir',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        $city_terms = get_terms(['taxonomy' => 'at_biz_dir-location', 'hide_empty' => false]);
        $product_terms = get_terms(['taxonomy' => 'at_biz_dir-tags', 'hide_empty' => false]);

        // --- Load saved data ---
        $saved = get_user_meta($user_id, 'dns_notify_prefs', true);
        $saved = is_array($saved) ? $saved : [];

        // --- Handle form submission ---
        $msg = '';
        if (!empty($_POST['np_save']) && check_admin_referer('np_save_prefs', 'np_nonce')) {
            $saved = [
                'product_enabled' => !empty($_POST['np_product_enabled']),
                'products'        => isset($_POST['np_products']) ? array_map('intval', (array) $_POST['np_products']) : [],
                'job_enabled'     => !empty($_POST['np_job_enabled']),
                'jobs'            => isset($_POST['np_jobs']) ? array_map('intval', (array) $_POST['np_jobs']) : [],
                'city_enabled'    => !empty($_POST['np_city_enabled']),
                'cities'          => isset($_POST['np_cities']) ? array_map('intval', (array) $_POST['np_cities']) : [],
            ];
            update_user_meta($user_id, 'dns_notify_prefs', $saved);
            $msg = '<div class="dns-alert">Preferences saved 🎉</div>';
        }

        ob_start(); ?>

        <div class="dns-wrap">
            <div class="dns-card">
                <h3 class="dns-title">Subscribe to Notifications</h3>
                <p class="dns-sub">Pick the categories and cities you care about. We’ll notify you when new listings match.</p>
                <?= $msg; ?>

                <!-- Tabs -->
                <div class="dns-tabs">
                    <button class="dns-tab-btn active" data-tab="jobs">Jobs</button>
                    <button class="dns-tab-btn" data-tab="products">Products</button>
                    <button class="dns-tab-btn" data-tab="cities">Cities</button>
                </div>

                <form method="post">
                    <?php wp_nonce_field('np_save_prefs','np_nonce'); ?>

                    <!-- Jobs Tab -->
                    <div class="dns-tab-content active" id="tab-jobs">
                        <div class="dns-field">
                            <label class="dns-label">
                                <input type="checkbox" name="np_job_enabled" id="np_job_enabled" <?= !empty($saved['job_enabled']) ? 'checked' : ''; ?> />
                                Enable Job Notifications
                            </label>
                            <select class="dns-select" name="np_jobs[]" id="np_jobs" multiple <?= empty($saved['job_enabled']) ? 'disabled' : ''; ?>>
                                <?php foreach ($at_biz_dir as $biz): ?>
                                    <option value="<?= esc_attr($biz->ID); ?>" <?= isset($saved['jobs']) && in_array($biz->ID, $saved['jobs']) ? 'selected' : ''; ?>>
                                        <?= esc_html(get_the_title($biz)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Products Tab -->
                    <div class="dns-tab-content" id="tab-products">
                        <div class="dns-field">
                            <label class="dns-label">
                                <input type="checkbox" name="np_product_enabled" id="np_product_enabled" <?= !empty($saved['product_enabled']) ? 'checked' : ''; ?> />
                                Enable Product Notifications
                            </label>
                            <select class="dns-select" name="np_products[]" id="np_products" multiple <?= empty($saved['product_enabled']) ? 'disabled' : ''; ?>>
                                <?php foreach ($product_terms as $t): ?>
                                    <option value="<?= esc_attr($t->term_id); ?>" <?= isset($saved['products']) && in_array($t->term_id, $saved['products']) ? 'selected' : ''; ?>>
                                        <?= esc_html($t->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Cities Tab -->
                    <div class="dns-tab-content" id="tab-cities">
                        <div class="dns-field">
                            <label class="dns-label">
                                <input type="checkbox" name="np_city_enabled" id="np_city_enabled" <?= !empty($saved['city_enabled']) ? 'checked' : ''; ?> />
                                Enable City Notifications
                            </label>
                            <select class="dns-select" name="np_cities[]" id="np_cities" multiple <?= empty($saved['city_enabled']) ? 'disabled' : ''; ?>>
                                <?php foreach ($city_terms as $t): ?>
                                    <option value="<?= esc_attr($t->term_id); ?>" <?= isset($saved['cities']) && in_array($t->term_id, $saved['cities']) ? 'selected' : ''; ?>>
                                        <?= esc_html($t->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="dns-actions">
                        <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">Save Preferences</button>
                        <button class="dns-btn dns-btn--ghost" type="reset">Reset</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}
