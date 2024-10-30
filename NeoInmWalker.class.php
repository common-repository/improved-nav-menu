<?php

/**
 * @package WordPress
 * @subpackage neOceane Theme
 */
class Neo_Inm_Walker extends Walker_Nav_Menu {

    function walk($elements, $max_depth, $args) {
        
        if(isset($args->nav_link_globals) && !empty($args->nav_link_globals)) {
            $GLOBALS[$args->nav_link_globals] = array();
            $GLOBALS['current_walk_name'] = $args->nav_link_globals;
        }
        
        // if isset parent_id only return submenu für this item
        if (isset($args->parent_id) && is_numeric($args->parent_id)) {
            $children = $this->get_children_for($elements, $this->get_page_item_from_object_id($elements, $args->parent_id));
            if (is_array($children) && count($children) > 0) {
                $elements = $children;
            } else {
                add_filter('wp_nav_menu', array(&$this, 'return_empty_nav_string'), 10, 2);
                return '';
            }
        }
        $this->add_first_last_classes($elements);
        return parent::walk($elements, $max_depth, $args);
        unset($GLOBALS['current_walk_name']);
    }

    function get_children_for($elements, $id) {
        if ($id == 0)
            return false;

        $id_field = $this->db_fields['id'];
        $parent_field = $this->db_fields['parent'];

        $children = array();

        foreach ($elements as $element) {
            if ($id == $element->$parent_field) {
                $temp_children = array();
                $children[] = $element;

                if (in_array('current-menu-item', $element->classes) || in_array('current-menu-parent', $element->classes) || in_array('current-menu-ancestor', $element->classes))
                    $temp_children = $this->get_children_for($elements, $element->$id_field);
                if (!empty($temp_children))
                    $children = array_merge($children, $temp_children);
            }
        }

        return $children;
    }

    function get_page_item_from_object_id($elements, $obj_id) {
        $id_field = $this->db_fields['id'];
        foreach ($elements as $element) {
            if ($element->object_id == $obj_id)
                return $element->$id_field;
        }
        return 0;
    }

    function add_first_last_classes($elements, $parent_key = 0) {
        $parent_field = $this->db_fields['parent'];

        $parents = array();
        $children = array();

        // every level gets first / last classes
        foreach ($elements as $element) {
            if ($parent_key == $element->$parent_field)
                $parents[] = $element;
            else
                $children[$element->$parent_field][] = $element;
        }
        $x = 0;
        $x_max = count($parents) - 1;
        // set first / last for parents
        foreach ($parents as $parent) {
            if ($x == 0 && is_array($parent->classes))
                array_push($parent->classes, 'menu-item-first');
            if ($x == $x_max && is_array($parent->classes))
                array_push($parent->classes, 'menu-item-last');
            $x++;
        }
        // set first / last recursive for all children
        foreach ($children as $parent => $child) {
            $this->add_first_last_classes($child, $parent);
        }
    }

    function return_empty_nav_string($nav_menu, $args) {
        return '';
    }

    function start_el(&$output, $item, $depth, $args) {
        global $wp_query;
        $indent = ( $depth ) ? str_repeat("\t", $depth) : '';

        $class_names = $value = '';

        $classes = empty($item->classes) ? array() : (array) $item->classes;
		
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item));
        $class_names = ' class="' . esc_attr($class_names) . '"';

        $output .= $indent . '<li id="menu-item-' . $item->ID . '"' . $value . $class_names . '>';

		$this->getChangedUrl($item);
		
        $attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
        $attributes .=!empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
        $attributes .=!empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
		$attributes .=!empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
        $attributes .=!empty($args->link_class) ? ' class="' . esc_attr($args->link_class) . '"' : '';

        $prepend = '';
        $append = '';
        $description = !empty($item->description) ? '<span>' . esc_attr($item->description) . '</span>' : '';

        if ($depth != 0) {
            $description = $append = $prepend = "";
        }
		
        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $args->link_before . $prepend . apply_filters('the_title', $item->title, $item->ID) . $append;		
        $item_output .= $description . $args->link_after;
        $item_output .= '</a>';
        $item_output .= $args->after;

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
		
    }

    function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {

            if ( !$element )
                    return;
            
            if(isset($GLOBALS['current_walk_name']) && !empty($GLOBALS['current_walk_name'])) {
                // Test  si l'élément est le courant de la page
                if ($element->current) {
                    $GLOBALS[$GLOBALS['current_walk_name']]['element_courant_depth'] = $depth;
                    $GLOBALS[$GLOBALS['current_walk_name']]['element_precedent'] = isset($GLOBALS[$GLOBALS['current_walk_name']]['precedents'][$depth]) ? $GLOBALS[$GLOBALS['current_walk_name']]['precedents'][$depth] : null;
                } else if (!isset($GLOBALS[$GLOBALS['current_walk_name']]['element_courant_depth'])) {
                    $GLOBALS[$GLOBALS['current_walk_name']]['precedents'][$depth] = $element;
                    for ($i = $depth + 1; $i <= $max_depth; $i++) {
                        unset($GLOBALS[$GLOBALS['current_walk_name']]['precedents'][$i]);
                    }
                } else if (!isset($GLOBALS[$GLOBALS['current_walk_name']]['element_suivant'])) {
                    if ($depth == $GLOBALS[$GLOBALS['current_walk_name']]['element_courant_depth']) {
                        $GLOBALS[$GLOBALS['current_walk_name']]['element_suivant'] = $element;
                    } else if ($depth < $args['mesparams']['element_courant_depth']) {
                        $GLOBALS[$GLOBALS['current_walk_name']]['element_suivant'] = '';
                    }
                }
            }
            // TODO tester le custom field de l'element
			
            
            parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }
		
	/**
	*changement de l'url si item contient élément un élement
	* 
     */
	function getChangedUrl(&$item){
		if(empty($item->custom))
			return ;
		else
		{
			$cust=explode (':',$item->custom);
			$url;
			$mode="ch";
			$test= $cust[1][0];
			//le char est-il un chiffre ? si oui on modifie via une ID
			if(is_numeric($test))
				$mode="id";
			//mode chaine	
			if($mode=='ch'){
				if($cust[0]=='category')
					if(get_term_by('name',$cust[1], 'category' ))
						$url= get_term_link( get_term_by('name',$cust[1], 'category' ));	
				else if($cust[0]=='lien')
                                 $url =$cust[1];
				else if($cust[0]!='page')
					if(get_term_by('name', $cust[1],$cust[0] ))
						$url = get_term_link( get_term_by('name', $cust[1],$cust[0] ));		
				else
					if(is_page(get_page_by_title($cust[1])->ID))
						$url = get_permalink( get_page_by_title($cust[1]));
					
			
			}
			//mode ID
			else {
                            $post_id = intval($cust[1]);
                            if ($cust[0] == 'category') {
                                if (is_category($post_id)) {
                                    $url = get_category_link($post_id);
                                }
                            } else if ($cust[0] != 'page') {
                                if (get_term_by('id', $post_id, $cust[0])) {
                                    $url = get_term_link(get_term_by('id', $post_id, $cust[0]));
                                }
                            } else {
                                $url = get_permalink($post_id);
                            }
                        }
			//si aucune URL n'a été obtenue, on ne change rien 
			if(!empty($url))
					$item->url = $url;
			
		}
    }
}
