<?php
namespace Smackcoders\Aioml;

if (!defined('ABSPATH')) {
    exit;
}

function aioml_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'aio-media-library-manager'));
    }

    $r2_enabled = (bool) get_option('aioml_r2_enabled', false);
    $r2_access_key = get_option('aioml_r2_access_key', '');
    $r2_bucket = get_option('aioml_r2_bucket', '');
    $r2_account_id = get_option('aioml_r2_account_id', '');
    $r2_public_url = get_option('aioml_r2_public_url', '');
    $s3_enabled = (bool) get_option('aioml_s3_enabled', false);
    $s3_access_key = get_option('aioml_s3_access_key', '');
    $s3_bucket = get_option('aioml_s3_bucket', '');
    $s3_region = get_option('aioml_s3_region', '');
    $s3_public_url = get_option('aioml_s3_public_url', '');
    $active_provider = get_option('aioml_cdn_provider', '');
    $is_r2_saved = $r2_enabled && $r2_access_key && $r2_bucket && $r2_account_id;
    $is_s3_saved = $s3_enabled && $s3_access_key && $s3_bucket && $s3_region;
    $r2_badge_label = $active_provider === 'r2' && $is_r2_saved ? __('Active', 'aio-media-library-manager') : ($is_r2_saved ? __('Configured', 'aio-media-library-manager') : __('Not Configured', 'aio-media-library-manager'));
    $s3_badge_label = $active_provider === 's3' && $is_s3_saved ? __('Active', 'aio-media-library-manager') : ($is_s3_saved ? __('Configured', 'aio-media-library-manager') : __('Not Configured', 'aio-media-library-manager'));
    $aws_s3_regions = [
        'us-east-1' => 'US East (N. Virginia)',
        'us-east-2' => 'US East (Ohio)',
        'us-west-1' => 'US West (N. California)',
        'us-west-2' => 'US West (Oregon)',
        'af-south-1' => 'Africa (Cape Town)',
        'ap-east-1' => 'Asia Pacific (Hong Kong)',
        'ap-south-1' => 'Asia Pacific (Mumbai)',
        'ap-south-2' => 'Asia Pacific (Hyderabad)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'ap-southeast-3' => 'Asia Pacific (Jakarta)',
        'ap-southeast-4' => 'Asia Pacific (Melbourne)',
        'ap-southeast-5' => 'Asia Pacific (Malaysia)',
        'ap-southeast-7' => 'Asia Pacific (Thailand)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ap-northeast-2' => 'Asia Pacific (Seoul)',
        'ap-northeast-3' => 'Asia Pacific (Osaka)',
        'ca-central-1' => 'Canada (Central)',
        'ca-west-1' => 'Canada West (Calgary)',
        'eu-central-1' => 'Europe (Frankfurt)',
        'eu-central-2' => 'Europe (Zurich)',
        'eu-west-1' => 'Europe (Ireland)',
        'eu-west-2' => 'Europe (London)',
        'eu-west-3' => 'Europe (Paris)',
        'eu-south-1' => 'Europe (Milan)',
        'eu-south-2' => 'Europe (Spain)',
        'eu-north-1' => 'Europe (Stockholm)',
        'il-central-1' => 'Israel (Tel Aviv)',
        'me-south-1' => 'Middle East (Bahrain)',
        'me-central-1' => 'Middle East (UAE)',
        'mx-central-1' => 'Mexico (Central)',
        'sa-east-1' => 'South America (Sao Paulo)',
    ];
    ?>
    <div class="wrap aioml-connectors-wrap">
        <div class="aioml-page-header">
            <h1><?php esc_html_e('Connectors', 'aio-media-library-manager'); ?></h1>
            <p><?php esc_html_e('Live, two-way bridges between AIOML and the tools your media lives in.', 'aio-media-library-manager'); ?></p>
        </div>

        <div class="aioml-connectors-grid">
            
            <!-- R2 Card -->
            <div class="aioml-connector-panel">
                <div class="aioml-connector-logo aioml-logo-r2">R2</div>
                <div class="aioml-connector-info">
                    <h3><?php esc_html_e('Cloudflare R2', 'aio-media-library-manager'); ?></h3>
                    <p><?php esc_html_e('Store R2 S3-compatible bucket credentials.', 'aio-media-library-manager'); ?></p>
                </div>
                <div class="aioml-connector-footer">
                    <span class="aioml-pill <?php echo esc_attr($active_provider === 'r2' && $is_r2_saved ? 'is-active' : ($is_r2_saved ? 'is-active' : 'is-muted')); ?>" id="aioml-r2-status" data-configured="<?php echo esc_attr($is_r2_saved ? '1' : '0'); ?>">
                        <?php echo esc_html($r2_badge_label); ?>
                    </span>
                    <button type="button" class="aioml-btn <?php echo esc_attr($is_r2_saved ? 'aioml-btn-default' : 'aioml-btn-primary'); ?> aioml-btn-sm" id="aioml-open-r2-modal">
                        <?php echo esc_html($is_r2_saved ? __('Manage', 'aio-media-library-manager') : __('Connect', 'aio-media-library-manager')); ?>
                    </button>
                </div>
            </div>

            <!-- S3 Card -->
            <div class="aioml-connector-panel">
                <div class="aioml-connector-logo aioml-logo-s3">S3</div>
                <div class="aioml-connector-info">
                    <h3><?php esc_html_e('AWS S3', 'aio-media-library-manager'); ?></h3>
                    <p><?php esc_html_e('Store AWS S3 access keys and bucket details.', 'aio-media-library-manager'); ?></p>
                </div>
                <div class="aioml-connector-footer">
                    <span class="aioml-pill <?php echo esc_attr($active_provider === 's3' && $is_s3_saved ? 'is-active' : ($is_s3_saved ? 'is-active' : 'is-muted')); ?>" id="aioml-s3-status" data-configured="<?php echo esc_attr($is_s3_saved ? '1' : '0'); ?>">
                        <?php echo esc_html($s3_badge_label); ?>
                    </span>
                    <button type="button" class="aioml-btn <?php echo esc_attr($is_s3_saved ? 'aioml-btn-default' : 'aioml-btn-primary'); ?> aioml-btn-sm" id="aioml-open-s3-modal">
                        <?php echo esc_html($is_s3_saved ? __('Manage', 'aio-media-library-manager') : __('Connect', 'aio-media-library-manager')); ?>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- R2 Modal -->
    <div class="aioml-modal-backdrop" id="aioml-r2-modal" hidden>
        <div class="aioml-modal" role="dialog" aria-modal="true" aria-labelledby="aioml-r2-modal-title">
            <div class="aioml-modal-header">
                <div class="aioml-modal-title-group">
                    <div class="aioml-connector-logo aioml-logo-r2 aioml-logo-sm">R2</div>
                    <div>
                        <h2 id="aioml-r2-modal-title"><?php esc_html_e('Cloudflare R2', 'aio-media-library-manager'); ?></h2>
                        <p><?php echo esc_html($is_r2_saved ? __('Update saved credentials', 'aio-media-library-manager') : __('Add credentials', 'aio-media-library-manager')); ?></p>
                    </div>
                </div>
                <button type="button" class="aioml-modal-close" aria-label="<?php esc_attr_e('Close modal', 'aio-media-library-manager'); ?>">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form id="aioml-r2-form">
                <div class="aioml-modal-body">
                    <div class="aioml-settings-grid">
                        <label class="aioml-field">
                            <span><?php esc_html_e('Access Key ID', 'aio-media-library-manager'); ?> <span class="aioml-required">*</span></span>
                            <input type="text" name="access_key" value="<?php echo esc_attr($r2_access_key); ?>" required>
                        </label>
                        <label class="aioml-field">
                            <span><?php esc_html_e('Secret Access Key', 'aio-media-library-manager'); ?> <span class="aioml-required">*</span></span>
                            <span class="aioml-password-field">
                                <input type="password" name="secret_key" value="" placeholder="<?php esc_attr_e('Enter secret key', 'aio-media-library-manager'); ?>" data-has-secret="<?php echo esc_attr(get_option('aioml_r2_secret_key', '') ? '1' : '0'); ?>">
                                <button type="button" class="aioml-btn aioml-toggle-secret"><?php esc_html_e('Show', 'aio-media-library-manager'); ?></button>
                            </span>
                        </label>
                        <label class="aioml-field">
                            <span><?php esc_html_e('Account ID', 'aio-media-library-manager'); ?> <span class="aioml-required">*</span></span>
                            <input type="text" name="account_id" value="<?php echo esc_attr($r2_account_id); ?>" required>
                        </label>
                        <label class="aioml-field">
                            <span><?php esc_html_e('Bucket Name', 'aio-media-library-manager'); ?> <span class="aioml-required">*</span></span>
                            <input type="text" name="bucket" value="<?php echo esc_attr($r2_bucket); ?>" required>
                        </label>
                        <label class="aioml-field aioml-field-full">
                            <span><?php esc_html_e('Region', 'aio-media-library-manager'); ?></span>
                            <select name="region">
                                <option value="auto" selected><?php esc_html_e('auto (R2 / S3-compatible)', 'aio-media-library-manager'); ?></option>
                            </select>
                        </label>
                        <label class="aioml-field aioml-field-full">
                            <span><?php esc_html_e('Public URL / Custom Domain', 'aio-media-library-manager'); ?></span>
                            <input type="url" name="public_url" value="<?php echo esc_attr($r2_public_url); ?>" placeholder="https://pub-xxxxx.r2.dev">
                        </label>
                    </div>

                    <div class="aioml-inline-result" id="aioml-r2-result" aria-live="polite"></div>
                </div>

                <div class="aioml-modal-footer">
                    <button type="button" class="aioml-btn aioml-modal-close"><?php esc_html_e('Cancel', 'aio-media-library-manager'); ?></button>
                    <button type="button" class="aioml-btn" id="aioml-test-r2"><?php esc_html_e('Test Connection', 'aio-media-library-manager'); ?></button>
                    <button type="submit" class="aioml-btn aioml-btn-primary" id="aioml-save-r2"><?php esc_html_e('Save Credentials', 'aio-media-library-manager'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- S3 Modal -->
    <div class="aioml-modal-backdrop" id="aioml-s3-modal" hidden>
        <div class="aioml-modal" role="dialog" aria-modal="true" aria-labelledby="aioml-s3-modal-title">
            <div class="aioml-modal-header">
                <div class="aioml-modal-title-group">
                    <div class="aioml-connector-logo aioml-logo-s3 aioml-logo-sm">S3</div>
                    <div>
                        <h2 id="aioml-s3-modal-title"><?php esc_html_e('AWS S3', 'aio-media-library-manager'); ?></h2>
                        <p><?php echo esc_html($is_s3_saved ? __('Update saved credentials', 'aio-media-library-manager') : __('Add credentials', 'aio-media-library-manager')); ?></p>
                    </div>
                </div>
                <button type="button" class="aioml-modal-close" aria-label="<?php esc_attr_e('Close modal', 'aio-media-library-manager'); ?>">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form id="aioml-s3-form">
                <div class="aioml-modal-body">
                    <div class="aioml-settings-grid">
                        <label class="aioml-field">
                            <span><?php esc_html_e('Access Key ID', 'aio-media-library-manager'); ?> <span class="aioml-required">*</span></span>
                            <input type="text" name="access_key" value="<?php echo esc_attr($s3_access_key); ?>" required>
                        </label>
                        <label class="aioml-field">
                            <span><?php esc_html_e('Secret Access Key', 'aio-media-library-manager'); ?> <span class="aioml-required">*</span></span>
                            <span class="aioml-password-field">
                                <input type="password" name="secret_key" value="" placeholder="<?php esc_attr_e('Enter secret key', 'aio-media-library-manager'); ?>" data-has-secret="<?php echo esc_attr(get_option('aioml_s3_secret_key', '') ? '1' : '0'); ?>">
                                <button type="button" class="aioml-btn aioml-toggle-secret"><?php esc_html_e('Show', 'aio-media-library-manager'); ?></button>
                            </span>
                        </label>
                        <label class="aioml-field">
                            <span><?php esc_html_e('Bucket Name', 'aio-media-library-manager'); ?> <span class="aioml-required">*</span></span>
                            <input type="text" name="bucket" value="<?php echo esc_attr($s3_bucket); ?>" required>
                        </label>
                        <label class="aioml-field">
                            <span><?php esc_html_e('Region', 'aio-media-library-manager'); ?> <span class="aioml-required">*</span></span>
                            <select name="region" required>
                                <option value=""><?php esc_html_e('Select AWS S3 region', 'aio-media-library-manager'); ?></option>
                                <?php foreach ($aws_s3_regions as $region_code => $region_name) : ?>
                                    <option value="<?php echo esc_attr($region_code); ?>" <?php selected($s3_region ?: 'ap-south-1', $region_code); ?>>
                                        <?php echo esc_html($region_name . ' - ' . $region_code); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="aioml-field aioml-field-full">
                            <span><?php esc_html_e('Public URL / Custom Domain', 'aio-media-library-manager'); ?></span>
                            <input type="url" name="public_url" value="<?php echo esc_attr($s3_public_url); ?>" placeholder="https://cdn.example.com">
                        </label>
                    </div>

                    <div class="aioml-inline-result" id="aioml-s3-result" aria-live="polite"></div>
                </div>

                <div class="aioml-modal-footer">
                    <button type="button" class="aioml-btn aioml-modal-close"><?php esc_html_e('Cancel', 'aio-media-library-manager'); ?></button>
                    <button type="button" class="aioml-btn" id="aioml-test-s3"><?php esc_html_e('Test Connection', 'aio-media-library-manager'); ?></button>
                    <button type="submit" class="aioml-btn aioml-btn-primary" id="aioml-save-s3"><?php esc_html_e('Save Credentials', 'aio-media-library-manager'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        /* CSS Variables matching Audie */
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --ink: #0f172a;
            --ink-muted: #64748b;
            --hairline: #e2e8f0;
            --hairline-soft: #f1f5f9;
            --shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.04);
            --primary: #3d4fdb;
            --primary-hover: #2f3cb0;
            --emerald-50: #ecfdf5;
            --emerald-500: #10b981;
            --emerald-700: #047857;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --rose-500: #f43f5e;
            --font-sans: 'Inter', sans-serif;
            --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .aioml-connectors-wrap {
            font-family: var(--font-sans) !important;
            margin: 0;
            padding: 16px 0px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            /* background: var(--bg); */
            min-height: calc(100vh - 32px);
        }

        .aioml-page-header {
            margin-bottom: 28px;
        }
        
        .aioml-page-header h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: -0.02em;
            font-weight: 800;
            line-height: 1.1;
            color: var(--ink);
            font-family: var(--font-sans);
        }
        
        .aioml-page-header p {
            margin: 6px 0 0 0;
            font-size: 14px;
            color: var(--ink-muted);
            font-family: var(--font-sans);
        }

        .aioml-connectors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .aioml-connector-panel {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: var(--panel);
            border-radius: 12px;
            border: 1px solid var(--hairline);
            box-shadow: var(--shadow-xs);
        }

        .aioml-connector-logo {
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 800;
            letter-spacing: -0.05em;
        }
        
        /* Specific logos */
        .aioml-logo-r2 {
            background: #f97316;
            width: 42px;
            height: 42px;
            font-size: 21px;
        }
        .aioml-logo-s3 {
            background: #0f172a;
            width: 42px;
            height: 42px;
            font-size: 21px;
        }

        .aioml-connector-info h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
        }
        
        .aioml-connector-info p {
            margin: 4px 0 0;
            font-size: 12.5px;
            color: var(--ink-muted);
            line-height: 1.5;
            min-height: 38px;
        }

        .aioml-connector-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--hairline-soft);
        }

        .aioml-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .aioml-pill.is-active {
            background: var(--emerald-50);
            color: var(--emerald-700);
        }
        
        .aioml-pill.is-active::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--emerald-500);
            margin-right: 6px;
        }

        .aioml-pill.is-muted {
            background: var(--slate-100);
            color: var(--ink-muted);
        }

        /* Buttons */
        .aioml-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-sans);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .aioml-btn-sm {
            height: 28px;
            padding: 0 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        .aioml-btn-primary {
            background: var(--primary);
            color: #fff;
            border: 1px solid var(--primary);
            box-shadow: 0 1px 2px rgba(61, 79, 219, 0.2);
        }
        .aioml-btn-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .aioml-btn-default {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--hairline);
            box-shadow: var(--shadow-xs);
        }
        .aioml-btn-default:hover {
            background: var(--slate-50);
            border-color: #cbd5e1;
        }

        /* Modal / Dialog */
        .aioml-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 100000;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .aioml-modal-backdrop[hidden] { display: none; }
        
        .aioml-modal {
            width: min(580px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .aioml-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--hairline);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .aioml-modal-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .aioml-logo-sm {
            width: 36px !important;
            height: 36px !important;
            font-size: 18px !important;
        }

        .aioml-modal-header h2 {
            margin: 0 0 2px 0;
            font-size: 18px;
            font-weight: 800;
            color: var(--ink);
        }
        .aioml-modal-header p {
            margin: 0;
            color: var(--ink-muted);
            font-size: 12px;
        }

        .aioml-modal-close {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            display: grid;
            place-items: center;
            transition: all 0.2s ease;
        }
        .aioml-modal-close:hover {
            color: var(--ink);
            background: var(--slate-100);
        }

        .aioml-modal-body {
            padding: 24px;
        }

        .aioml-settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .aioml-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .aioml-field-full {
            grid-column: span 2;
        }

        .aioml-field > span:first-child {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .aioml-required {
            color: var(--rose-500);
        }

        .aioml-field input[type="text"], 
        .aioml-field input[type="url"], 
        .aioml-field input[type="password"], 
        .aioml-field select {
            width: 100%;
            height: 42px;
            padding: 0 12px;
            border-radius: 8px;
            border: 1px solid var(--hairline);
            background: var(--panel);
            color: var(--ink);
            font-size: 13.5px;
            font-family: var(--font-sans);
            box-shadow: var(--shadow-xs);
            box-sizing: border-box;
            transition: all 0.2s;
        }

        .aioml-field input[type="password"] {
            font-family: var(--font-mono);
        }

        .aioml-field input:focus, .aioml-field select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(61, 79, 219, 0.1);
        }

        .aioml-password-field {
            display: flex;
            gap: 8px;
        }
        .aioml-password-field input {
            flex: 1;
        }
        .aioml-password-field .aioml-btn {
            height: 42px;
            padding: 0 16px;
            border-radius: 8px;
            border: 1px solid var(--hairline);
            background: var(--panel);
            color: var(--ink);
            box-shadow: var(--shadow-xs);
            font-size: 13.5px;
        }
        .aioml-password-field .aioml-btn:hover {
            background: var(--slate-50);
            border-color: #cbd5e1;
        }

        .aioml-inline-result {
            margin-top: 16px;
            font-size: 14px;
            font-weight: 600;
            grid-column: span 2;
        }
        .aioml-inline-result.is-success { color: var(--emerald-700); }
        .aioml-inline-result.is-error { color: var(--rose-500); }

        .aioml-modal-footer {
            padding: 16px 24px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }
        
        .aioml-modal-footer .aioml-btn {
            height: 38px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .aioml-modal-footer .aioml-btn:not(.aioml-btn-primary) {
            background: transparent;
            color: var(--ink-muted);
            border: 1px solid transparent;
        }
        
        .aioml-modal-footer .aioml-btn:not(.aioml-btn-primary):hover {
            background: var(--slate-100);
            color: var(--ink);
        }
        
        .aioml-modal-footer #aioml-test-r2,
        .aioml-modal-footer #aioml-test-s3 {
            border: 1px solid var(--hairline) !important;
            box-shadow: var(--shadow-xs);
        }
        
        .aioml-modal-footer #aioml-test-r2:hover,
        .aioml-modal-footer #aioml-test-s3:hover {
            background: var(--slate-50) !important;
            border-color: #cbd5e1 !important;
        }
    </style>
    <?php
}
