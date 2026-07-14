<?php
namespace Smackcoders\Aioml;
if( !defined('AIOML_DIR') ){
    define('AIOML_DIR',__DIR__);
}
require_once plugin_dir_path(__DIR__).'admin-dash/main_menu.php';
require_once plugin_dir_path(__DIR__).'admin-dash/aioml-settings.php';
require_once plugin_dir_path(__FILE__).'../modules/register-category.php';
require_once plugin_dir_path(__FILE__).'../modules/add-new-folder.php';
require_once plugin_dir_path(__FILE__).'../modules/fetch_mediafiles.php';
require_once plugin_dir_path(__FILE__).'../modules/aioml-cdn.php';
class AdminMenu{

    private static $instance = null;
    private $register_custom_category = null;
    private $aioml_folder_instance = null;
    private $aioml_cdn_instance = null;
    private $aioml_settings_hook = '';

    public static function getInstance(){
        if(self::$instance === null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct(){
        $this->register_custom_category = Register_Category::GetInstance();
        $this->aioml_folder_instance = FolderAction::getInstance();
        $this->aioml_cdn_instance = Aioml_Cdn::getInstance();
        add_action('admin_menu', [$this, 'register_aioml_menus']);
        add_action('admin_menu', [$this, 'remove_attachment_taxonomies_submenu'], 999);
        add_action('admin_enqueue_scripts',[$this,'enqueue_admin_assets']);
        add_action('admin_enqueue_scripts',[$this, 'enqueue_script']);
        add_action('admin_enqueue_scripts',[$this, 'Aioml_for_edit_page_media']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_aioml_settings_assets']);
    }

    public function register_aioml_menus() {
        $this->aioml_settings_hook = add_submenu_page(
            'upload.php',
            'AIOML Settings',
            'AIOML Settings',
            'manage_options',
            'aioml-settings',
            [$this, 'render_aioml_settings_page']
        );
    }

    public function remove_attachment_taxonomies_submenu() {
        remove_submenu_page('upload.php', 'edit-tags.php?taxonomy=attachment_category&post_type=attachment');
        remove_submenu_page('upload.php', 'edit-tags.php?taxonomy=attachment_category&amp;post_type=attachment');
        remove_submenu_page('upload.php', 'edit-tags.php?taxonomy=attachment_category');
        remove_submenu_page('upload.php', 'edit-tags.php?taxonomy=attachment_tag&post_type=attachment');
        remove_submenu_page('upload.php', 'edit-tags.php?taxonomy=attachment_tag&amp;post_type=attachment');
        remove_submenu_page('upload.php', 'edit-tags.php?taxonomy=attachment_tag');
    }

    public function render_aioml_settings_page() {
        aioml_render_settings_page();
    }

    public function enqueue_aioml_settings_assets($hook_suffix) {
        if ($hook_suffix !== $this->aioml_settings_hook) {
            return;
        }

        $script_path = plugin_dir_path(__FILE__) . '../inc/assets/js/aioml-cdn.js';

        wp_enqueue_script(
            'aioml-cdn-settings',
            plugin_dir_url(__FILE__) . '../inc/assets/js/aioml-cdn.js',
            ['jquery'],
            filemtime($script_path),
            true
        );

        wp_localize_script(
            'aioml-cdn-settings',
            'aiomlCdnData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aioml_cdn_nonce'),
            ]
        );
    }

    public function enqueue_admin_assets($hook_suffix) {

        if ($hook_suffix !== 'upload.php') {
            return; 
        }

        $custom_script_url  = plugin_dir_url(__FILE__) . '../inc/assets/js/admin-script.js';
        $drag_script_url  = plugin_dir_url(__FILE__) . '../inc/assets/js/drag-script.js';
        $custom_script_path = plugin_dir_path(__FILE__) . '../inc/assets/js/admin-script.js';
        $drag_script_path = plugin_dir_path(__FILE__) . '../inc/assets/js/drag-script.js';

        wp_enqueue_script(
            'custom-script',
            $custom_script_url,
            array(),
            filemtime($custom_script_path),
            true
        );
        wp_enqueue_script(
            'drag-script',
            $drag_script_url,
            ['jquery'],
            filemtime($drag_script_path),
            true
        );

        wp_enqueue_style(
            'aioml-style',
            plugin_dir_url(__FILE__) . '../inc/assets/css/style.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . '../inc/assets/css/style.css')
        );
        wp_localize_script( 
            'drag-script', 
            'dnd_data', array(
                'ajax_url'     => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('custom_media_folder_nonce'),
            )
            );
}

public function enqueue_script($hook) {
    if ($hook !== 'upload.php') return;

    $dir = plugin_dir_url(__FILE__) . '../my-app/build/';
    $manifest = json_decode(file_get_contents(plugin_dir_path(__FILE__) . '../my-app/build/asset-manifest.json'), true);

    // JS
    wp_enqueue_script(
        'mylib-react-js',
        $dir . ltrim($manifest['files']['main.js'], '/'),
        ['jquery'], 
        null,
        true
    );

    // CSS
    if (isset($manifest['files']['main.css'])) {
        wp_enqueue_style(
            'mylib-react-css',
            $dir . ltrim($manifest['files']['main.css'], '/')
        );
    }

    //Load the TailWind
     wp_localize_script(
        'mylib-react-js', 
        'react_data', array(
            'ajax_url'     => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('custom_media_folder_nonce'),
            'nonce_safedel' => wp_create_nonce('wp_rest'),
            'rest_url'      => esc_url_raw( rest_url('wp/v2/media') ),
            'remove_from_folder_url' => esc_url_raw( rest_url('aioml/v1/media/remove-from-folder') ),
        )
    );

}
    public function Aioml_for_edit_page_media($hook){
        
        if ( !in_array($hook, ['post.php', 'post-new.php', 'customize.php', 'options-general.php']) ) {
            return;
        }

        $edit_page_script_url  = plugin_dir_url(__FILE__) . '../inc/assets/js/post-php-script.js';
        $edit_page_script_path = plugin_dir_path(__FILE__) . '../inc/assets/js/post-php-script.js';
        $edit_page_style_path = plugin_dir_path(__FILE__) . '../inc/assets/css/post-php-styles.css';
        $edit_page_style_url  = plugin_dir_url(__FILE__) . '../inc/assets/css/post-php-styles.css';

        wp_enqueue_script(
            'post-php-script',
            $edit_page_script_url,
            array(),
            filemtime($edit_page_script_path),
            true
        );
        wp_enqueue_style( 
            'post-php-styles',
            $edit_page_style_url,
            [],
            filemtime($edit_page_style_path), 
        );
        
        wp_localize_script( 
            'post-php-script', 
            'post_php_data', array(
                'ajax_url'     => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('post_php_nonce'),
            )
        );

        // Enqueue React App for Modal
        $dir = plugin_dir_url(__FILE__) . '../my-app/build/';
        $manifest_path = plugin_dir_path(__FILE__) . '../my-app/build/asset-manifest.json';

        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);

            // JS
            if (isset($manifest['files']['main.js'])) {
                wp_enqueue_script(
                    'mylib-react-js',
                    $dir . ltrim($manifest['files']['main.js'], '/'),
                    ['jquery'], 
                    null,
                    true
                );
            }

            // CSS
            if (isset($manifest['files']['main.css'])) {
                wp_enqueue_style(
                    'mylib-react-css',
                    $dir . ltrim($manifest['files']['main.css'], '/')
                );
            }

            // Load the TailWind / React Data
            wp_localize_script(
                'mylib-react-js', 
                'react_data', array(
                    'ajax_url'     => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('custom_media_folder_nonce'),
                    'nonce_safedel' => wp_create_nonce('wp_rest'),
                    'rest_url'      => esc_url_raw( rest_url('wp/v2/media') ),
                    'remove_from_folder_url' => esc_url_raw( rest_url('aioml/v1/media/remove-from-folder') ),
                )
            );
        }
    }
}
