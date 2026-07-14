<?php
namespace Smackcoders\Aioml;

class Register_Category{

    private static $instance = null;
    private function __construct(){
        add_action( 'init', [$this,'Aioml_register_attachment_category_taxonomy'],0 );
        // for grid mode
        add_filter( 'ajax_query_attachments_args',[$this,'Aioml_Ajax_query_attachment_args']);
        add_filter( 'rest_media_query',[$this,'Aioml_Rest_media_Query'], 10, 2 ); 
        add_action( 'rest_api_init',[$this,'Aioml_Rest_api_Init']);
        add_filter( 'rest_media_collection_params',[$this,'Aioml_Rest_media_colllection_params']);
        // for list mode
        // add_action('restrict_manage_posts', [$this,'Aioml_Restrict_manage_posts']);  // for add drop down menu on list mode.
        add_action('pre_get_posts', [$this,'Aioml_pre_get_posts'],1,1);
        add_action('edited_attachment', [$this,'Aioml_edited_attachment']);
        
        // remove media from the attachment_category
        add_action('rest_api_init', [$this,'Aioml_rest_media_remove_item']);

    }

    public static function GetInstance(){
        if(self::$instance === null ){
            self::$instance = new self();
        }
        return self::$instance;
    }

   public function Aioml_register_attachment_category_taxonomy() {
        $labels = array(
            'name'              => 'Folder',
            'singular_name'     => 'Attachment Category',
            'search_items'      => 'Search Attachment Categories',
            'all_items'         => 'All Attachment Categories',
            'edit_item'         => 'Edit Attachment Category',
            'update_item'       => 'Update Attachment Category',
            'add_new_item'      => 'Add New Attachment Category',
            'new_item_name'     => 'New Attachment Category Name',
            'menu_name'         => 'Attachment Categories'
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => false,
            'show_admin_column' => true,
            'show_in_menu'      => false,  
            'query_var'         => false, //for show data in list 
            'public'            => true,        // 🔥 REQUIRED
            'show_in_rest'      => true,        // 🔥 REQUIRED
            'rewrite'           => array( 'slug' => 'attachment-category' ),
            'update_count_callback' => '_update_generic_term_count',
        );

        register_taxonomy( 'attachment_category', 'attachment', $args );
        register_taxonomy_for_object_type( 'attachment_category', 'attachment' );

    }

     
    // public function Aioml_Ajax_query_attachment_args( $query ) {

    //     // Check both formats
    //     $folder = null;

    //     if ( ! empty( $_REQUEST['attachment_category'] ) ) {
    //         $folder = intval( $_REQUEST['attachment_category'] );
    //     }

    //     if ( isset($_REQUEST['query']['attachment_category']) ) {
    //         $folder = intval( $_REQUEST['query']['attachment_category'] );
    //     }

    //     // If no folder filter → return unmodified query
    //     if ( !$folder ) {
    //         return $query;
    //     }

    //     // Ensure tax_query exists
    //     if ( ! isset( $query['tax_query'] ) || ! is_array( $query['tax_query'] ) ) {
    //         $query['tax_query'] = [];
    //     }

        
    //     // Add our taxonomy filter
    //     $query['tax_query'][] = [
    //         'taxonomy' => 'attachment_category',
    //         'field'    => 'term_id',
    //         'terms'    => [$folder],
    //     ];

    //     return $query;
    // }

    public function Aioml_Ajax_query_attachment_args( $query ) {

        $folder = null;

        // Check both request formats
        if ( ! empty( $_REQUEST['attachment_category'] ) ) {
            $folder = intval( $_REQUEST['attachment_category'] );
        }

        if ( isset( $_REQUEST['query']['attachment_category'] ) ) {
            $folder = intval( $_REQUEST['query']['attachment_category'] );
        }

        // Always remove the taxonomy parameter from query variables to prevent WordPress core 
        // from doing an automatic slug-based term lookup (which returns 0 results for -1 / -2)
        if ( isset( $query['attachment_category'] ) ) {
            unset( $query['attachment_category'] );
        }

        // -1 → ALL media files (no filtering)
        if ( $folder === -1 || $folder === null ) {
            return $query;
        }

        // Ensure tax_query exists
        if ( ! isset( $query['tax_query'] ) || ! is_array( $query['tax_query'] ) ) {
            $query['tax_query'] = [];
        }

        // -2 → Uncategorized (no attachment_category assigned)
        if ( $folder === -2 ) {

            $query['tax_query'][] = [
                'taxonomy' => 'attachment_category',
                'operator' => 'NOT EXISTS',
            ];

            return $query;
        }

        // Normal category filtering (> 0)
        if ( $folder > 0 ) {

            $query['tax_query'][] = [
                'taxonomy' => 'attachment_category',
                'field'    => 'term_id',
                'terms'    => [ $folder ],
            ];
        }

        return $query;
    }




    
    public function Aioml_Rest_media_Query( $args, $request ) {

        if ( isset( $request['attachment_category'] ) ) {
            $folder = intval( $request['attachment_category'] );

            // Always unset the taxonomy query var to avoid automatic slug search in REST API
            if ( isset( $args['attachment_category'] ) ) {
                unset( $args['attachment_category'] );
            }

            // -1 → ALL media files (no filtering)
            if ( $folder === -1 ) {
                return $args;
            }

            // Ensure tax_query exists
            if ( ! isset( $args['tax_query'] ) || ! is_array( $args['tax_query'] ) ) {
                $args['tax_query'] = [];
            }

            // -2 → Uncategorized (no attachment_category assigned)
            if ( $folder === -2 ) {
                $args['tax_query'][] = [
                    'taxonomy' => 'attachment_category',
                    'operator' => 'NOT EXISTS',
                ];
            } elseif ( $folder > 0 ) {
                $args['tax_query'][] = [
                    'taxonomy' => 'attachment_category',
                    'field'    => 'term_id',
                    'terms'    => [ $folder ],
                ];
            }
        }

        return $args;
    }


    
    public function Aioml_Rest_api_Init() {
        register_rest_field( 'attachment', 'attachment_category', [
            'get_callback' => function ( $object ) {
                return wp_get_object_terms( $object['id'], 'attachment_category', [
                    'fields' => 'ids'
                ]);
            },
            'schema' => [
                'type'  => 'array',
                'items' => ['type' => 'integer'],
            ],
        ]);
    }

    public function Aioml_Rest_media_colllection_params( $params ) {
        $params['attachment_category'] = [
            'description' => 'Filter media by attachment_category term ID',
            'type'        => 'integer',
            'required'    => false,
        ];
        return $params;
    }



    // List mode page render

    /** * Add taxonomy dropdown to Media Library list view */
    // public function Aioml_Restrict_manage_posts() {
    //     global $typenow;

    //     if ($typenow !== 'attachment') return;

    //     $taxonomy = 'attachment_category';
    //     $selected = isset($_GET[$taxonomy]) ? intval($_GET[$taxonomy]) : '';

    //     wp_dropdown_categories([
    //         'taxonomy'        => $taxonomy,
    //         'name'            => $taxonomy,
    //         'show_option_all' => 'All Folders',
    //         'hide_empty'      => false,
    //         'hierarchical'    => true,
    //         'orderby'         => 'name',
    //         'selected'        => $selected,
    //         'value_field'     => 'term_id',
    //     ]);
    // }


    /** * Let WP_Query filter attachments by taxonomy in list mode */
    /**
 
    * Robust pre_get_posts for Uploads list table.
 */
    // public function Aioml_pre_get_posts( $query ) {

    //     // Only run in admin
    //     if ( ! is_admin() ) {
    //         return;
    //     }

    //     // We only care when the uploads page is being built
    //     global $pagenow;
    //     if ( ! isset( $pagenow ) || $pagenow !== 'upload.php' ) {
    //         return;
    //     }

    //     // If there's no attachment_category request param, nothing to do
    //     if ( empty( $_GET['attachment_category'] ) ) {
    //         return;
    //     }

        

    //     // Ensure the query operates on attachments
    //     $post_type = $query->get( 'post_type' );

    //     if ( empty( $post_type ) ) {
    //         // WP sometimes omits post_type for the uploads table — force it
    //         $query->set( 'post_type', 'attachment' );
    //     } else {
    //         // if array, ensure attachment present
    //         if ( is_array( $post_type ) ) {
    //             if ( ! in_array( 'attachment', $post_type, true ) ) {
    //                 // not targeting attachments
    //                 return;
    //             }
    //         } else {
    //             if ( $post_type !== 'attachment' ) {
    //                 return;
    //             }
    //         }
    //     }

    //     // Force main query (if WP uses secondary queries the taxonomy param was explicitly provided,
    //     // we still want to apply it to the main one; but keep applying only to main query here)
    //     // (you can remove this check if you need to manipulate non-main queries also)
    //     // if ( ! $query->is_main_query() ) { return; }

    //     // Build / append tax_query safely
    //     $folder_id = intval( $_GET['attachment_category'] );
    //     if ( $folder_id <= 0 ) {
    //         return;
    //     }

    //     $existing = $query->get( 'tax_query' );
    //     if ( ! is_array( $existing ) ) {
    //         $existing = [];
    //     }

    //     $existing[] = [
    //         'taxonomy'         => 'attachment_category',
    //         'field'            => 'term_id',
    //         'terms'            => [ $folder_id ],
    //         'include_children' => false,
    //     ];

    //     $query->set( 'tax_query', $existing );

    //     // Optional: ensure posts_per_page is set (list table has its own pagination)
    //     if ( ! $query->get( 'posts_per_page' ) ) {
    //         $query->set( 'posts_per_page', 20 );
    //     }

    // }

    public function Aioml_pre_get_posts( $query ) {

        // Only run in admin
        if ( ! is_admin() ) {
            return;
        }

        // Only apply on Media Library (upload.php)
        global $pagenow;
        if ( ! isset( $pagenow ) || $pagenow !== 'upload.php' ) {
            return;
        }

        // Must contain attachment_category param
        if ( ! isset($_GET['attachment_category']) ) {
            return;
        }

        // Ensure post_type = attachment
        $post_type = $query->get( 'post_type' );

        if ( empty( $post_type ) ) {
            $query->set( 'post_type', 'attachment' );
        } else {
            if ( is_array( $post_type ) ) {
                if ( ! in_array( 'attachment', $post_type, true ) ) {
                    return;
                }
            } else {
                if ( $post_type !== 'attachment' ) {
                    return;
                }
            }
        }

        $folder_id = intval( $_GET['attachment_category'] );

        // Always unset the taxonomy query var to avoid automatic slug search in WP_Query
        $query->set( 'attachment_category', '' );

        // --------------------------------------------------
        // 🔥 CASE 1: ID = -1 → SHOW ALL MEDIA
        // --------------------------------------------------
        if ( $folder_id === -1 ) {
            $query->set( 'tax_query', [] );
            return;
        }

        // --------------------------------------------------
        // 🔥 CASE 2: ID = -2 → SHOW UNCATEGORIZED MEDIA ONLY
        // --------------------------------------------------
        if ( $folder_id === -2 ) {

            $tax_query = [
                [
                    'taxonomy' => 'attachment_category',
                    'operator' => 'NOT EXISTS',   // THIS IS THE KEY
                ]
            ];

            $query->set( 'tax_query', $tax_query );
            return;
        }

        // --------------------------------------------------
        // NORMAL FOLDER FILTER
        // --------------------------------------------------
        if ( $folder_id > 0 ) {

            $existing = $query->get( 'tax_query' );
            if ( ! is_array( $existing ) ) {
                $existing = [];
            }

            $existing[] = [
                'taxonomy'         => 'attachment_category',
                'field'            => 'term_id',
                'terms'            => [ $folder_id ],
                'include_children' => false,
            ];

            $query->set( 'tax_query', $existing );
        }

        // Set pagination if empty
        if ( ! $query->get( 'posts_per_page' ) ) {
            $query->set( 'posts_per_page', 20 );
        }
    }








    /** * Ensure taxonomy count is correct when assigning attachments */
    public function Aioml_edited_attachment($post_id) {
        $terms = wp_get_object_terms($post_id, 'attachment_category', ['fields' => 'ids']);
        if (!empty($terms)) {
            wp_update_term_count_now($terms, 'attachment_category');
        }
    }

    
    public function Aioml_rest_media_remove_item() {

        register_rest_route('aioml/v1', '/media/remove-from-folder', [
            'methods'  => 'POST',
            'callback' => [$this,'aioml_remove_media_from_folder'],
            'permission_callback' => function () {
                return current_user_can('upload_files');
            },
            'args' => [
                'media_id' => [
                    'required' => true,
                    'type'     => 'integer',
                ],
                'term_id' => [
                    'required' => true,
                    'type'     => 'integer',
                ],
            ],
        ]);

    }
    
    public function aioml_remove_media_from_folder( $request ) {

        $media_id = (int) $request->get_param('media_id');
        $term_id  = (int) $request->get_param('term_id');

        if ( ! $media_id || ! $term_id ) {
            return new \WP_Error('invalid_data', 'Invalid media or folder ID', [ 'status' => 400 ]);
        }

        // Ensure attachment exists
        $attachment = get_post($media_id);
        if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
            return new \WP_Error('invalid_attachment', 'Media not found', [ 'status' => 404 ]);
        }

        $taxonomy = 'attachment_category';

        // Get current terms
        $terms = wp_get_object_terms($media_id, $taxonomy, ['fields' => 'ids']);

        if ( is_wp_error($terms) ) {
            return new \WP_Error('term_error', 'Could not read attachment terms', [ 'status' => 500 ]);
        }

        // Remove selected term
        $new_terms = array_diff($terms, [ $term_id ]);

        // Update attachment terms
        wp_set_object_terms($media_id, $new_terms, $taxonomy, false);

        return rest_ensure_response([
            'success'  => true,
            'media_id' => $media_id,
            'removed_term' => $term_id,
        ]);
    }


 


}
