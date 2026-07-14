<?php
namespace Smackcoders\Aioml;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

if (!defined('ABSPATH')) {
    exit;
}

class Aioml_Cdn {
    private static $instance = null;
    private $providers = ['r2', 's3'];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $autoload = plugin_dir_path(__DIR__) . 'vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        add_action('wp_ajax_aioml_test_r2_connection', [$this, 'test_r2_connection']);
        add_action('wp_ajax_aioml_save_r2_credentials', [$this, 'save_r2_credentials']);
        add_action('wp_ajax_aioml_test_s3_connection', [$this, 'test_s3_connection']);
        add_action('wp_ajax_aioml_save_s3_credentials', [$this, 'save_s3_credentials']);

        add_action('add_attachment', [$this, 'upload_attachment_original_to_cdn']);
        add_filter('wp_generate_attachment_metadata', [$this, 'upload_attachment_sizes_to_cdn'], 20, 2);
        add_filter('wp_get_attachment_url', [$this, 'rewrite_attachment_url'], 20, 2);
        add_filter('wp_calculate_image_srcset', [$this, 'rewrite_image_srcset'], 20, 5);
    }

    public function test_r2_connection() {
        $this->test_connection('r2');
    }

    public function save_r2_credentials() {
        $this->save_credentials('r2');
    }

    public function test_s3_connection() {
        $this->test_connection('s3');
    }

    public function save_s3_credentials() {
        $this->save_credentials('s3');
    }

    public function upload_attachment_original_to_cdn($attachment_id) {
        if (!$this->is_cdn_active()) {
            return;
        }

        $file_path = get_attached_file($attachment_id);
        if (!$file_path) {
            return;
        }

        $object_key = $this->upload_file_to_cdn($file_path);
        if ($object_key) {
            $this->save_attachment_cdn_meta($attachment_id, $object_key);
        }
    }

    public function upload_attachment_sizes_to_cdn($metadata, $attachment_id) {
        if (!$this->is_cdn_active()) {
            return $metadata;
        }

        $file_path = get_attached_file($attachment_id);
        if (!$file_path) {
            return $metadata;
        }

        $main_key = $this->upload_file_to_cdn($file_path);
        if ($main_key) {
            $this->save_attachment_cdn_meta($attachment_id, $main_key);
        }

        if (empty($metadata['sizes']) || !is_array($metadata['sizes'])) {
            return $metadata;
        }

        $base_dir = trailingslashit(dirname($file_path));
        foreach ($metadata['sizes'] as $size) {
            if (!empty($size['file'])) {
                $this->upload_file_to_cdn($base_dir . $size['file']);
            }
        }

        return $metadata;
    }

    public function rewrite_attachment_url($url, $attachment_id) {
        if (!$this->is_cdn_active()) {
            return $url;
        }

        $object_key = get_post_meta($attachment_id, '_aioml_cdn_key', true);
        if (!$object_key) {
            $object_key = get_post_meta($attachment_id, '_aioml_r2_key', true);
        }
        if (!$object_key) {
            $object_key = $this->get_object_key_from_path(get_attached_file($attachment_id));
        }

        if (!$object_key) {
            return $url;
        }

        $cdn_url = $this->build_public_url($object_key);
        return $cdn_url ?: $url;
    }

    public function rewrite_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (!$this->is_cdn_active() || empty($sources) || !is_array($sources)) {
            return $sources;
        }

        $uploads = wp_get_upload_dir();
        $base_url = trailingslashit($uploads['baseurl']);

        foreach ($sources as $width => $source) {
            if (empty($source['url']) || strpos($source['url'], $base_url) !== 0) {
                continue;
            }

            $relative = ltrim(str_replace($base_url, '', $source['url']), '/');
            $cdn_url = $this->build_public_url($relative);
            if ($cdn_url) {
                $sources[$width]['url'] = $cdn_url;
            }
        }

        return $sources;
    }

    private function test_connection($provider) {
        $this->verify_ajax_request();
        $settings = $this->get_settings_from_request($provider, false);

        if (is_wp_error($settings)) {
            wp_send_json_error(['message' => $settings->get_error_message()], 400);
        }

        $client = $this->get_s3_client($provider, $settings);
        if (is_wp_error($client)) {
            $fallback = $this->head_bucket_with_http($provider, $settings);
            if (is_wp_error($fallback)) {
                wp_send_json_error(['message' => $fallback->get_error_message()], 400);
            }
            wp_send_json_success(['message' => 'Connection successful. Bucket is reachable.']);
        }

        try {
            $client->headBucket(['Bucket' => $settings['bucket']]);
            wp_send_json_success(['message' => 'Connection successful. Bucket is reachable.']);
        } catch (AwsException $e) {
            wp_send_json_error(['message' => $this->format_aws_error($e)], 400);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()], 400);
        }
    }

    private function save_credentials($provider) {
        $this->verify_ajax_request();
        $settings = $this->get_settings_from_request($provider, false);

        if (is_wp_error($settings)) {
            wp_send_json_error(['message' => $settings->get_error_message()], 400);
        }

        update_option("aioml_{$provider}_access_key", $settings['access_key']);
        update_option("aioml_{$provider}_secret_key", $this->encrypt_value($settings['secret_key']));
        update_option("aioml_{$provider}_bucket", $settings['bucket']);
        update_option("aioml_{$provider}_region", $settings['region']);
        update_option("aioml_{$provider}_public_url", $settings['public_url']);
        update_option("aioml_{$provider}_enabled", true);

        if ($provider === 'r2') {
            update_option('aioml_r2_account_id', $settings['account_id']);
        }

        update_option('aioml_cdn_provider', $provider);

        $label = $provider === 'r2' ? 'Cloudflare R2' : 'AWS S3';
        wp_send_json_success(['message' => $label . ' provider saved.', 'provider' => $provider]);
    }

    private function verify_ajax_request() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to manage CDN settings.'], 403);
        }

        if (!check_ajax_referer('aioml_cdn_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.'], 403);
        }
    }

    private function get_settings_from_request($provider, $require_public_url) {
        if (!in_array($provider, $this->providers, true)) {
            return new \WP_Error('aioml_invalid_provider', 'Invalid CDN provider.');
        }

        $access_key = isset($_POST['access_key']) ? sanitize_text_field(wp_unslash($_POST['access_key'])) : '';
        $secret_key = isset($_POST['secret_key']) ? sanitize_text_field(wp_unslash($_POST['secret_key'])) : '';
        $bucket = isset($_POST['bucket']) ? sanitize_text_field(wp_unslash($_POST['bucket'])) : '';
        $region = isset($_POST['region']) ? sanitize_text_field(wp_unslash($_POST['region'])) : ($provider === 'r2' ? 'auto' : '');
        $account_id = isset($_POST['account_id']) ? sanitize_text_field(wp_unslash($_POST['account_id'])) : '';
        $public_url = isset($_POST['public_url']) ? esc_url_raw(trim(wp_unslash($_POST['public_url']))) : '';

        if (!$secret_key) {
            $secret_key = $this->get_secret_key($provider);
        }

        if (!$access_key || !$secret_key || !$bucket) {
            return new \WP_Error('aioml_missing_cdn_fields', 'Access Key ID, Secret Access Key, and Bucket Name are required.');
        }

        if ($provider === 'r2' && !$account_id) {
            return new \WP_Error('aioml_missing_r2_account_id', 'Cloudflare Account ID is required.');
        }

        if ($provider === 's3' && !$region) {
            return new \WP_Error('aioml_missing_s3_region', 'AWS S3 Region is required.');
        }

        if ($require_public_url && !$public_url) {
            return new \WP_Error('aioml_missing_public_url', 'Public URL / Custom Domain is required.');
        }

        return [
            'provider' => $provider,
            'access_key' => $access_key,
            'secret_key' => $secret_key,
            'bucket' => $bucket,
            'region' => $provider === 'r2' ? 'auto' : $region,
            'account_id' => $account_id,
            'public_url' => $public_url,
        ];
    }

    private function get_saved_settings($provider = null) {
        $provider = $provider ?: $this->get_active_provider();

        return [
            'provider' => $provider,
            'access_key' => get_option("aioml_{$provider}_access_key", ''),
            'secret_key' => $this->get_secret_key($provider),
            'bucket' => get_option("aioml_{$provider}_bucket", ''),
            'region' => get_option("aioml_{$provider}_region", $provider === 'r2' ? 'auto' : ''),
            'account_id' => get_option('aioml_r2_account_id', ''),
            'public_url' => get_option("aioml_{$provider}_public_url", ''),
        ];
    }

    private function get_s3_client($provider, $settings = null) {
        if (!class_exists(S3Client::class)) {
            return new \WP_Error('aioml_aws_sdk_missing', 'AWS SDK for PHP is not installed. Falling back to WordPress HTTP signing.');
        }

        $settings = $settings ?: $this->get_saved_settings($provider);
        $args = [
            'version' => 'latest',
            'region' => $settings['region'],
            'credentials' => [
                'key' => $settings['access_key'],
                'secret' => $settings['secret_key'],
            ],
        ];

        if ($provider === 'r2') {
            $args['endpoint'] = 'https://' . $settings['account_id'] . '.r2.cloudflarestorage.com';
            $args['use_path_style_endpoint'] = true;
        }

        return new S3Client($args);
    }

    private function upload_file_to_cdn($file_path) {
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }

        $provider = $this->get_active_provider();
        $settings = $this->get_saved_settings($provider);
        $object_key = $this->get_object_key_from_path($file_path);
        if (!$provider || !$object_key) {
            return false;
        }

        $client = $this->get_s3_client($provider, $settings);
        if (is_wp_error($client)) {
            return $this->put_object_with_http($provider, $settings, $file_path, $object_key);
        }

        try {
            $client->putObject([
                'Bucket' => $settings['bucket'],
                'Key' => $object_key,
                'SourceFile' => $file_path,
                'ContentType' => wp_check_filetype($file_path)['type'] ?: 'application/octet-stream',
            ]);
            return $object_key;
        } catch (\Exception $e) {
            error_log('AIOML CDN upload failed: ' . $e->getMessage());
            return false;
        }
    }

    private function head_bucket_with_http($provider, $settings) {
        $path = $provider === 'r2' ? '/' . rawurlencode($settings['bucket']) : '/';
        $response = $this->signed_request($provider, 'HEAD', $settings, $path);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            return true;
        }

        return new \WP_Error('aioml_head_bucket_failed', $this->get_http_error_message($provider, $response, 'Unable to reach bucket.'));
    }

    private function put_object_with_http($provider, $settings, $file_path, $object_key) {
        $body = file_get_contents($file_path);
        if ($body === false) {
            return false;
        }

        $path = $provider === 'r2'
            ? '/' . rawurlencode($settings['bucket']) . '/' . $this->encode_object_key($object_key)
            : '/' . $this->encode_object_key($object_key);

        $response = $this->signed_request($provider, 'PUT', $settings, $path, $body, wp_check_filetype($file_path)['type'] ?: 'application/octet-stream');

        if (is_wp_error($response)) {
            error_log('AIOML CDN upload failed: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            return $object_key;
        }

        error_log('AIOML CDN upload failed: ' . $this->get_http_error_message($provider, $response, 'Upload failed.'));
        return false;
    }

    private function signed_request($provider, $method, $settings, $path, $body = '', $content_type = '') {
        $host = $provider === 'r2'
            ? $settings['account_id'] . '.r2.cloudflarestorage.com'
            : $settings['bucket'] . '.s3.' . $settings['region'] . '.amazonaws.com';

        $region = $provider === 'r2' ? 'auto' : $settings['region'];
        $endpoint = 'https://' . $host . $path;
        $amz_date = gmdate('Ymd\THis\Z');
        $date_stamp = gmdate('Ymd');
        $payload_hash = hash('sha256', $body);

        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => $payload_hash,
            'x-amz-date' => $amz_date,
        ];

        if ($content_type) {
            $headers['content-type'] = $content_type;
        }

        ksort($headers);
        $canonical_headers = '';
        foreach ($headers as $name => $value) {
            $canonical_headers .= strtolower($name) . ':' . trim($value) . "\n";
        }

        $signed_headers = implode(';', array_keys($headers));
        $canonical_request = $method . "\n" . $path . "\n\n" . $canonical_headers . "\n" . $signed_headers . "\n" . $payload_hash;
        $credential_scope = $date_stamp . '/' . $region . '/s3/aws4_request';
        $string_to_sign = "AWS4-HMAC-SHA256\n{$amz_date}\n{$credential_scope}\n" . hash('sha256', $canonical_request);
        $signature = hash_hmac('sha256', $string_to_sign, $this->get_signature_key($settings['secret_key'], $date_stamp, $region, 's3'));

        $headers['authorization'] = 'AWS4-HMAC-SHA256 Credential=' . $settings['access_key'] . '/' . $credential_scope . ', SignedHeaders=' . $signed_headers . ', Signature=' . $signature;

        return wp_remote_request($endpoint, [
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
            'timeout' => 45,
        ]);
    }

    private function get_signature_key($key, $date_stamp, $region_name, $service_name) {
        $k_date = hash_hmac('sha256', $date_stamp, 'AWS4' . $key, true);
        $k_region = hash_hmac('sha256', $region_name, $k_date, true);
        $k_service = hash_hmac('sha256', $service_name, $k_region, true);
        return hash_hmac('sha256', 'aws4_request', $k_service, true);
    }

    private function save_attachment_cdn_meta($attachment_id, $object_key) {
        $provider = $this->get_active_provider();

        update_post_meta($attachment_id, '_aioml_cdn_provider', $provider);
        update_post_meta($attachment_id, '_aioml_cdn_uploaded', 'yes');
        update_post_meta($attachment_id, '_aioml_cdn_key', $object_key);
        update_post_meta($attachment_id, '_aioml_cdn_synced_at', current_time('mysql'));

        if ($provider === 'r2') {
            update_post_meta($attachment_id, '_aioml_r2_uploaded', 'yes');
            update_post_meta($attachment_id, '_aioml_r2_key', $object_key);
            update_post_meta($attachment_id, '_aioml_r2_synced_at', current_time('mysql'));
        }
    }

    private function get_object_key_from_path($file_path) {
        if (!$file_path) {
            return '';
        }

        $uploads = wp_get_upload_dir();
        $base_dir = wp_normalize_path(trailingslashit($uploads['basedir']));
        $normalized = wp_normalize_path($file_path);

        if (strpos($normalized, $base_dir) === 0) {
            return ltrim(substr($normalized, strlen($base_dir)), '/');
        }

        return basename($file_path);
    }

    private function build_public_url($object_key) {
        $provider = $this->get_active_provider();
        $public_url = untrailingslashit(get_option("aioml_{$provider}_public_url", ''));

        if (!$public_url && $provider === 's3') {
            $bucket = get_option('aioml_s3_bucket', '');
            $region = get_option('aioml_s3_region', '');
            $public_url = $bucket && $region ? 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com' : '';
        }

        if (!$public_url) {
            return '';
        }

        return trailingslashit($public_url) . ltrim($object_key, '/');
    }

    private function is_cdn_active() {
        $provider = $this->get_active_provider();
        if (!$provider) {
            return false;
        }

        $is_active = (bool) get_option("aioml_{$provider}_enabled", false)
            && get_option("aioml_{$provider}_access_key", '')
            && get_option("aioml_{$provider}_secret_key", '')
            && get_option("aioml_{$provider}_bucket", '');

        if (!$is_active) {
            return false;
        }

        if ($provider === 'r2') {
            return (bool) get_option('aioml_r2_account_id', '');
        }

        return (bool) get_option('aioml_s3_region', '');
    }

    private function get_active_provider() {
        $provider = get_option('aioml_cdn_provider', '');
        return in_array($provider, $this->providers, true) ? $provider : '';
    }

    private function encode_object_key($object_key) {
        return implode('/', array_map('rawurlencode', explode('/', ltrim($object_key, '/'))));
    }

    private function get_http_error_message($provider, $response, $fallback) {
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($body && preg_match('/<Message>(.*?)<\/Message>/', $body, $matches)) {
            return strtoupper($provider) . ' error ' . $code . ': ' . html_entity_decode($matches[1]);
        }

        return $code ? $fallback . ' HTTP ' . $code . '.' : $fallback;
    }

    private function encrypt_value($value) {
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($value);
        }

        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        try {
            $iv = random_bytes($iv_length);
        } catch (\Exception $e) {
            $iv = substr(hash('sha256', uniqid((string) wp_rand(), true)), 0, $iv_length);
        }
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $this->get_encryption_key(), 0, $iv);

        return base64_encode($iv . '::' . $encrypted);
    }

    private function decrypt_value($value) {
        if (!$value) {
            return '';
        }

        if (!function_exists('openssl_decrypt')) {
            return base64_decode($value);
        }

        $decoded = base64_decode($value, true);
        if ($decoded !== false && strpos($decoded, '::') !== false) {
            list($iv, $encrypted) = explode('::', $decoded, 2);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->get_encryption_key(), 0, $iv);
            return $decrypted === false ? '' : $decrypted;
        }

        $iv = substr(hash('sha256', $this->get_encryption_key()), 0, 16);
        $decrypted = openssl_decrypt($value, 'AES-256-CBC', $this->get_encryption_key(), 0, $iv);
        return $decrypted === false ? '' : $decrypted;
    }

    private function get_secret_key($provider) {
        return $this->decrypt_value(get_option("aioml_{$provider}_secret_key", ''));
    }

    private function get_encryption_key() {
        $key = (defined('AUTH_KEY') ? AUTH_KEY : '') . (defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : '');
        return $key ?: wp_salt('auth');
    }

    private function format_aws_error(AwsException $e) {
        $aws_message = $e->getAwsErrorMessage();
        return $aws_message ? $aws_message : $e->getMessage();
    }
}
