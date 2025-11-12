<div class="dns-wrap">
    <div class="dns-card">
        <h3 class="dns-title">Subscribe to Notifications</h3>
        <p class="dns-sub">Pick the categories and cities you care about. We’ll notify you when new listings match.</p>
        <?= $msg; ?>
        <form method="post">
            <?php wp_nonce_field('np_save_prefs','np_nonce'); ?>

            <div class="dns-grid">
                <!-- Product Categories -->
                <div class="dns-field">
                    <label class="dns-label">Product Category (directory_type)</label>
                    <select class="dns-select" name="np_products[]" multiple>
                        <?php foreach ($product_terms as $t): ?>
                            <option value="<?= esc_attr($t->term_id); ?>" <?= in_array($t->term_id, $saved_products, true) ? 'selected' : ''; ?>>
                                <?= esc_html($t->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="dns-help">Terms from taxonomy <code>directory_type</code> for <code>at_biz_dir</code>.</span>
                </div>

                <!-- Job Categories -->
                <div class="dns-field">
                    <label class="dns-label">Job Category</label>
                    <div class="dns-chiplist">
                        <?php foreach ($job_options as $job): 
                            $checked = in_array($job, $saved_jobs, true) ? 'checked' : ''; ?>
                            <label>
                                <input type="checkbox" name="np_jobs[]" value="<?= esc_attr($job); ?>" <?= $checked; ?> />
                                <span><?= esc_html($job); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="dns-chips">
                        <?php foreach ($saved_jobs as $j): ?>
                            <span class="dns-chip"><?= esc_html($j); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <span class="dns-help">Quick list; edit this array in the plugin file to add more.</span>
                </div>

                <!-- Cities -->
                <div class="dns-field" style="grid-column:1/-1">
                    <label class="dns-label">City (at_biz_dir-location)</label>
                    <select class="dns-select" name="np_cities[]" multiple>
                        <?php foreach ($city_terms as $t): ?>
                            <option value="<?= esc_attr($t->term_id); ?>" <?= in_array($t->term_id, $saved_cities, true) ? 'selected' : ''; ?>>
                                <?= esc_html($t->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="dns-actions">
                <button class="dns-btn dns-btn--primary" type="submit" name="np_save" value="1">Save Preferences</button>
                <button class="dns-btn dns-btn--ghost" type="reset">Reset</button>
            </div>
        </form>
    </div>
</div>
