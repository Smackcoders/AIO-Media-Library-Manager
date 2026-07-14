<?php
namespace Smackcoders\Aioml;

class DatabaseConnection{
    protected $aioml_wpdb = null;
    protected $aioml_table_name = "";
    
    protected function __construct(){
        global $wpdb;
        $this->aioml_wpdb = $wpdb;
        $this->aioml_table_name = $this->aioml_wpdb->prefix."aio_media_library";
    }

    public function InsertFolder($name, $slug, $parent_id, $term_id,$term_taxonomy_id=0) {

        $data = [
            'name'       => $name,
            'slug'       => $slug,
            'term_id'    => $term_id,
            'parent'     => $parent_id,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
        ];

        // Format types
        $format = ['%s','%s','%d','%d','%s','%d'];

        // Insert into DB
        $result = $this->aioml_wpdb->insert(
            $this->aioml_table_name,
            $data,
            $format
        );

        // Check result
        if ($result === false) {
            return [
                'status'  => 'error',
                'message' => 'Failed to insert folder data.'
            ];
        }

        return [
            'status'    => 'success',
            'message'   => 'Folder inserted successfully.',
            'insert_id' => $this->aioml_wpdb->insert_id
        ];
    }
    public function DeleteDataFromDb($term_id){

        $deleted = $this->aioml_wpdb->delete(
            $this->aioml_table_name,   
            ['term_id' => $term_id],   
            ['%d']                     
        );

        if ( $deleted === false ) {
            return(['message' => 'Failed to delete row from custom table']);
        }

        if ( $deleted === 0 ) {
            return(['message' => 'No row found with this term_id']);
        }

        return([
            'message' => 'Folder row deleted successfully',
            'deleted_term_id' => $term_id
        ]);
    }
    public function fetch_all_folders(){

        $terms = get_terms([
            'taxonomy'   => 'attachment_category',
            'hide_empty' => false,
        ]);

        if ( empty($terms) || is_wp_error($terms) ) {
            return [
                'status'  => 'empty',
                'message' => 'No folders found.',
                'data'    => []
            ];
        }

        $data = [];
        foreach ( $terms as $term ) {
            $data[] = [
                'term_id' => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
                'parent'  => $term->parent,
                'count'   => $term->count,
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Fetched all folders from hierarchy',
            'data'   => $data
        ];
    }

    public function ChangeFolderItemCount($term_id,$count){

        $result = $this->aioml_wpdb->update(
            $this->aioml_table_name,
            [ 'count' => $count ],
            [ 'term_id' => $term_id ],
            [ '%d' ],
            [ '%d' ]
        );

        $message ="folder updated on table";
        
        if ($result === false) {
            return "Failed to update folder count.";
        }

        if ($result === 0) {
            return "No change (count already same).";
        }

        return $message;
    }

    public function Aioml_Rename_folder_onDB($aioml_folder_id,$aioml_folder_new_name){

        $result = $this->aioml_wpdb->update(
            $this->aioml_table_name,
            ['name'=>$aioml_folder_new_name],
            ['term_id' => $aioml_folder_id],
            ['%s'],
            ['%d']
        );
        if($result === false){
            return [
                'message'=>'failed to rename folder',
                'success'=>false
            ];
        }
        return [
                'message'=>'Folder renamed on the table',
                'success'=>true
            ];
    }

    public function Aioml_update_Move_folders($term_id, $parent_id)
    {
        try {

            $term_id   = intval($term_id);
            $parent_id = intval($parent_id);

            if ($term_id <= 0) {
                return [
                    'success' => false,
                    'message' => "Invalid term ID ({$term_id})"
                ];
            }

            // Update your custom folder table
            $result = $this->aioml_wpdb->update(
                $this->aioml_table_name,
                ['parent' => $parent_id],
                ['term_id' => $term_id],
                ['%d'],
                ['%d']
            );

            if ($result === false) {
                return [
                    'success' => false,
                    'message' => "Database update failed for term {$term_id}"
                ];
            }

            return [
                'success' => true,
                'message' => "Folder {$term_id} moved under {$parent_id}"
            ];

        } catch (\Exception $e) {

            return [
                'success' => false,
                'message' => "Unexpected error: " . $e->getMessage()
            ];
        }
    }



}
