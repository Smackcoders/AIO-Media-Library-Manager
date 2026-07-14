<?php
namespace Smackcoders\Aioml;
require_once plugin_dir_path(__FILE__).'/aioml-database.php';
class FolderAction extends DatabaseConnection{
    private static $instance = null;

    private function __construct(){
        parent::__construct();
        
        // fetch all folders from custom table
        add_action("wp_ajax_fetch_all_folder",[$this,"fetchAllFolders"]);
        // create folder
        add_action("wp_ajax_create_new_folder",[$this,"createNewFolder"]);        
        // delete a single folder
        add_action("wp_ajax_delete_aioml_folder",[$this,"Aioml_deleteFolder"]);
        // delete multiple folders
        add_action("wp_ajax_delete_aioml_folders",[$this,"Aioml_deleteFolders"]);
        // mediafile drag and drop
        add_action("wp_ajax_move_attachment_to_term_id",[$this,"Move_Attachment_to_term_id"]);
        // rename a folder
        add_action("wp_ajax_aioml_rename_folder",[$this,"Aioml_Rename_Folder"]);
        // copy and paste folder(s) — authenticated users only
        add_action("wp_ajax_aioml_copy_paste_folders",[$this,"Aioml_copy_paste_folders"]);
        // cut and paste folder(s) — authenticated users only
        add_action("wp_ajax_aioml_cut_paste_folders",[$this,"Aioml_cut_paste_folders"]);
    }
    public static function GetInstance(){
        if(self::$instance === null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function createNewFolder(){
        if (!isset($_POST['security']) ||
            !wp_verify_nonce($_POST['security'], 'custom_media_folder_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed']);
        }

        if( !isset($_POST['folder_name']) ){
            wp_send_json_error(['message'=>'folder name not set']);
        }

        $aioml_foldername = isset($_POST['folder_name']) ? sanitize_text_field( wp_unslash( $_POST['folder_name'] ) ) : "";
        $aioml_ParentId   = isset($_POST['parent_id']) ? intval( $_POST['parent_id'] ) : 0;
        $base_slug = sanitize_title($aioml_foldername);
        $aioml_slug = wp_unique_term_slug(
            $base_slug,
            (object) ['taxonomy' => 'attachment_category', 'parent' => $aioml_ParentId]
        );

        // if ( $existing_term ) {
        //     wp_send_json_error(['message' => 'A folder with this name already exists.']);
        // }

        $result = wp_insert_term(
            $aioml_foldername,   
            'attachment_category',
            array(
                'slug'   => $aioml_slug,
                'parent' => $aioml_ParentId
            )
        );

        if ( is_wp_error($result) ) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        $term = get_term( $result['term_id'], 'attachment_category' );
        if ( $term && ! is_wp_error( $term ) ) {
            $aioml_slug = $term->slug;
        }

        $db_result = $this->InsertFolder($aioml_foldername,$aioml_slug ,$aioml_ParentId,$result['term_id'],$result['term_taxonomy_id']);
        
           wp_send_json_success([
            'message' => 'Folder created successfully.'.'->'.$db_result['message'],
            'term_id'=>$result['term_id'],
            'term_taxonomy_id'=>$result['term_taxonomy_id'],
            'folder_name'=>$aioml_foldername,
            'slug'=>$aioml_slug,
            'parent_id'=>$aioml_ParentId
           ]);
    }

    public function Aioml_deleteFolder() {

        if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'custom_media_folder_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }

        if ( ! isset($_POST['term_id']) ) {
            wp_send_json_error(['message' => 'Folder ID not provided.']);
        }

        $aioml_term_id = intval($_POST['term_id']);

        if ( $aioml_term_id <= 0 ) {
            wp_send_json_error(['message' => 'Invalid folder ID.']);
        }

        $term = get_term( $aioml_term_id, 'attachment_category' );

        if ( ! $term || is_wp_error($term) ) {
            wp_send_json_error(['message' => 'Folder does not exist.']);
        }

        $deleted = wp_delete_term( $aioml_term_id, 'attachment_category' );

        if ( is_wp_error($deleted) ) {
            wp_send_json_error(['message' => $deleted->get_error_message()]);
        }
        if ( ! $deleted ) {
            wp_send_json_error(['message' => 'Failed to delete folder.']);
        }
        $result = $this->DeleteDataFromDb($aioml_term_id);
        wp_send_json_success([
            'message' => 'Folder deleted successfully.'.$result['message'],
            'deleted_term_id' => $aioml_term_id
        ]);
    }
    public function Aioml_deleteFolders() {
        if (
            ! isset( $_POST['security'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'custom_media_folder_nonce' )
        ) {
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ], 403 );
        }

        // Get raw posted JSON string safely
        $delete_ids_raw = isset( $_POST['delete_ids'] ) ? wp_unslash( $_POST['delete_ids'] ) : '';

        // Decode — expecting JSON array like: [20,22,23] OR a JSON-encoded string of IDs
        $delete_ids = json_decode( $delete_ids_raw, true );

        // If decode fails, try fallback: maybe it's a comma-separated string "20,22,23"
        if ( ! is_array( $delete_ids ) ) {
            // try fallback
            $maybe_csv = trim( $delete_ids_raw );
            if ( $maybe_csv === '' ) {
                wp_send_json_error( [ 'message' => 'No folder IDs provided.' ], 400 );
            }
            $parts = array_filter( array_map( 'trim', explode( ',', $maybe_csv ) ) );
            if ( empty( $parts ) ) {
                wp_send_json_error( [ 'message' => 'Invalid delete_ids format. Provide a JSON array like [20,22].' ], 400 );
            }
            $delete_ids = $parts;
        }

        // Normalize to integers and unique
        $delete_ids = array_map( 'intval', $delete_ids );
        $delete_ids = array_values( array_filter( array_unique( $delete_ids ), function ( $v ) {
            return $v > 0;
        } ) );

        if ( empty( $delete_ids ) ) {
            wp_send_json_error( [ 'message' => 'No valid folder IDs provided.' ], 400 );
        }

        $total_count = [];
        $errors = [];

        foreach ( $delete_ids as $delete_id ) {
            // call method on $this and get result
            $res = $this->Aioml_bulkDelete( $delete_id );

            if ( isset( $res['success'] ) && $res['success'] ) {
                $total_count[] = [
                    'deleted_term_id' => $delete_id,
                    'message' => $res['message'] ?? 'Deleted'
                ];
            } else {
                // collect error but continue
                $errors[] = [
                    'term_id' => $delete_id,
                    'message' => $res['message'] ?? 'Unknown error'
                ];
            }
        }

        $response = [
            'deleted' => count( $total_count ),
            'failed'  => count( $errors ),
            'details' => [
                'deleted_items' => $total_count,
                'failed_items'  => $errors,
            ],
        ];

        if ( count( $errors ) > 0 ) {
            wp_send_json_success( $response, 207 ); // 207 Multi-Status (informational); still success wrapper for AJAX
        }

        wp_send_json_success( $response );
    }

    /**
     * Try to delete one term. Return array with success true/false and message.
     * This function no longer sends JSON responses directly.
     */
    public function Aioml_bulkDelete( $aioml_term_id ) {
        // Normalize
        $aioml_term_id = intval( $aioml_term_id );
        if ( $aioml_term_id <= 0 ) {
            return [ 'success' => false, 'message' => 'Invalid folder ID or Try to delete the Permanent folders.' ];
        }

        $term = get_term( $aioml_term_id, 'attachment_category' );

        if ( ! $term || is_wp_error( $term ) ) {
            return [ 'success' => false, 'message' => 'Folder does not exist.' ];
        }

        // Attempt delete
        $deleted = wp_delete_term( $aioml_term_id, 'attachment_category' );

        if ( is_wp_error( $deleted ) ) {
            return [ 'success' => false, 'message' => $deleted->get_error_message() ];
        }

        if ( ! $deleted ) {
            return [ 'success' => false, 'message' => 'Failed to delete folder (wp_delete_term returned false).' ];
        }

        // Remove any additional data from DB — assume this method exists and returns array or string
        $result = [];
        if ( method_exists( $this, 'DeleteDataFromDb' ) ) {
            try {
                $result = $this->DeleteDataFromDb( $aioml_term_id );
            } catch ( Exception $e ) {
                // keep going — only include the warning in message
                $result = [ 'message' => 'DeleteDataFromDb exception: ' . $e->getMessage() ];
            }
        }

        $msg = 'Folder deleted successfully.';
        if ( is_array( $result ) && isset( $result['message'] ) ) {
            $msg .= ' ' . $result['message'];
        } elseif ( is_string( $result ) && ! empty( $result ) ) {
            $msg .= ' ' . $result;
        }

        return [
            'success' => true,
            'message' => $msg,
        ];
    }

    
    public function fetchAllFolders(){
        if (!isset($_POST['security']) ||
            !wp_verify_nonce($_POST['security'], 'custom_media_folder_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed']);
        }
        $result = $this->fetch_all_folders();

        wp_send_json_success([
            'message'=>$result['message'],
            'data'=> $result['data']
        ]);
    }

    public function Move_Attachment_to_term_id() {
        if (!isset($_POST['security']) ||
            !wp_verify_nonce($_POST['security'], 'custom_media_folder_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed']);
        }

        if ( ! isset( $_POST['folder_id'], $_POST['attachment_id'] ) ) {
            wp_send_json_error( [ 'message' => 'folder_id or attachmentId missing' ] );
        }

        $folder_id     = absint( wp_unslash( $_POST['folder_id'] ) );
        $attachment_id = absint( wp_unslash( $_POST['attachment_id'] ) );

        // Validate term ID
        if ( ! term_exists( $folder_id, 'attachment_category' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid folder (term) ID' ] );
        }

        // Validate media ID
        if ( get_post_type( $attachment_id ) !== 'attachment' ) {
            wp_send_json_error( [ 'message' => 'Invalid media file (attachmentId)' ] );
        }

        //  Assign the attachment to the term reference only
        wp_add_object_terms( $attachment_id, $folder_id, 'attachment_category' );

        // Update the term count so WP can filter correctly
        wp_update_term_count_now( [ $folder_id ], 'attachment_category' );

        //  Save attachment IDs in custom term meta
        $existing = get_term_meta( $folder_id, 'folder_attachments', true );
        if ( ! is_array( $existing ) ) {
            $existing = [];
        }

        if ( ! in_array( $attachment_id, $existing, true ) ) {
            $existing[] = $attachment_id;
            update_term_meta( $folder_id, 'folder_attachments', $existing );
        }

        $term            = get_term( $folder_id, 'attachment_category' );
        $attachment_count = (int) $term->count;
        $result_msg = $this->ChangeFolderItemCount($folder_id,$attachment_count);

        wp_send_json_success( [
            'message'      => 'Media successfully assigned to folder & '.$result_msg,
            'folder_id'    => $folder_id,
            'attachmentId' => $attachment_id,
            'count'        => count( $existing ), // optional
        ] );
    }

    public function Aioml_Rename_Folder(){
        if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'custom_media_folder_nonce'))
            wp_send_json_error('Nonce verification failed.');

        $aioml_folder_id     = absint( wp_unslash( $_POST['folder_id'] ) );
        $aioml_new_name = sanitize_text_field( wp_unslash( $_POST['new_name'] ));

        if ( $aioml_folder_id <= 0 || empty($aioml_new_name) ) {
            wp_send_json_error(['message' => 'Invalid folder ID or name.']);
        }

        // Update the taxonomy term (source of truth for fetchAllFolders)
        $new_slug = wp_unique_term_slug(
            sanitize_title($aioml_new_name),
            (object) ['taxonomy' => 'attachment_category']
        );
        $term_update = wp_update_term($aioml_folder_id, 'attachment_category', [
            'name' => $aioml_new_name,
            'slug' => $new_slug,
        ]);

        if ( is_wp_error($term_update) ) {
            wp_send_json_error(['message' => $term_update->get_error_message()]);
        }

        // Update custom table for consistency
        $aioml_result = $this->Aioml_Rename_folder_onDB($aioml_folder_id,$aioml_new_name);

        if(!$aioml_result['success']){
            wp_send_json_error(['message'=>$aioml_result['message']]); 
        }
        
        wp_send_json_success( [
            'message' =>$aioml_result['message'],
            'term_id' => $aioml_folder_id,
            'name' => $aioml_new_name,
            'slug' => $new_slug,
        ] );

    }


    public function Aioml_copy_paste_folders() {
        if (
            ! isset( $_POST['security'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'custom_media_folder_nonce' )
        ) {
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ], 403 );
        }

        // Get raw value: e.g. "[20,22,23]"
        $copy_ids_raw = isset($_POST['copy_ids']) ? wp_unslash($_POST['copy_ids']) : '';

        // Decode JSON: "[20,22,23]" → array(20,22,23)
        $copy_ids = json_decode($copy_ids_raw, true);

        // If decode fails, return error
        if (!is_array($copy_ids)) {
            wp_send_json_error(['message' => 'Invalid copy_ids format. Must be JSON array like [20,22,23].']);
        }

        // Convert to integers
        $copy_ids = array_map('intval', $copy_ids);

        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

        if (empty($copy_ids)) {
            wp_send_json_error(['message' => 'No folders selected to copy.']);
        }

        $taxonomy = 'attachment_category';
        $created = [];

        foreach ($copy_ids as $source_id) {
            $this->Aioml_duplicate_term($source_id, $parent_id, $taxonomy, $created);
        }

        if (empty($created)) {
            wp_send_json_error(['message' => 'Failed to copy folder(s).']);
        }
        foreach($created as $create){
            $this->InsertFolder($create['name'],$create['slug'],$create['parent'],$create['term_id']);
        }
        wp_send_json_success([
            'term_id_arr' => $created
        ]);
    }

    /**
     * Duplicate a term and its entire subtree.
     * - Ensures unique name (name-copy, name-copy-2, ...)
     * - Ensures unique slug (slug-copy, slug-copy-2, ...)
     * - Recurses properly using the new term id as parent for children
     */
    public function Aioml_duplicate_term($orig_term_id, $new_parent_id, $taxonomy, &$output)
    {
        $term = get_term($orig_term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            error_log("Aioml_duplicate_term: invalid term id {$orig_term_id}");
            return;
        }

        // --- UNIQUE NAME ---
        $base_name = $term->name . '-copy';
        $new_name  = $base_name;
        $name_index = 2;
        while (term_exists($new_name, $taxonomy)) {
            $new_name = $base_name . '-' . $name_index;
            $name_index++;
        }

        // --- UNIQUE SLUG ---
        $base_slug = sanitize_title($term->slug ?: $term->name);
        $new_slug  = sanitize_title($base_slug . '-copy');
        $slug_index = 2;
        while (get_term_by('slug', $new_slug, $taxonomy)) {
            $new_slug = sanitize_title($base_slug . '-copy-' . $slug_index);
            $slug_index++;
        }

        // --- INSERT NEW TERM ---
        $insert = wp_insert_term($new_name, $taxonomy, [
            'slug'   => $new_slug,
            'parent' => intval($new_parent_id)
        ]);

        if (is_wp_error($insert)) {
            error_log("Aioml_duplicate_term: wp_insert_term failed for orig {$orig_term_id} -> " . $insert->get_error_message());
            return;
        }

        $new_term_id = intval($insert['term_id']);

        // --- COPY ATTACHMENTS (MEDIA) ---
        $orig_attachments = get_term_meta($orig_term_id, 'folder_attachments', true);
        if (!is_array($orig_attachments)) {
            $orig_attachments = [];
        }

        foreach ($orig_attachments as $attachment_id) {
            // Assign attachment to new folder
            wp_add_object_terms($attachment_id, $new_term_id, $taxonomy);

            // Update new folder's meta
            $existing = get_term_meta($new_term_id, 'folder_attachments', true);
            if (!is_array($existing)) $existing = [];

            if (!in_array($attachment_id, $existing, true)) {
                $existing[] = $attachment_id;
                update_term_meta($new_term_id, 'folder_attachments', $existing);
            }
        }

        // Update term count
        wp_update_term_count_now([$new_term_id], $taxonomy);

        // --- SAVE NEW TERM INFO FOR FRONTEND ---
        $output[] = [
            'name'    => $new_name,
            'term_id' => $new_term_id,
            'parent'  => intval($new_parent_id),
            'slug'    => $new_slug
        ];

        // --- RECURSE CHILD TERMS ---
        $children = get_terms([
            'taxonomy'   => $taxonomy,
            'parent'     => $orig_term_id,
            'hide_empty' => false,
        ]);

        if (!empty($children) && !is_wp_error($children)) {
            foreach ($children as $child) {
                $this->Aioml_duplicate_term($child->term_id, $new_term_id, $taxonomy, $output);
            }
        }
    }

    public function Aioml_cut_paste_folders()
    {
        try {
            if (
                ! isset( $_POST['security'] ) ||
                ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'custom_media_folder_nonce' )
            ) {
                wp_send_json_error( [ 'message' => 'Nonce verification failed' ], 403 );
            }

            // Read parent_id (with fallback for typo parenet_id)
            $new_parent_id = intval($_POST['parent_id'] ?? ($_POST['parenet_id'] ?? 0));

            // Read & decode cut_ids: "[37]" → [37]
            $raw_ids = isset($_POST['cut_ids']) ? wp_unslash($_POST['cut_ids']) : '';
            $cut_ids = json_decode($raw_ids, true);

            if (!is_array($cut_ids)) {
                wp_send_json_error(['message' => 'Invalid cut_ids format.']);
            }

            if (empty($cut_ids)) {
                wp_send_json_error(['message' => 'No folders selected to cut.']);
            }

            $taxonomy = 'attachment_category';

            foreach ($cut_ids as $term_id) {

                $term_id = intval($term_id);
                $exists = term_exists($term_id, $taxonomy);

                if (!$exists) {
                    wp_send_json_error(['message' => "Folder ID {$term_id} does not exist."]);
                }

                // Ensure WordPress recognizes term before updating parent
                wp_set_object_terms(0, $term_id, $taxonomy, true);

                // Update parent using WordPress default function
                $update = wp_update_term($term_id, $taxonomy, [
                    'parent' => $new_parent_id
                ]);

                if (is_wp_error($update)) {
                    wp_send_json_error([
                        'message' => "Failed to move folder {$term_id}: " . $update->get_error_message()
                    ]);
                }

                // Clear term cache to reflect updated hierarchy
                clean_term_cache($term_id, $taxonomy);

                // Update your custom table
                $custom = $this->Aioml_update_Move_folders($term_id, $new_parent_id);

                if (!$custom['success']) {
                    wp_send_json_error(['message' => $custom['message']]);
                }
            }

            // Success
            wp_send_json_success([
                'message' => 'Folders cut and moved successfully.'
            ]);

        } catch (\Exception $e) {

            wp_send_json_error([
                'message' => 'Unexpected error during cut: ' . $e->getMessage()
            ]);
        }
    }

















}
