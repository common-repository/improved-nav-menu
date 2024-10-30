<?php
/*
  Plugin Name: Improved Nav Menu
  Plugin URI: http://www.neoceane.com/
  Description: Some usefull addons on nav menu. Adding first and last class to <li> element. Adding redirection capacities to Menu Item in Administration.
  Version:1.0
  Author: neOceane 
  Author URI: http://www.neoceane.com/
 */
 
    define( 'NEO_INM_PATH', plugin_dir_path(__FILE__) );
 
    //création du custom fields dans l'interface des menus
    include(NEO_INM_PATH.'/NeoInmEditWalker.class.php');
    
    // include Walker Class
    require(NEO_INM_PATH.'/NeoInmWalker.class.php');
 
 
    /* sélection du walker appelé par wordpress */
    function neo_inm_nav_menu_args( $args = array() ) {
        $args['walker'] = new Neo_Inm_Walker();
        return $args;
    }

    add_filter( 'wp_nav_menu_args', 'neo_inm_nav_menu_args', 10, 1 );
	
	/* Nav Custom Menu edition*/
	
    /*
     * Saves new field to postmeta for navigation 
     */
    add_action('wp_update_nav_menu_item', 'neo_inm_nav_update',10, 3);
    function neo_inm_nav_update($menu_id, $menu_item_db_id, $args ) {
        if ( is_array($_REQUEST['menu-item-custom']) ) {
            $neo_inm_value = $_REQUEST['menu-item-custom'][$menu_item_db_id];
            update_post_meta( $menu_item_db_id, '_menu_item_custom', $neo_inm_value );
        }
    }

    /*
     * Adds value of new field to $item object that will be passed to     Walker_Nav_Menu_Edit_Custom
     */
    add_filter( 'wp_setup_nav_menu_item','neo_inm_nav_item' );
    function neo_inm_nav_item($menu_item) {
        $menu_item->custom = get_post_meta( $menu_item->ID, '_menu_item_custom', true );
        return $menu_item;
    }

    add_filter( 'wp_edit_nav_menu_walker', 'neo_inm_nav_edit_walker', 10, 2 );
    function neo_inm_nav_edit_walker($walker,$menu_id) {
        return 'Neo_Inm_Edit_Walker';
    }

?>