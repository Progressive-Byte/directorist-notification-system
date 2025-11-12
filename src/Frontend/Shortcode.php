<?php
namespace DNS\Frontend;

if (!defined('ABSPATH')) exit;

class Shortcode {

    public function __construct() {
        add_shortcode( 'notification_system', [$this, 'render'] );
    }

    /**
     * Render shortcode
     */
    public function render() {
        wp_enqueue_style('dns-notification-preferences');

        if (!is_user_logged_in()) {
            return '<div class="dns-card"><p>You must be logged in to save preferences.</p></div>';
        }

        $user_id = get_current_user_id();

        // Options
        $job_options = ['Carpenter','Painter','Electrician','Plumber','Mechanic','Mason','Welder','Driver','Helper','Technician'];
        $product_terms = get_terms(['taxonomy'=>'directory_type','hide_empty'=>false]);
        $city_terms = get_terms(['taxonomy'=>'at_biz_dir-location','hide_empty'=>false]);

        // Load saved data from user_meta
        $saved = get_user_meta($user_id, 'dns_notify_prefs', true);
        $saved = is_array($saved) ? $saved : [];

        // Handle form submission
        $msg = '';
        if (!empty($_POST['np_save']) && check_admin_referer('np_save_prefs','np_nonce')) {
            $saved = [
                'product_enabled' => !empty($_POST['np_product_enabled']),
                'products' => isset($_POST['np_products']) ? array_map('intval', (array) $_POST['np_products']) : [],
                'job_enabled' => !empty($_POST['np_job_enabled']),
                'jobs' => isset($_POST['np_jobs']) ? array_map('sanitize_text_field', (array) $_POST['np_jobs']) : [],
                'city_enabled' => !empty($_POST['np_city_enabled']),
                'cities' => isset($_POST['np_cities']) ? array_map('intval', (array) $_POST['np_cities']) : [],
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

                <!-- Tab content -->

                <!-- Jobs Tab -->
                <div class="dns-tab-content active" id="tab-jobs">
                    <div class="dns-field">
                        <label class="dns-label">
                            <input type="checkbox" name="np_job_enabled" <?= !empty($saved['job_enabled']) ? 'checked' : ''; ?> /> Enable Job Notifications
                        </label>
                        <div class="dns-chiplist">
                            <?php foreach ($job_options as $job):
                                $checked = isset($saved['jobs']) && in_array($job, $saved['jobs']) ? 'checked' : ''; ?>
                                <label>
                                    <input type="checkbox" name="np_jobs[]" value="<?= esc_attr($job); ?>" <?= $checked; ?> />
                                    <span><?= esc_html($job); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Products Tab -->
                <div class="dns-tab-content" id="tab-products">
                    <div class="dns-field">
                        <label class="dns-label">
                            <input type="checkbox" name="np_product_enabled" <?= !empty($saved['product_enabled']) ? 'checked' : ''; ?> /> Enable Product Notifications
                        </label>
                        <select class="dns-select" name="np_products[]" multiple <?= empty($saved['product_enabled']) ? 'disabled' : ''; ?>>
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
                        <!-- Toggle Checkbox -->
                        <label class="dns-label">
                            <input type="checkbox" name="np_city_enabled" id="np_city_enabled" <?= !empty($saved['city_enabled']) ? 'checked' : ''; ?> />
                            Enable City Notifications
                        </label>

                        <!-- City Select -->
                        <select class="dns-select" name="np_cities[]" multiple <?= empty($saved['city_enabled']) ? 'disabled' : ''; ?> id="np_cities_select">
                            <?php foreach ($city_terms as $t): ?>
                                <option value="<?= esc_attr($t->term_id); ?>" <?= isset($saved['cities']) && in_array($t->term_id, $saved['cities']) ? 'selected' : ''; ?>>
                                    <?= esc_html($t->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>


                <!-- Action buttons -->
                <div class="dns-actions">
                    <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">Save Preferences</button>
                    <button class="dns-btn dns-btn--ghost" type="reset">Reset</button>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

}
