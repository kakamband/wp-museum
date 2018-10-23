<?php
/**
 * Creates the collection post type, an instance of CustomPostType.
 *
 * Collections are associated with Wordpress categories. They "contain"
 * museum objects in associated categories. They may optionally contain
 * objects of sub-categories and/or child collections.
 */

require_once ( 'CustomPostType.php' );
require_once ( 'MetaBox.php' );

$collection_options = [
    'type'          => 'collection',
    'label'         => 'Collection',
    'label_plural'  => 'Collections',
    'description'   => 'Contains related museum objects.',
    'menu_icon'     => 'dashicons-archive',
    'hierarchical'  => true,
    'options'       => [
        'capabilities'  => [
            'edit_posts'            => WPM_PREFIX . 'edit_collections',
            'edit_others_posts'     => WPM_PREFIX . 'edit_others_collections',
            'publish_posts'         => WPM_PREFIX . 'publish_collections',
            'read_private_posts'    => WPM_PREFIX . 'read_private_collections',
            'delete_posts'          => WPM_PREFIX . 'delete_collections',
            'edit_published_posts'  => WPM_PREFIX . 'edit_published_collections'
        ],
        'map_meta_cap'  => true
    ] 
];
$collection_post_type = new CustomPostType( $collection_options );
$collection_post_type->add_support( ['thumbnail'] );

/*
 * Custom Fields
 */

$categories = get_categories( array('hide_empty' => false));
$category_options = [-1 => '' ];
foreach ( $categories as $category ) {
    $category_options[$category->cat_ID] = $category->name;
}
$collection_post_type->add_meta_field( 'associated_category', 'Associated Category', 'select', $options=['options'=>$category_options]);
$collection_post_type->add_meta_field( 'include_sub_collections', 'Include Sub Collections', 'checkbox' );
$collection_post_type->add_meta_field( 'include_child_categories', 'Include Child Categories', 'checkbox' );

/*
 * Displays a metabox containing a table of associated objects.
 */
$display_associated_objects = function() {
    
    $collection_objects = get_associated_objects( 'any' );
    
    echo "<table class='wp-list-table widefat striped'>";
    if ( isset( $collection_objects ) && !empty( $collection_objects ) ) {
        foreach ( $collection_objects as $co ) {
        if ( $co->post_type == 'collection' ) continue;
        $permalink = get_permalink( $co->ID );
        $ps = get_post_status_object( $co->post_status )->label;
        echo "<tr>
               <td>{$co->post_title}</td>
               <td><a href='post.php?post={$co->ID}&action=edit'>Edit</a></td>
               <td><a href='{$permalink}'>View</a></td>
               <td>{$ps}</td>
              </tr>";
        }
    }
    else {
        echo "<tr><td>No collection objects found.</td></tr>";
    }
    echo "</table>";
};
$associated_objects_box = new MetaBox ( 'collection_objects', __('Objects'), $display_associated_objects );
$collection_post_type->add_custom_meta ( $associated_objects_box );

/*
 * Register the post type (CustomPostType)
 */
$collection_post_type->register();

/*
 * Registers the Collection post type with Wordpress' REST api. Each
 * collection endpoint contains a list of associated object ids.
 */
add_action( 'rest_api_init', function()  {
    register_rest_field( 'collection', 'associated_objects', array (
        'get_callback'  => function ( $object ) {
            $associated_objects = get_associated_object_ids( $object['id'] );
            foreach ( $associated_objects as $ao ) {
                $obj_post = get_post ( $ao );
                $object_entries[] = array ($ao, $obj_post->post_type);
            }
            return $object_entries;
        },
        'update_callback'   => null,
        'schema'            => null   
    ) );
} );


