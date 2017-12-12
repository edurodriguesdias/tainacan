<?php 

namespace Tainacan\DevInterface;

use Tainacan\Helpers;

class DevInterface {
    
    var $repositories = [];
    var $has_errors = false;
    
    public function __construct() {

        add_action('add_meta_boxes', array(&$this, 'register_metaboxes'));
        add_action('save_post', array(&$this, 'save_post'), 10, 2);
        add_action('admin_enqueue_scripts', array(&$this, 'add_admin_js'));
        
        add_filter('post_type_link', array(&$this, 'permalink_filter'), 10, 3);
        
        global $Tainacan_Collections, $Tainacan_Filters, $Tainacan_Logs, $Tainacan_Metadatas, $Tainacan_Taxonomies;
        
        $repositories = [$Tainacan_Collections, $Tainacan_Filters, $Tainacan_Logs, $Tainacan_Metadatas, $Tainacan_Taxonomies];
        
        foreach ($repositories as $repo) {
            $cpt = $repo->entities_type::get_post_type();
            $this->repositories[$cpt] = $repo;
        }
        
    }
    
    function add_admin_js() {
        global $TAINACAN_BASE_URL;
        wp_enqueue_script('wp-settings',$TAINACAN_BASE_URL . '/js/wp-settings.js');

        $settings = [
            'root' => esc_url_raw( rest_url() ).'tainacan/v2',
            'nonce' => wp_create_nonce( 'wp_rest' )
        ];

        wp_localize_script( 'wp-settings', 'wpApiSettings', $settings );
        wp_enqueue_script('tainacan-dev-admin', $TAINACAN_BASE_URL . '/assets/web-components.js');
    }
    
    /**
     * Filters the permalink for posts to:
     *
     * * Replace Collectino single permalink with the link to the post type archive for items of that collection
     * 
     * @return string new permalink
     */
    function permalink_filter($permalink, $post, $leavename) {
        
        $collection_post_type = \Tainacan\Entities\Collection::get_post_type();
        
        if (!is_admin() && $post->post_type == $collection_post_type) {
            
            $collection = new \Tainacan\Entities\Collection($post);
            $items_post_type = $collection->get_db_identifier();
            
            $post_type_object = get_post_type_object($items_post_type);
            
            if (isset($post_type_object->rewrite) && is_array($post_type_object->rewrite) && isset($post_type_object->rewrite['slug']))
                return site_url($post_type_object->rewrite['slug']);
                
        }
        
        return $permalink;
        
        
        
    }
    
    /**
     * Run through all post types attributes and add metaboxes for them.
     *
     * Also run through all collections metadata and add metaboxes for its items post type
     * 
     * @return void
     */
    function register_metaboxes() {
        
        
        foreach ($this->repositories as $cpt => $repo) {
            
            add_meta_box(
                $cpt . '_properties',
                __('Properties', 'tainacan'), 
                array(&$this, 'properties_metabox_' . $repo->get_name()),
                $cpt, 
                'normal' 
                
            );
            
        }
        
        global $Tainacan_Collections;
        $collections = $Tainacan_Collections->fetch([], 'OBJECT');
        
        foreach ($collections as $col) {
            add_meta_box(
                $col->get_db_identifier() . '_metadata', 
                __('Metadata', 'tainacan'), 
                array(&$this, 'metadata_metabox'),
                $col->get_db_identifier(), //post type
                'normal' 
                
            );
            
            add_meta_box(
                $col->get_db_identifier() . '_metadata_js', 
                __('Metadata Components', 'tainacan'), 
                array(&$this, 'metadata_components_metabox'),
                $col->get_db_identifier(), //post type
                'normal' 
                
            );
        }
        
        
    }
    
    function properties_metabox_Collections() {
        global $Tainacan_Collections;
        $this->properties_metabox($Tainacan_Collections);
    }
    function properties_metabox_Filters() {
        global $Tainacan_Filters;
        $this->properties_metabox($Tainacan_Filters);
    }
    function properties_metabox_Logs() {
        global $Tainacan_Logs;
        $this->properties_metabox($Tainacan_Logs);
    }
    function properties_metabox_Metadatas() {
        global $Tainacan_Metadatas;
        $this->properties_metabox($Tainacan_Metadatas);
    }
    function properties_metabox_Taxonomies() {
        global $Tainacan_Taxonomies;
        $this->properties_metabox($Tainacan_Taxonomies);
    }
    
    function properties_metabox($repo) {
        global $pagenow, $typenow, $post;
        
        $map = $repo->get_map();
        
        $entity = new $repo->entities_type($post);
        
        wp_nonce_field( 'save_'.$repo->get_name(), $repo->get_name().'_noncename' );
        
        ?>
        <div id="postcustomstuff">
            <table>
                
                <thead>
                    <tr>
                        <th class="left"><?php _e('Property', 'tainacan'); ?></th>
                        <th><?php _e('Value', 'tainacan'); ?></th>
                    </tr>
                </thead>
                
                <tbody>
                    
                    <?php foreach ($map as $prop => $mapped): ?>
                        
                        <?php if ($mapped['map'] != 'meta' && $mapped['map'] != 'meta_multi') continue; ?>
                        <?php 
                            $value = $entity->get_mapped_property($prop); 
                            if (is_array($value)) $value = json_encode($value);
                        ?>
                        <tr>
                            <td>
                                <label><?php echo $mapped['title']; ?></label><br/>
                                <small><?php echo $mapped['description']; ?></small>
                            </td>
                            <td>
                                <?php if ($prop == 'collection_id'): ?>
                                    <?php  Helpers\HtmlHelpers::collections_dropdown( $value ); ?>
                                <?php elseif ($prop == 'collections_ids'): ?>
                                    <?php  Helpers\HtmlHelpers::collections_checkbox_list( $value ); ?>
                                <?php elseif ($prop == 'field_type_options'): ?>
                                    <?php echo $value; ?>
                                <?php elseif ($prop == 'field_type'): ?>
                                    <?php echo $this->field_type_dropdown($post->ID,$value); ?>
                                <?php else: ?>
                                        <textarea name="tnc_prop_<?php echo $prop; ?>"><?php echo htmlspecialchars($value); ?></textarea>
                                <?php endif; ?>    
                                
                                
                            </td>
                        </tr>
                        
                    <?php endforeach; ?>
                    
                </tbody>
                
            </table>
        </div>
        <?php

        
    }
    
    
    
    function metadata_metabox() {
        global $Tainacan_Collections, $Tainacan_Item_Metadata, $pagenow, $typenow, $post;
        
        $collections = $Tainacan_Collections->fetch([], 'OBJECT');
        
        // get current collection
        $current_collection = false;
        foreach ($collections as $col) {
            if ($col->get_db_identifier() == $typenow) {
                $current_collection = $col;
                break;
            }
        }
        
        if (false === $current_collection)
            return;
            
        $entity = new \Tainacan\Entities\Item($post);
        
        //for new Items
        if (!$entity->get_collection_id())
            $entity->set_collection($current_collection);
        
        $metadata = $Tainacan_Item_Metadata->fetch($entity, 'OBJECT');
        
        wp_nonce_field( 'save_metadata_'.$typenow, $typenow.'_metadata_noncename' );
        
        ?>
        
        <input type="hidden" name="tnc_prop_collection_id" value="<?php echo $current_collection->get_id(); ?>" />
        
        <div id="postcustomstuff">
            <table>
                
                <thead>
                    <tr>
                        <th class="left"><?php _e('Metadata', 'tainacan'); ?></th>
                        <th><?php _e('Value', 'tainacan'); ?></th>
                    </tr>
                </thead>
                
                <tbody>
                    
                    <?php foreach ($metadata as $item_meta): ?>
                        
                        <?php 
                            $value = $item_meta->get_value();
                            if (is_array($value)) $value = json_encode($value);
                        ?>
                        <tr>
                            <td>
                                <label><?php echo $item_meta->get_metadata()->get_name(); ?></label><br/>
                                <small><?php echo $item_meta->get_metadata()->get_description(); ?></small>
                            </td>
                            <td>
                                <textarea name="tnc_metadata_<?php echo $item_meta->get_metadata()->get_id(); ?>"><?php echo htmlspecialchars($value); ?></textarea>
                            </td>
                        </tr>
                        
                    <?php endforeach; ?>
                    
                </tbody>
                
            </table>
        </div>
        <?php

        
    }
    
    function metadata_components_metabox() {
        global $Tainacan_Collections, $Tainacan_Item_Metadata, $pagenow, $typenow, $post;
        
        $collections = $Tainacan_Collections->fetch([], 'OBJECT');
        
        // get current collection
        $current_collection = false;
        foreach ($collections as $col) {
            if ($col->get_db_identifier() == $typenow) {
                $current_collection = $col;
                break;
            }
        }
        
        if (false === $current_collection)
            return;
            
        $entity = new \Tainacan\Entities\Item($post);
        
        //for new Items
        if (!$entity->get_collection_id())
            $entity->set_collection($current_collection);
        
        $metadata = $Tainacan_Item_Metadata->fetch($entity, 'OBJECT');
        
        wp_nonce_field( 'save_metadata_'.$typenow, $typenow.'_metadata_noncename' );
        
        ?>
        
        <input type="hidden" name="tnc_prop_collection_id" value="<?php echo $current_collection->get_id(); ?>" />
        
        <div id="postcustomstuff">
            <table>
                
                <thead>
                    <tr>
                        <th class="left"><?php _e('Metadata', 'tainacan'); ?></th>
                        <th><?php _e('Value', 'tainacan'); ?></th>
                    </tr>
                </thead>
                
                <tbody>
                    
                    <?php foreach ($metadata as $item_meta): ?>
                        
                        <?php 
                            $value = $item_meta->get_value();
                            if (is_array($value)) $value = json_encode($value);
                        ?>
                        <tr>
                            <td>
                                <label><?php echo $item_meta->get_metadata()->get_name(); ?></label><br/>
                                <small><?php echo $item_meta->get_metadata()->get_description(); ?></small>
                            </td>
                            <td>
                                <?php //echo '<tainacan-text name="'.$item_meta->get_metadata()->get_name().'"></tainacan-text>'; ?>
                                <?php echo  $item_meta->get_metadata()->get_field_type_object()->render( $item_meta ); ?>
                            </td>
                        </tr>
                        
                    <?php endforeach; ?>
                    
                </tbody>
                
            </table>
        </div>
        <?php

        
    }

    function field_type_dropdown($id,$selected) {

        global $Tainacan_Metadatas;

        $class = ( class_exists( $selected ) ) ? new $selected() : '';

        if(is_object( $class )){
            $selected =  str_replace('Tainacan\Field_Types\\','', get_class( $class ) );
        }

        $field_types = $Tainacan_Metadatas->fetch_field_types('NAME');
        ?>
            <select name="tnc_prop_field_type">
                <?php foreach ($field_types as $field_type): ?>
                    <option value="<?php echo $field_type; ?>" <?php selected($field_type, $selected) ?>><?php echo $field_type; ?></option>
                <?php endforeach; ?>
            </select>
            <?php
             if( $class ){
                 $options = get_post_meta($id,'field_type_options',true);
                 $class->set_options($options);
                 echo $class->form();
             }
            ?>
        <?php
    }
    
    function collections_checkbox_list($selected) {
        global $Tainacan_Collections;
        $collections = $Tainacan_Collections->fetch([], 'OBJECT');
        $selected = json_decode($selected);
        ?>
            <?php foreach ($collections as $col): ?>
                
                <input type="checkbox" name="tnc_prop_collections_ids[]" value="<?php echo $col->get_id(); ?>" <?php checked(in_array($col->get_id(), $selected)); ?> style="width: 15px;">
                <?php echo $col->get_name(); ?>
                <br/>
            <?php endforeach; ?>
        <?php
    }
    
    function save_post($post_id, $post) {
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        
        $post_type = $post->post_type;
        
        if (array_key_exists($post_type, $this->repositories)) {
            $repo = $this->repositories[$post_type];
            
            if (!isset($_POST[$repo->get_name().'_noncename']) || !wp_verify_nonce($_POST[$repo->get_name().'_noncename'], 'save_'.$repo->get_name()))
                return;
            
            $map = $repo->get_map();
            $entity = new $repo->entities_type($post);
            
            foreach ($map as $prop => $mapped) {
                
                if ($mapped['map'] != 'meta' && $mapped['map'] != 'meta_multi') 
                    continue; 
                    
                $value = $_POST["tnc_prop_" . $prop];
                if ($mapped['map'] == 'meta_multi') {
                    if (!is_array($value))
                        $value = json_decode($value);
                }
                
                
                $entity->set_mapped_property($prop, $value);


                if ($entity->validate_prop($prop)) {

                    // we cannot user repository->insert here, it would create an infinite loop
                    if ($prop == 'field_type') {
                        //TODO: This can be better
                        $class = '\Tainacan\Field_Types\\'.$value;
                        update_post_meta($post_id, 'field_type_options', $_POST['field_type_'.strtolower( $value ) ] );
                        update_post_meta($post_id, 'field_type',  wp_slash( get_class( new $class() ) ) );
                    } elseif($prop == 'field_type_options') {
                        continue;
                    } elseif ($mapped['map'] == 'meta' || $mapped['map'] == 'meta_multi') {
                        
                        $repo->insert_metadata($entity, $prop);
                        
        			}
                }
                
                // TODO: display validation errors somehow
                // TODO: Actually we will replace it saving via ajax using API
            }
            //die;
        } else {
            
            // Check if post type is an item from a collection
            // TODO: there should ve a method in the repository to find this out
            // or I could try to initialize an entity and find out what type it is
            
            global $Tainacan_Collections, $Tainacan_Items, $Tainacan_Metadatas, $Tainacan_Item_Metadata;
            $collections = $Tainacan_Collections->fetch([], 'OBJECT');
            $cpts = [];
            foreach($collections as $col) {
                $cpts[$col->get_db_identifier()] = $col;
            }
            
            if (array_key_exists($post_type, $cpts)) {
                
                $entity = new \Tainacan\Entities\Item($post);
                
                // for new Items
                if (!$entity->get_collection_id()) {
                    $entity->set_collection($cpts[$post_type]);
                    $Tainacan_Items->insert_metadata($entity, 'collection_id');
                }
                
                
                $metalist = $Tainacan_Metadatas->fetch_by_collection($cpts[$post_type], [], 'OBJECT');
                
                foreach ($metalist as $meta) {
                    $item_meta = new \Tainacan\Entities\Item_Metadata_Entity($entity, $meta);
                    if (isset($_POST['tnc_metadata_' . $meta->get_id()])) {
                        $item_meta->set_value($_POST['tnc_metadata_' . $meta->get_id()]);
                        if ($item_meta->validate()) {
                            $Tainacan_Item_Metadata->insert($item_meta);
                        } else {
                            
                        }
                        
                    }
                }
                
                
            }

            
            
            
        }
        
    }

    
}



 ?>