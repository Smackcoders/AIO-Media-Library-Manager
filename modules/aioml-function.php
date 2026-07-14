<?php
namespace Smackcoders\Aioml;

class AiomlSmack_Folder_Functions{
    private static $instance = null;

    private function __construct(){
        add_action('wp_ajax_AiomlSmack_save_folder_to_database', [ $this,'AiomlSmack_save_folder_to_database_callback']);// create a new category
        add_action('wp_ajax_nopriv_AiomlSmack_save_folder_to_database', [ $this,'AiomlSmack_save_folder_to_database_callback']);
        
        add_action('wp_ajax_AiomlSmack_delete_folder_from_database', [ $this,'AiomlSmack_delete_folder_from_database_callback']); // delete the selected category
        add_action('wp_ajax_nopriv_AiomlSmack_delete_folder_from_database', [ $this,'AiomlSmack_delete_folder_from_database_callback']);
        
        add_action('wp_ajax_AiomlSmackupdate_folder_in_database', [$this,'AiomlSmackupdate_folder_in_database_callback']);// rename the selected category
        add_action('wp_ajax_nopriv_AiomlSmackupdate_folder_in_database', [$this,'AiomlSmackupdate_folder_in_database_callback']);
        
        add_action('wp_ajax_AiomlSmackfolder_DragDrop_database', [$this,'AiomlSmackfolder_DragDrop_database_callback']); // drag and drop to shift the media from one category to another category
        add_action('wp_ajax_nopriv_AiomlSmackfolder_DragDrop_database', [$this,'AiomlSmackfolder_DragDrop_database_callback']);
        
        add_action('wp_ajax_AiomlSmack_move_attachments_to_category', [$this,'AiomlSmack_move_attachments_to_category_callback']); //act as same as drag and drop
        
        add_action('wp_ajax_aioml_get_sorted_folders', [$this,'AiomlSmack_get_sorted_folders']); // sort the folder as per the order provided by the frontend
        add_action('wp_ajax_AiomlSmack_sort_media', [ $this,'AiomlSmack_sort_media_callback']); // sort media files as per the order provided by the frontend

        add_action('wp_ajax_AiomlSmack_copy_folder', [ $this,'AiomlSmack_copy_folder_callback']); // copy the category create as a catergory(copy) format and store under the seleted category
        add_action('wp_ajax_nopriv_AiomlSmack_copy_folder', [ $this,'AiomlSmack_copy_folder_callback']);

        add_action('wp_ajax_AiomlSmack_cut_folder', [ $this,'AiomlSmack_cut_folder_callback']); // set a parent for the selected category(move)
        add_action('wp_ajax_nopriv_AiomlSmack_cut_folder', [ $this,'AiomlSmack_cut_folder_callback']);

        add_action('wp_ajax_AiomlSmack_paste_folder', [ $this,'AiomlSmack_paste_folder_callback']); // work same as the cut
        add_action('wp_ajax_nopriv_AiomlSmack_paste_folder', [ $this,'AiomlSmack_paste_folder_callback']);

        add_action('wp_ajax_get_media_folder',[$this, 'AiomlSmack_get_media_folder'] ); // get the slug of the media_id we give as a input

        add_action('wp_ajax_remove_media_from_folder_taxonomy',[$this, 'AiomlSmack_remove_media_from_folder_taxonomy']); // remove the media from the category
        add_action('wp_ajax_AiomlSmack_replace_media_file', [$this,'AiomlSmack_replace_media_file_callback']); // delete the old files and add new file to the same location and update the file name,added_date and post_name in the wordpress tables
        add_action('wp_ajax_AiomlSmack_get_media_details', [$this,'AiomlSmack_get_media_details_callback']); // returns the media type , url and title
        add_action('wp_ajax_AiomlSmack_fetch_folders_from_database', [$this,'AiomlSmack_fetch_folders_from_database_callback']); // fetch all categories data from the Database
        
        

        

    }

    public static function getInstance(){
        if(self::$instance === null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function AiomlSmack_save_folder_to_database_callback() {

        if (
            !isset($_POST['security']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')
        ) {
            wp_send_json_error('Nonce verification failed.');
        }

        if (isset($_POST['name'], $_POST['parentId'])) {
    
            $aioml_folderName = sanitize_text_field(wp_unslash($_POST['name']));
            $aioml_parentId   = intval(wp_unslash($_POST['parentId']));

        
            if (!preg_match('/^[a-zA-Z0-9 _-]+$/', $aioml_folderName)) {
                wp_send_json_error('Folder name contains invalid characters.');
            }

            $aioml_folderSlug = sanitize_title($aioml_folderName);
            $aioml_folderSlug = wp_unique_term_slug(
                $aioml_folderSlug,
                (object) ['taxonomy' => 'attachment_category', 'parent' => $aioml_parentId]
            );

            $aioml_term = wp_cache_get('folder_term_' . $aioml_folderSlug);
            if ($aioml_term === false) {
                $aioml_term = wp_insert_term($aioml_folderName, 'attachment_category', array('slug' => $aioml_folderSlug));

                if (is_wp_error($aioml_term)) {
                    wp_send_json_error('Error saving folder to database: ' . esc_html($aioml_term->get_error_message()));
                }

                wp_cache_set('folder_term_' . $aioml_folderSlug, $aioml_term);
            }


            $aioml_term_id = (int) $aioml_term['term_id'];
            $aioml_term_obj = get_term( $aioml_term_id, 'attachment_category' );
            if ( $aioml_term_obj && ! is_wp_error( $aioml_term_obj ) ) {
                $aioml_folderSlug = $aioml_term_obj->slug;
            }

            global $wpdb;
            $aioml_table_name = $wpdb->prefix . 'aio_media_library';

            $aioml_data = array(
                'term_id' => $aioml_term_id,
                'name'    => $aioml_folderName,
                'slug'    => $aioml_folderSlug,
                'parent'  => $aioml_parentId,
            );

            $aioml_format = array('%d', '%s', '%s', '%d');

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $aioml_result = $wpdb->insert($aioml_table_name, $aioml_data, $aioml_format);

            if ($aioml_result === false) {
                wp_send_json_error('Error saving folder to database.');
            } else {
                wp_send_json_success('Folder saved to database: ' . esc_html($aioml_folderName));
            }
        } else {
            
            wp_send_json_error('Invalid data provided.');
        }

        wp_die();
    }

    public function AiomlSmack_sort_media_callback() {
        if (!check_ajax_referer('aioml_smack_folder_nonce', 'security', false)) {
            wp_send_json_error('Invalid nonce');
            wp_die();
        }

        $aioml_title_order = isset($_POST['title_order']) ? sanitize_text_field(wp_unslash($_POST['title_order'])) : 'none';
        $aioml_date_order = isset($_POST['date_order']) ? sanitize_text_field(wp_unslash($_POST['date_order'])) : 'none';
        $aioml_file_type = isset($_POST['file_type']) ? sanitize_text_field(wp_unslash($_POST['file_type'])) : 'all';
        $aioml_folder_slug = isset($_POST['folder_slug']) ? sanitize_text_field(wp_unslash($_POST['folder_slug'])) : 'all';
        $aioml_paged = isset($_POST['page']) ? absint($_POST['page']) : 1;

        $aioml_args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 30,
            'paged'          => $aioml_paged,
            'fields'         => 'ids',
        );

        if ($aioml_folder_slug !== 'all') {
            $aioml_term = get_term_by('slug', $aioml_folder_slug, 'attachment_category');
            if ($aioml_term && !is_wp_error($aioml_term)) {
                $aioml_media_ids = get_objects_in_term($aioml_term->term_id, 'attachment_category');
                $aioml_args['post__in'] = !empty($aioml_media_ids) ? $aioml_media_ids : array(0); 
            } else {
                wp_send_json_error('Invalid folder selected.');
            }
        }

    
        if ($aioml_file_type !== 'all') {
            $aioml_mime_types = array(
                'image'    => 'image',
                'video'    => 'video',
                'document' => 'application',
            );
            if (isset($aioml_mime_types[$aioml_file_type])) {
                $aioml_args['post_mime_type'] = $aioml_mime_types[$aioml_file_type];
            }
        }

    
        if ($aioml_title_order !== 'none') {
            $aioml_args['orderby'] = 'title';
            $aioml_args['order'] = ($aioml_title_order === 'asc') ? 'ASC' : 'DESC';
        } elseif ($aioml_date_order !== 'none') {
            $aioml_args['orderby'] = 'date';
            $aioml_args['order'] = ($aioml_date_order === 'new_old') ? 'DESC' : 'ASC';
        }

    
        $aioml_query = new WP_Query($aioml_args);
        $aioml_media_items = array();

        if ($aioml_query->have_posts()) {
            foreach ($aioml_query->posts as $aioml_post_id) {
                $aioml_media_items[] = array(
                    'id'        => $aioml_post_id,
                    'title'     => get_the_title($aioml_post_id) ?: 'Untitled',
                    'url'       => wp_get_attachment_url($aioml_post_id),
                    'mime_type' => get_post_mime_type($aioml_post_id),
                    'date'      => get_the_date('c', $aioml_post_id),
                );
            }
        }


        if (!empty($aioml_media_items)) {
            wp_send_json_success($aioml_media_items);
        } else {
            wp_send_json_error('No media found.');
        }

        wp_die();
    }

    public function AiomlSmack_copy_folder_callback() {
        check_ajax_referer('folder_nonce', 'security');
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Unauthorized user.');
        }

        global $wpdb;
        $aioml_table_name = esc_sql($wpdb->prefix . 'aio_media_library');

        $aioml_source_folder_id = isset($_POST['source_folder_id']) ? intval($_POST['source_folder_id']) : 0;
        $aioml_target_parent_id = isset($_POST['target_parent_id']) ? intval($_POST['target_parent_id']) : 0;

        if (!$aioml_source_folder_id) {
            wp_send_json_error('Invalid source folder ID.');
        }

        if ($aioml_target_parent_id === 0 && !isset($_POST['allow_root'])) {
            wp_send_json_error('Target parent ID is required for copying.');
        }

    


    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared 
    $aioml_sql = "SELECT * FROM {$aioml_table_name} WHERE term_id = %d";

    $aioml_source_folder = $wpdb->get_row(
        // phpcs:ignore  WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->prepare($aioml_sql, $aioml_source_folder_id)
    );

        if (!$aioml_source_folder) {
            wp_send_json_error('Source folder not found.');
        }

        if ($aioml_target_parent_id !== 0) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $aioml_parent_folder = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$aioml_table_name} WHERE term_id = %d", $aioml_target_parent_id)
            );
            if (!$aioml_parent_folder) {
                wp_send_json_error('Target parent folder not found.');
            }
        }


        $aioml_base_name = preg_replace('/\s*\(Copy(?:\s*\d*)?\)$/i', '', $aioml_source_folder->name);
        $aioml_existing_terms = get_terms([
            'taxonomy'   => 'attachment_category',
            'hide_empty' => false,
            'name__like' => $aioml_base_name . ' (Copy)',
        ]);

        $aioml_max_suffix = -1;
        foreach ($aioml_existing_terms as $aioml_term) {
            if ($aioml_term->name === $aioml_base_name . ' (Copy)') {
                $aioml_max_suffix = max($aioml_max_suffix, 0);
            } elseif (preg_match('/' . preg_quote($aioml_base_name, '/') . '\s*\(Copy (\d+)\)/i', $aioml_term->name, $aioml_matches)) {
                $aioml_max_suffix = max($aioml_max_suffix, intval($aioml_matches[1]));
            }
        }

        $aioml_new_folder_name = ($aioml_max_suffix === -1)
            ? $aioml_base_name . ' (Copy)'
            : $aioml_base_name . ' (Copy ' . ($aioml_max_suffix + 1) . ')';

        $aioml_new_slug = wp_unique_term_slug(sanitize_title($aioml_new_folder_name), (object)['taxonomy' => 'attachment_category']);

    
        $aioml_term = wp_insert_term($aioml_new_folder_name, 'attachment_category', [
            'slug'   => $aioml_new_slug,
            'parent' => $aioml_target_parent_id,
        ]);

        if (is_wp_error($aioml_term)) {
            wp_send_json_error('Error creating copied folder: ' . $aioml_term->get_error_message());
        }

        $aioml_new_term_id = $aioml_term['term_id'];


        $aioml_insert = $wpdb->insert(
            $aioml_table_name,
            [
                'term_id'    => $aioml_new_term_id,
                'name'       => $aioml_new_folder_name,
                'slug'       => $aioml_new_slug,
                'parent'     => $aioml_target_parent_id,
                'created_by' => get_current_user_id(),
            ],
            ['%d', '%s', '%s', '%d', '%d']
        );

        if ($aioml_insert === false) {
            wp_delete_term($aioml_new_term_id, 'attachment_category');
            wp_send_json_error('Error saving copied folder to database.');
        }

    
        $aioml_media_ids = get_objects_in_term($aioml_source_folder_id, 'attachment_category');
        if (!empty($aioml_media_ids)) {
            foreach ($aioml_media_ids as $aioml_attachment_id) {
                wp_set_object_terms($aioml_attachment_id, [$aioml_new_term_id], 'attachment_category', true);
            }
        }

        wp_send_json_success([
            'new_slug' => $aioml_new_slug,
            'term_id'  => $aioml_new_term_id,
        ]);
    }

    public function AiomlSmack_cut_folder_callback() {
        check_ajax_referer('folder_nonce', 'security');

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Unauthorized user.');
        }

        $aioml_source_folder_id = isset($_POST['source_folder_id']) ? intval($_POST['source_folder_id']) : 0;
        $aioml_target_parent_id = isset($_POST['target_parent_id']) ? intval($_POST['target_parent_id']) : 0;

        if (!$aioml_source_folder_id) {
            wp_send_json_error('Invalid source folder ID.');
        }

        global $wpdb;
        $aioml_table_name = $wpdb->prefix . 'aio_media_library';

        // Validate source folder
        $aioml_source_folder = wp_cache_get('folder_' . $aioml_source_folder_id, 'aioml_folders');
        if (false === $aioml_source_folder) {
            $aioml_source_folder = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$aioml_table_name} WHERE term_id = %d", $aioml_source_folder_id));
            if ($aioml_source_folder) {
                wp_cache_set('folder_' . $aioml_source_folder_id, $aioml_source_folder, 'aioml_folders', HOUR_IN_SECONDS);
            }
        }
        if (!$aioml_source_folder) {
            wp_send_json_error('Source folder not found.');
        }

        // Validate target parent (if not root)
        if ($aioml_target_parent_id !== 0) {
            $aioml_parent_folder = wp_cache_get('folder_' . $aioml_target_parent_id, 'aioml_folders');
            if (false === $aioml_parent_folder) {
                $aioml_parent_folder = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$aioml_table_name} WHERE term_id = %d", $aioml_target_parent_id));
                if ($aioml_parent_folder) {
                    wp_cache_set('folder_' . $aioml_target_parent_id, $aioml_parent_folder, 'aioml_folders', HOUR_IN_SECONDS);
                }
            }
            if (!$aioml_parent_folder) {
                wp_send_json_error('Target parent folder not found.');
            }
        }

        // Update folder parent in wp_aio_media_library
        $aioml_cache_key = 'folder_data_' . $aioml_source_folder_id;
        wp_cache_delete($aioml_cache_key, 'folder_data');
        $aioml_result = $wpdb->update(
            $aioml_table_name,
            ['parent' => $aioml_target_parent_id],
            ['term_id' => $aioml_source_folder_id],
            ['%d'],
            ['%d']
        );

        // Update term parent in wp_terms
        $aioml_term_update = wp_update_term($aioml_source_folder_id, 'attachment_category', [
            'parent' => $aioml_target_parent_id,
        ]);

        if ($aioml_result === false || is_wp_error($aioml_term_update)) {
            wp_send_json_error('Error moving folder: ' . ($wpdb->last_error ?: $aioml_term_update->get_error_message()));
        }

        wp_send_json_success(['term_id' => $aioml_source_folder_id]);
    }

    public function AiomlSmack_paste_folder_callback() {
        if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Unauthorized user.');
        }

        $aioml_source_folder_id = isset($_POST['source_folder_id']) ? intval(wp_unslash($_POST['source_folder_id'])) : 0;
        $aioml_target_parent_id = isset($_POST['target_parent_id']) ? intval(wp_unslash($_POST['target_parent_id'])) : 0;

        if (!$aioml_source_folder_id) {
            wp_send_json_error('Invalid source folder ID.');
        }

        global $wpdb;
        $aioml_table_name = $wpdb->prefix . 'aio_media_library';

        // Validate source folder
        $aioml_source_folder = wp_cache_get('folder_' . $aioml_source_folder_id, 'aioml_folders');
        if (false === $aioml_source_folder) {
            $aioml_source_folder = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$aioml_table_name} WHERE term_id = %d", $aioml_source_folder_id));
            if ($aioml_source_folder) {
                wp_cache_set('folder_' . $aioml_source_folder_id, $aioml_source_folder, 'aioml_folders', HOUR_IN_SECONDS);
            }
        }
        if (!$aioml_source_folder) {
            wp_send_json_error('Source folder not found.');
        }

        // Validate target parent (if not root)
        if ($aioml_target_parent_id !== 0) {
            $aioml_parent_folder = wp_cache_get('folder_' . $aioml_target_parent_id, 'aioml_folders');
            if (false === $aioml_parent_folder) {
                $aioml_parent_folder = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$aioml_table_name} WHERE term_id = %d", $aioml_target_parent_id));
                if ($aioml_parent_folder) {
                    wp_cache_set('folder_' . $aioml_target_parent_id, $aioml_parent_folder, 'aioml_folders', HOUR_IN_SECONDS);
                }
            }
            if (!$aioml_parent_folder) {
                wp_send_json_error('Target parent folder not found.');
            }
        }

        // Prevent circular reference (source folder cannot be its own parent)
        if ($aioml_source_folder_id === $aioml_target_parent_id) {
            wp_send_json_error('Cannot move folder to itself.');
        }

        // Update folder parent in wp_aio_media_library
        $aioml_cache_key = 'folder_data_' . $aioml_source_folder_id;
        wp_cache_delete($aioml_cache_key, 'folder_data');
        $aioml_result = $wpdb->update(
            $aioml_table_name,
            ['parent' => $aioml_target_parent_id],
            ['term_id' => $aioml_source_folder_id],
            ['%d'],
            ['%d']
        );

        // Update term parent in wp_terms
        $aioml_term_update = wp_update_term($aioml_source_folder_id, 'attachment_category', [
            'parent' => $aioml_target_parent_id,
        ]);

        if ($aioml_result === false || is_wp_error($aioml_term_update)) {
            wp_send_json_error('Error pasting folder: ' . ($wpdb->last_error ?: $aioml_term_update->get_error_message()));
        }

        wp_send_json_success(['term_id' => $aioml_source_folder_id]);
    }

    public function AiomlSmack_get_media_folder() {
        if (
            !isset($_POST['security']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')
        ) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Unauthorized');
        }

        $aioml_media_id = isset($_POST['media_id']) ? intval(wp_unslash($_POST['media_id'])) : 0;
        if (!$aioml_media_id) {
            wp_send_json_error('Invalid media ID');
        }

        $aioml_terms = wp_get_post_terms($aioml_media_id, 'attachment_category');
        if (empty($aioml_terms) || is_wp_error($aioml_terms)) {
            wp_send_json_error('No folder assigned');
        }

        wp_send_json_success($aioml_terms[0]->slug);
    }

    public function AiomlSmack_remove_media_from_folder_taxonomy() {
        if (
            !isset($_POST['security']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')
        ) {
            wp_send_json_error('Nonce verification failed.');
        }

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Unauthorized');
        }

        $aioml_media_ids = [];

    
        if (isset($_POST['media_ids'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $aioml_raw_media_ids = wp_unslash($_POST['media_ids']);
            $aioml_media_ids = is_array($aioml_raw_media_ids) ? array_map('intval', $aioml_raw_media_ids) : [];
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized 
        $aioml_folder_slug = isset($_POST['folder_slug']) ? sanitize_text_field(wp_unslash($_POST['folder_slug'])) : '';

        if (empty($aioml_media_ids) || empty($aioml_folder_slug)) {
            wp_send_json_error('Missing data.');
        }

        $aioml_term = get_term_by('slug', $aioml_folder_slug, 'attachment_category');
        if (!$aioml_term) {
            wp_send_json_error('Folder not found.');
        }

        $aioml_removed = 0;
        foreach ($aioml_media_ids as $aioml_id) {
            $aioml_current_terms = wp_get_post_terms($aioml_id, 'attachment_category', ['fields' => 'ids']);
            if (in_array((int)$aioml_term->term_id, $aioml_current_terms, true)) {
                wp_remove_object_terms($aioml_id, (int)$aioml_term->term_id, 'attachment_category');
                $aioml_removed++;
            }
        }

        wp_send_json_success("Removed from folder: $aioml_removed items");
    }

    public function AiomlSmack_replace_media_file_callback() {
        if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')) {
            wp_send_json_error('Nonce verification failed.');
        }

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Unauthorized user.');
        }

        $aioml_media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;
        if (!$aioml_media_id) {
            wp_send_json_error('Invalid media ID.');
        }

        $aioml_new_name = isset($_POST['new_name']) ? sanitize_text_field(wp_unslash($_POST['new_name'])) : '';
        $aioml_date_option = isset($_POST['date_option']) ? sanitize_text_field(wp_unslash($_POST['date_option'])) : 'keep';
        $aioml_custom_date = isset($_POST['custom_date']) ? sanitize_text_field(wp_unslash($_POST['custom_date'])) : '';

        if (empty($_FILES['file'])) {
            wp_send_json_error('No file uploaded.');
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $aioml_file = $_FILES['file'];
        $aioml_upload_dir = wp_upload_dir();
        $aioml_old_attachment = get_post($aioml_media_id);
        if (!$aioml_old_attachment || $aioml_old_attachment->post_type !== 'attachment') {
            wp_send_json_error('Invalid attachment.');
        }

        $aioml_old_file_path = get_attached_file($aioml_media_id);
        if (!$aioml_old_file_path) {
            wp_send_json_error('Could not locate the original file.');
        }

        $aioml_wp_filetype = wp_check_filetype($aioml_file['name']);
        if (!$aioml_wp_filetype['ext'] || !$aioml_wp_filetype['type']) {
            wp_send_json_error('Invalid file type.');
        }

        if ($aioml_file['size'] > 10 * 1024 * 1024) {
            wp_send_json_error('File size exceeds 10 MB limit.');
        }

        // Initialize WordPress Filesystem API
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        global $wp_filesystem;
        if (!WP_Filesystem()) {
            wp_send_json_error('Failed to initialize WordPress Filesystem API.');
        }

        // Delete the old file using wp_delete_file()
        if ($wp_filesystem->exists($aioml_old_file_path)) {
            wp_delete_file($aioml_old_file_path);
        }

        $aioml_upload = wp_handle_upload($aioml_file, array('test_form' => false));
        if (isset($aioml_upload['error'])) {
            wp_send_json_error('Error uploading file: ' . $aioml_upload['error']);
        }

        $aioml_new_file_path = $aioml_old_file_path;
        if (!$wp_filesystem->move($aioml_upload['file'], $aioml_new_file_path)) {
            wp_send_json_error('Error moving the uploaded file.');
        }

        $aioml_attachment_data = array(
            'ID' => $aioml_media_id,
            'guid' => str_replace($aioml_upload['file'], $aioml_new_file_path, $aioml_upload['url']),
        );

        if (!empty($aioml_new_name)) {
            $aioml_attachment_data['post_title'] = $aioml_new_name;
            $aioml_attachment_data['post_name'] = sanitize_title($aioml_new_name);
        }

        if ($aioml_date_option === 'today') {
            $aioml_current_date_time = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $aioml_attachment_data['post_date'] = $aioml_current_date_time->format('Y-m-d H:i:s');
            $aioml_attachment_data['post_date_gmt'] = $aioml_current_date_time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } elseif ($aioml_date_option === 'custom' && !empty($aioml_custom_date)) {
            $aioml_custom_date_time = DateTime::createFromFormat('Y-m-d', $aioml_custom_date, new DateTimeZone('Asia/Kolkata'));
            if ($aioml_custom_date_time) {
                $aioml_attachment_data['post_date'] = $aioml_custom_date_time->format('Y-m-d H:i:s');
                $aioml_attachment_data['post_date_gmt'] = $aioml_custom_date_time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }
        }

        wp_update_post($aioml_attachment_data);

        $aioml_metadata = wp_generate_attachment_metadata($aioml_media_id, $aioml_new_file_path);
        wp_update_attachment_metadata($aioml_media_id, $aioml_metadata);

        wp_send_json_success('File replaced successfully.');
    }

    public function AiomlSmack_get_media_details_callback() {
        if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')) {
            wp_send_json_error('Nonce verification failed.');
        }

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Unauthorized user.');
        }

        $aioml_media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0;
        if (!$media_id) {
            wp_send_json_error('Invalid media ID.');
        }

        $aioml_attachment = get_post($aioml_media_id);
        if (!$aioml_attachment || $aioml_attachment->post_type !== 'attachment') {
            wp_send_json_error('Invalid attachment.');
        }

        $aioml_url = wp_get_attachment_url($aioml_media_id);
        $aioml_mime_type = get_post_mime_type($aioml_media_id);
        $aioml_title = get_the_title($aioml_media_id);

        wp_send_json_success([
            'url' => $aioml_url,
            'mime_type' => $aioml_mime_type,
            'title' => $aioml_title
        ]);
    }

    public function AiomlSmack_fetch_folders_from_database_callback() {
        global $wpdb;

        $aioml_cache_key = 'folders_from_database';
        $aioml_folders = wp_cache_get($aioml_cache_key);

        if (false === $aioml_folders) {
            $aioml_terms = get_terms(array(
                'taxonomy' => 'attachment_category',
                'hide_empty' => false,
            ));

            $aioml_folders = array();

            foreach ($aioml_terms as $aioml_term) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $aioml_media_data = $wpdb->get_row(
                    $wpdb->prepare("
                        SELECT 
                            sm.name, 
                            sm.term_id,     
                            sm.slug, 
                            sm.parent, 
                            tt.count AS term_count
                        FROM {$wpdb->prefix}aio_media_library AS sm
                        LEFT JOIN {$wpdb->term_taxonomy} AS tt ON sm.term_id = tt.term_id
                        WHERE sm.term_id = %d AND tt.taxonomy = %s
                        GROUP BY sm.term_id
                    ", $aioml_term->term_id, 'attachment_category'),
                    ARRAY_A
                );

                if ($aioml_media_data) {
                    $aioml_folders[] = array(
                        'name' => esc_html($aioml_media_data['name']),
                        'term_id' => esc_html($aioml_media_data['term_id']),
                        'slug' => esc_html($aioml_media_data['slug']),
                        'parent' => esc_html($aioml_media_data['parent']),
                        'term_count' => esc_html($aioml_media_data['term_count']),
                    );
                }
            }

            wp_cache_set($aioml_cache_key, $aioml_folders);
        }

        // echo wp_json_encode($aioml_folders);
        wp_send_json_success( $aioml_folders );

        $this->AiomlSmack_assign_uncategorized_attachments();

        wp_die();
    }

    private function AiomlSmack_assign_uncategorized_attachments() {
        global $wpdb;

        $aioml_folder_name = "UnCategorized";

        $aioml_term = get_term_by('slug', $aioml_folder_name, 'attachment_category');
        if (!$aioml_term) {
            $aioml_result = wp_insert_term($aioml_folder_name, 'attachment_category', array('slug' => $aioml_folder_name));
            if (is_wp_error($aioml_result)) {
                return;
            }
            $aioml_term_id = $aioml_result['term_id'];
        } else {
            $aioml_term_id = $aioml_term->term_id;
        }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $aioml_attachments = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = 0 AND post_type = 'attachment'");

        foreach ($aioml_attachments as $aioml_attachment_id) {
            wp_update_post(array(
                'ID'          => $aioml_attachment_id,
                'post_parent' => $aioml_term_id,
            ));

            $aioml_result = wp_set_object_terms($aioml_attachment_id, [$aioml_term_id], 'attachment_category');

            if (is_wp_error($aioml_result)) {
            }
        }
    }

    
    public function AiomlSmack_delete_folder_from_database_callback() {
        if (
            !isset($_POST['security']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')
        ) {
            wp_send_json_error('Nonce verification failed.');
        }

        global $wpdb;

        $aioml_folderName = isset($_POST['folder_name']) ? sanitize_text_field(wp_unslash($_POST['folder_name'])) : '';

        if (empty($aioml_folderName)) {
            wp_send_json_error('Missing folder name.');
        }

        $aioml_term = get_term_by('name', $aioml_folderName, 'attachment_category');

        if ($aioml_term && !is_wp_error($aioml_term)) {
            $aioml_term_id = $aioml_term->term_id;

            $this->AiomlSmack_move_attachments_to_uncategorized($aioml_folderName);

            $aioml_media_parent_update_result = $this->AiomlSmackupdate_media_parent_column($aioml_term_id);
            if ($aioml_media_parent_update_result === false) {
                $aioml_error_message = $wpdb->last_error;
                wp_send_json_error('Error updating parent column: ' . esc_html($aioml_error_message));
            }

            $aioml_delete_term_result = wp_delete_term($aioml_term_id, 'attachment_category');
            if (is_wp_error($aioml_delete_term_result)) {
                $aioml_error_message = $aioml_delete_term_result->get_error_message();
                wp_send_json_error('Error deleting folder: ' . esc_html($aioml_error_message));
            }

            $aioml_delete_media_result = $this->AiomlSmack_delete_media_by_term_id($aioml_term_id);
            if ($aioml_delete_media_result === false) {
                $aioml_error_message = $wpdb->last_error;
                wp_send_json_error('Error deleting media: ' . esc_html($aioml_error_message));
            }

            wp_send_json_success('Folder deleted: ' . esc_html($aioml_folderName));
        } else {
            wp_send_json_error('Folder not found: ' . esc_html($aioml_folderName));
        }

        wp_die();
    }


    private function AiomlSmackupdate_media_parent_column($aioml_term_id) {
        global $wpdb;
        $aioml_update_result = wp_cache_get('media_parent_update_result_' . $aioml_term_id);
        if ($aioml_update_result === false) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $aioml_update_result = $wpdb->update(
                "{$wpdb->prefix}aio_media_library",
                array('parent' => 0),
                array('parent' => $aioml_term_id),
                array('%d'),
                array('%d')
            );

            wp_cache_set('media_parent_update_result_' . $aioml_term_id, $aioml_update_result);
        }
        return $aioml_update_result;
    }

    private function AiomlSmack_delete_media_by_term_id($aioml_term_id) {
        global $wpdb;

        $aioml_delete_result = wp_cache_get('delete_media_term_id_' . $aioml_term_id);

        if ($aioml_delete_result === false) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $aioml_delete_result = $wpdb->delete(
                "{$wpdb->prefix}aio_media_library",
                array('term_id' => $aioml_term_id),
                array('%d')
            );

            wp_cache_set('delete_media_term_id_' . $aioml_term_id, $aioml_delete_result);
        }

        return $aioml_delete_result;
    }

    private function AiomlSmack_move_attachments_to_uncategorized($aioml_folderName) {
        // if (
        //     !isset($_POST['security']) ||
        //     !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')
        // ) {
        //     wp_send_json_error('Nonce verification failed.');
        // }

        global $wpdb;

        $aioml_term_id = wp_cache_get('term_id_' . $aioml_folderName, 'folder_data');

        if ($aioml_term_id === false) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $aioml_term_id = $wpdb->get_var(
                $wpdb->prepare("SELECT term_id FROM {$wpdb->terms} WHERE name = %s", $aioml_folderName)
            );

            if (!$aioml_term_id) {
                return;
            }

    // phpcs:ignore WordPress.WP.DiscouragedFunctions.wp_cache_set_wp_cache_set
    wp_cache_set('term_id_' . $aioml_folderName, $aioml_term_id, 'folder_data', HOUR_IN_SECONDS);    }

        $aioml_attachment_ids = wp_cache_get('attachment_ids_' . $aioml_term_id, 'folder_data');

        if ($aioml_attachment_ids === false) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $aioml_attachment_ids = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT p.ID 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
            WHERE tt.term_id = %d 
            AND p.post_type = 'attachment'
            ",
            $aioml_term_id
        )
    );



            wp_cache_set('attachment_ids_' . $aioml_term_id, $aioml_attachment_ids, 'folder_data', HOUR_IN_SECONDS);
        }

        foreach ($aioml_attachment_ids as $aioml_attachment_id) {
            $aioml_result = wp_set_object_terms($aioml_attachment_id, 'uncategorized', 'attachment_category', true);

            if (is_wp_error($aioml_result)) {
            }
        }
    }

    public function AiomlSmackupdate_folder_in_database_callback() {
        check_ajax_referer('folder_nonce', 'security');
        global $wpdb;


        if (isset($_POST['folder_name'], $_POST['new_name'])) {

            $aioml_folderName = sanitize_text_field(wp_unslash($_POST['folder_name']));
            $aioml_newName    = sanitize_text_field(wp_unslash($_POST['new_name']));
            $aioml_newSlug    = sanitize_title($aioml_newName);

            if (!preg_match('/^[a-zA-Z0-9_ ]+$/', $aioml_newName)) {
                wp_send_json_error('Folder name cannot contain special characters.');
            }

            $aioml_cache_key = 'term_by_name_' . md5($aioml_folderName);
            $aioml_term = wp_cache_get($aioml_cache_key, 'terms');

            if (false === $aioml_term) {
                $aioml_term = get_term_by('name', $aioml_folderName, 'attachment_category');
                if ($aioml_term && !is_wp_error($aioml_term)) {
                    wp_cache_set($aioml_cache_key, $aioml_term, 'terms', HOUR_IN_SECONDS);
                }
            }

            if ($aioml_term) {
                $aioml_term_id = $aioml_term->term_id;

                $aioml_term_update_result = wp_update_term($aioml_term_id, 'attachment_category', [
                    'name' => $aioml_newName,
                    'slug' => $aioml_newSlug,
                ]);

                if (!is_wp_error($aioml_term_update_result)) {
                    wp_cache_delete($aioml_term_id, 'terms');

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $aioml_update_media_result = $wpdb->update(
                        $wpdb->prefix . 'aio_media_library',
                        [
                            'name' => $aioml_newName, // new name
                            'slug' => $aioml_newSlug  // new slug
                        ],
                        [ 'term_id' => $aioml_term_id ], // where
                        ['%s','%s'],
                        ['%d']
                    );



                    if ($aioml_update_media_result !== false) {
                        wp_send_json_success([
                            'message'  => 'Folder name and slug updated to: ' . esc_html($aioml_newName),
                            'new_slug' => $aioml_newSlug,
                        ]);
                    } else {
                        wp_send_json_error('Error updating folder name in wp_aio_media_library table: ' . esc_html($wpdb->last_error));
                    }

                } else {
                    wp_send_json_error('Error updating term: ' . esc_html($aioml_term_update_result->get_error_message()));
                }
            } else {
                wp_send_json_error('Folder not found: ' . esc_html($aioml_folderName));
            }

        } else {
            wp_send_json_error('Missing folder name, new name, or new slug.');
        }

        wp_die();
    }

    public function AiomlSmackfolder_DragDrop_database_callback() {
        if (
            !isset($_POST['security']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')
        ) {
            wp_send_json_error('Nonce verification failed.');
        }

        global $wpdb;

        if (isset($_POST['dragged'], $_POST['target'])) {
            $aioml_draggedFolder = intval(wp_unslash($_POST['dragged']));
            $aioml_targetFolder  = intval(wp_unslash($_POST['target']));

            $aioml_table_name = $wpdb->prefix . 'aio_media_library';
            $aioml_taxonomy = 'attachment_category';
            $aioml_data  = ['parent' => $aioml_targetFolder];
            $aioml_where = ['term_id' => $aioml_draggedFolder];

            $cache_key = 'folder_data_' . $aioml_draggedFolder;
            wp_cache_delete($aioml_cache_key, 'folder_data');

            if ($aioml_data && $aioml_where) {
                wp_set_object_terms($aioml_draggedFolder, $aioml_targetFolder, $aioml_taxonomy, false);
                wp_send_json_success("Media (ID: $aioml_draggedFolder) moved to folder ID $aioml_targetFolder");
            } else {
                wp_send_json_error('Missing aioml_data or target_term_id');
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $aioml_result = $wpdb->update($aioml_table_name, $aioml_data, $aioml_where);

            if ($aioml_result === false) {
                wp_send_json_error('Error updating folder in the database.');
            } else {
                wp_send_json_success('Folder updated successfully.');
            }
        } else {
            wp_send_json_error('Missing required parameters.');
        }

        wp_die();
    }

    public function AiomlSmack_move_attachments_to_category_callback() {
        if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')) {
            wp_send_json_error('Nonce verification failed.');
        }

        if (isset($_POST['attachment_id'], $_POST['folder_name'])) {
            $aioml_attachment_id = absint($_POST['attachment_id']);
            
            $aioml_folder_name = sanitize_text_field(wp_unslash($_POST['folder_name']));

            $aioml_term = get_term_by('slug', $aioml_folder_name, 'attachment_category');

            if ($aioml_term && !is_wp_error($aioml_term)) {
                $aioml_term_id = $aioml_term->term_id;

                $aioml_updated = wp_update_post(array(
                    'ID'          => $aioml_attachment_id,
                    'post_parent' => $aioml_term_id,
                ));

                if (is_wp_error($aioml_updated)) {
                    wp_send_json_error('Error updating post parent: ' . $aioml_updated->get_error_message());
                } else {
                    $aioml_result = wp_set_object_terms($aioml_attachment_id, [$aioml_term_id], 'attachment_category');
                    if (is_wp_error($aioml_result)) {
                        wp_send_json_error('Error setting object terms: ' . $aioml_result->get_error_message());
                    } else {
                        wp_send_json_success('Media moved to category successfully.');
                    }
                }
            } else {
                wp_send_json_error('Error retrieving term: Term not found.');
            }
        } else {
            wp_send_json_error('Missing parameters.');
        }
    }

    // public function AiomlSmack_get_sorted_folders() {
    //     if (
    //         !isset($_POST['security']) ||
    //         !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'folder_nonce')
    //     ) {
    //         wp_send_json_error('Nonce verification failed.');
    //     }

    //     global $wpdb;
    //     $aioml_table_name = $wpdb->prefix."aio_media_library"

    //     $aioml_order_by = isset($_POST['order_by']) ? sanitize_text_field(wp_unslash($_POST['order_by'])) : 'name';
    //     $aioml_order_by = in_array($order_by, ['name', 'created_at'], true) ? $order_by : 'name';

    //     $aioml_order = isset($_POST['order']) ? sanitize_text_field(wp_unslash($_POST['order'])) : 'ASC';
    //     $aioml_order = strtoupper($aioml_order) === 'DESC' ? 'DESC' : 'ASC';

    //     $aioml_cache_key = 'aioml_sorted_folders_' . md5($aioml_order_by . '_' . $aioml_order);
    //     $aioml_results = wp_cache_get($aioml_cache_key, 'aioml_folders');

    //     if ($aioml_results === false) {
    //         $aioml_order_by=esc_sql($aioml_order_by);
    //         $aioml_order=esc_sql($aioml_order);

    //         $aioml_sql = "SELECT * FROM $aioml_table_name ORDER BY $aioml_order_by $aioml_order";

    //         // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching ,WordPress.DB.PreparedSQL.NotPrepared
    //     $aioml_results=$wpdb->get_results($aioml_sql, ARRAY_A);

    //         wp_cache_set($aioml_cache_key, $aioml_results, 'aioml_folders', 300);
    //     }

    //     wp_send_json_success($aioml_results);
    // }


}
