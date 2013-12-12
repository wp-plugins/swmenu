<?php
defined('ABSPATH') or die("Cannot access pages directly.");

defined("DS") or define("DS", DIRECTORY_SEPARATOR);

class sw_swmenu_widget extends WP_Widget {

    function sw_swmenu_widget() {

        $name = 'swmenu';
        $desc = 'Dynamic and Responsive CSS3 Menus for Wordpess';
        $id_base = 'sw_swmenu_widget';
        $css_class = '';


        $widget_ops = array(
            'classname' => $css_class,
            'description' => __($desc, 'sw-menu'),
        );
        parent::WP_Widget('nav_menu', __('Custom Menu'), $widget_ops);

        $this->WP_Widget($id_base, __($name, 'swmenu'), $widget_ops);


        //	add_action( 'wp_head', array(&$this, 'styles'), 10, 1 );	
        //	add_action( 'wp_footer', array(&$this, 'footer'), 10, 1 );	

        $this->defaults = array(
            'title' => '',
            'overlay_hack' => false,
        );
    }

    function widget($args, $instance) {
        extract($args);
        // Get menu	
        $widget_options = wp_parse_args($instance, $this->defaults);
        extract($widget_options, EXTR_SKIP);

        $nav_menu = wp_get_nav_menu_object($instance['nav_menu']);
        $menu_items = wp_get_nav_menu_items($nav_menu->term_id);

        if (!$nav_menu)
            return;

        $instance['title'] = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

        echo $args['before_widget'];

        if (!empty($instance['title']))
            echo $args['before_title'] . $instance['title'] . $args['after_title'];

        //print_r($menu_items);
        $final_menu = array();
        foreach ($menu_items as $swmenu) {
            $final_menu[] = array(
                "TITLE" => $swmenu->title,
                "URL" => $swmenu->url,
                "ID" => $swmenu->ID,
                "PARENT" => $swmenu->menu_item_parent,
                "ORDER" => $swmenu->menu_order
            );
        }

        $final_menu = chainswmenu('ID', 'PARENT', 'ORDER', $final_menu, 0, 25);


        $swmenupro=sw_swmenu::get_style();
        $swmenupro['active_menu']= sw_getactiveswmenu($final_menu);
        $swmenupro['overlay_hack']=$instance['overlay_hack'];
        $menu = css3Menuswmenu($final_menu, $swmenupro);
        

        echo $menu;




        echo $args['after_widget'];
    }

    /**
     * Get the View file
     * 
     * Isolates the view file from the other variables and loads the view file,
     * giving it the three parameters that are needed. This method does not
     * need to be changed.
     *
     * @param array $widget
     * @param array $params
     * @param array $sidebar
     */
    function getViewFile($widget, $params, $sidebar) {
        require $this->widget['view'];
    }

    /**
     * Administration Form
     * 
     * This method is called from within the wp-admin/widgets area when this
     * widget is placed into a sidebar. The resulting is a widget options form
     * that allows the administration to modify how the widget operates.
     * 
     * You do not need to adjust this method what-so-ever, it will parse the array
     * parameters given to it from the protected widget property of this class.
     *
     * @param array $instance
     * @return boolean
     */
    function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        $nav_menu = isset($instance['nav_menu']) ? $instance['nav_menu'] : '';
        $rowItems = isset($instance['rowItems']) ? $instance['rowItems'] : '';

        $overlay_hack = isset( $instance['overlay_hack'] ) ? (bool) $instance['overlay_hack'] : false;
        
         $widget_options = wp_parse_args($instance, $this->defaults);
        extract($widget_options, EXTR_SKIP);

        // Get menus
        $menus = get_terms('nav_menu', array('hide_empty' => false));

        // If no menus exists, direct the user to go and create some.
        if (!$menus) {
            echo '<p>' . sprintf(__('No menus have been created yet. <a href="%s">Create some</a>.'), admin_url('nav-menus.php')) . '</p>';
            return;
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
            <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('nav_menu'); ?>"><?php _e('Select Menu:'); ?></label>
            <select id="<?php echo $this->get_field_id('nav_menu'); ?>" name="<?php echo $this->get_field_name('nav_menu'); ?>">
                <?php
                foreach ($menus as $menu) {
                    $selected = $nav_menu == $menu->term_id ? ' selected="selected"' : '';
                    echo '<option' . $selected . ' value="' . $menu->term_id . '">' . $menu->name . '</option>';
                }
                ?>
            </select>
        </p>
        <p>
            <p><input class="checkbox" type="checkbox" <?php checked( $overlay_hack ); ?> id="<?php echo $this->get_field_id( 'overlay_hack' ); ?>" name="<?php echo $this->get_field_name( 'overlay_hack' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'overlay_hack' ); ?>">Overlay Hack?</label></p>
            
        </p>
        <?php
        return true;
    }

    /**
     * Update the Administrative parameters
     * 
     * This function will merge any posted paramters with that of the saved
     * parameters. This ensures that the widget options never get lost. This
     * method does not need to be changed.
     *
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    function update($new_instance, $old_instance) {
        $instance['title'] = strip_tags(stripslashes($new_instance['title']));
        $instance['nav_menu'] = (int) $new_instance['nav_menu'];
        $instance['overlay_hack'] = isset( $new_instance['overlay_hack'] ) ? (bool) $new_instance['overlay_hack'] : false;	
        return $instance;
    }

}

function islastswmenu($array, $id) {
    $this_level = $array[$id]['indent'];
    $last = 0;
    $i = $id + 1;
    $do = 1;
    while ($do) {
        if (@$array[$i]['indent'] < $this_level || $i == count($array)) {
            $last = 1;
        }
        if (@$array[$i]['indent'] <= $this_level) {
            $do = 0;
        }
        $i++;
    }
    return $last;
}

function sw_getactiveswmenu($ordered) {
    $current_url = current_page_urlswmenu();
    //echo $current_url;
    foreach ($ordered as $item) {
        //echo $item['TITLE'];
        if ($current_url == $item['URL']) {
            $current_itemid = $item['ID'];
        }
    }
    //echo $current_itemid;
    if (is_home()) {
        $id = $ordered[0]['ID'];
    } else {

        $indent = 0;
        $parent_value = $current_itemid;
        $parent = 1;
        $id = 0;
        while ($parent) {
            for ($i = 0; $i < count($ordered); $i++) {
                $row = $ordered[$i];
                if ($row['ID'] == $parent_value) {
                    $parent_value = $row['PARENT'];
                    $indent = $row['indent'];
                    $id = $row['ID'];
                }
            }
            if (!$indent) {
                $parent = 0;
            }
        }
    }
    // echo $id;
    return ($id);
}

function current_page_urlswmenu() {
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"])) {
        if ($_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}



function css3Menuswmenu($ordered,$swmenupro){
        $name = "";
    $counter = 0;
    $doMenu = 1;
    $number = count($ordered);
    $topcount = 0;
  $live_site=sw_swmenu::get_plugin_directory();
    $str  = "<div class='".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."-wrap' align=\"" . $swmenupro['position'] . "\" >\n";
    $str .= "<ul class=\"".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."\"  > \n";
    
    while ($doMenu) {
        if ($ordered[$counter]['indent'] == 0) {
            $ordered[$counter]['URL'] = str_replace('&', '&amp;', $ordered[$counter]['URL']);
            $name=$ordered[$counter]['IMAGE']?"<img src='".$live_site."/".$ordered[$counter]['IMAGE']."' alt='".$ordered[$counter]['TITLE']."' hspace='3'/>":"";
            $name .= $ordered[$counter]['TITLE'];
           if (($counter + 1 != $number) && ($ordered[$counter + 1]['indent'] > $ordered[$counter]['indent']) && ($swmenupro['top_sub_indicator']||$swmenupro['top_sub_hover_indicator'])) {
               $indicator = "\n<div class='sw_indicator' style='float:" . $swmenupro['top_sub_indicator_align'] . "'>\n";
              if ($swmenupro['top_sub_indicator']) { 
                $indicator .= "<img src='" . $live_site . "/images/" . $swmenupro['top_sub_indicator'] . "'  style='position:relative;left:" . $swmenupro['top_sub_indicator_left'] . "px;top:" . $swmenupro['top_sub_indicator_top'] . "px;float:left;' alt=''  border='0' ".($swmenupro['top_sub_hover_indicator']?"class='seq1'":"")." />\n";
               }
               if ($swmenupro['top_sub_hover_indicator']) { 
                $indicator .="<img src='" . $live_site . "/images/" . $swmenupro['top_sub_hover_indicator'] . "'  style='position:relative;left:" . $swmenupro['top_sub_indicator_left'] . "px;top:" . $swmenupro['top_sub_indicator_top'] . "px;float:left;' alt=''  border='0' class='seq2' />\n";
               } 
               if ($swmenupro['top_sub_indicator']||$swmenupro['top_sub_hover_indicator']) { 
               $indicator.="</div>";
               }
              $name=$indicator.$name;
                
           }
        
           if (($ordered[$counter]['ID'] == $swmenupro['active_menu'])) {
               $str .= "<li class='sw_active'> \n";
            }else{
                $str .= "<li> \n";
            }
             if (($counter + 1 != $number) && ($ordered[$counter + 1]['indent'] > $ordered[$counter]['indent']) ) {
                $parent=1;
                $p_id=($swmenupro['menu_id']?$swmenupro['menu_id'].$ordered[$counter]['ID']:'swmenu'.$ordered[$counter]['ID']);
                 }else{
                  $parent=0;   
                 }
                $topcount++;
           
                switch ($ordered[$counter]['TARGET']) {
                    case 1:
                      $str .= "<a".($parent?" class='sw_parent'":"")." id='".($swmenupro['menu_id']?$swmenupro['menu_id'].$ordered[$counter]['ID']:'swmenu'.$ordered[$counter]['ID'])."' href='" . $ordered[$counter]['URL'] . "' target='_blank'>" . $name . "\n</a>\n";
                     break;
                    case 2:
                      $str .= "<a".($parent?" class='sw_parent'":"")." id='".($swmenupro['menu_id']?$swmenupro['menu_id'].$ordered[$counter]['ID']:'swmenu'.$ordered[$counter]['ID'])."' href=\"#\" onclick=\"javascript: window.open('" . $ordered[$counter]['URL'] . "', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550'); return false\" >" . $name . "\n</a>\n";
                    break;
                    default:
                      $str .= "<a".($parent?" class='sw_parent'":"")." id='".($swmenupro['menu_id']?$swmenupro['menu_id'].$ordered[$counter]['ID']:'swmenu'.$ordered[$counter]['ID'])."' href='" . $ordered[$counter]['URL'] . "'>" . $name . "\n</a>\n";
                    break;
                  }
                
            
            if ($counter + 1 == $number) {
                $doSubMenu = 0;
                $doMenu = 0;
            } elseif ($ordered[$counter + 1]['indent'] == 0) {
                $doSubMenu = 0;
            } else {
                $doSubMenu = 1;
            }
            $counter++;
            
            while ($doSubMenu) {
                if ($ordered[$counter]['indent'] != 0) {
                    if ($ordered[$counter]['indent'] > $ordered[$counter - 1]['indent']) {
                        $str .= "<ul id='".$p_id."-sub"."'>\n";
                    }
                    $ordered[$counter]['URL'] = str_replace('&', '&amp;', $ordered[$counter]['URL']);
                    $name = $ordered[$counter]['TITLE'];
                    if (($counter + 1 == $number) || ($ordered[$counter + 1]['indent'] == 0)) {
                        $doSubMenu = 0;
                    }
                   $classname = "";
                    
                    if (($counter + 1 != $number) && ($ordered[$counter + 1]['indent'] > $ordered[$counter]['indent']) && ($swmenupro['sub_sub_indicator']||$swmenupro['sub_sub_hover_indicator'])) {
                      $indicator = "\n<div class='sw_indicator' style='float:" . $swmenupro['sub_sub_indicator_align'] . "'>\n";
                      if ($swmenupro['sub_sub_indicator']) { 
                         $indicator .= "<img src='" . $live_site . "/images/" . $swmenupro['sub_sub_indicator'] . "'  style='position:relative;left:" . $swmenupro['sub_sub_indicator_left'] . "px;top:" . $swmenupro['sub_sub_indicator_top'] . "px;float:left;' alt=''  border='0' ".($swmenupro['sub_sub_hover_indicator']?"class='seq1'":"")." />\n";
                      }
                      if ($swmenupro['sub_sub_hover_indicator']) { 
                          $indicator .="<img src='" . $live_site . "/images/" . $swmenupro['sub_sub_hover_indicator'] . "'  style='position:relative;left:" . $swmenupro['sub_sub_indicator_left'] . "px;top:" . $swmenupro['sub_sub_indicator_top'] . "px;float:left;' alt=''  border='0' class='seq2' />\n";
                      } 
                      if ($swmenupro['sub_sub_indicator']||$swmenupro['sub_sub_hover_indicator']) { 
                      $indicator.="</div>";
                      }
                   $name=$indicator.$name;
                  }
                     
                 if (($counter + 1 != $number) && ($ordered[$counter + 1]['indent'] > $ordered[$counter]['indent']) ) {
                   $parent=1;
                    $p_id=($swmenupro['menu_id']?$swmenupro['menu_id'].$ordered[$counter]['ID']:'swmenu'.$ordered[$counter]['ID']);
                 }else{
                    $parent=0;   
                 }
                     
                       if ($current_itemid == $ordered[$counter]['ID']) {
                            $classname .= 'sw_active';
                        }
                  // echo $ordered[$counter]['TARGET'];
                    $str .= "<li".($classname?" class='".$classname."'":"").">\n";
                    switch ($ordered[$counter]['TARGET']) {
                    case 1:
                      $str .= "<a".($parent?" class='sw_parent'":"")." id='".($swmenupro['menu_id']?$swmenupro['menu_id'].$ordered[$counter]['ID']:'swmenu'.$ordered[$counter]['ID'])."' href='" . $ordered[$counter]['URL'] . "' target='_blank'>" . $name . "\n</a>\n";
                     break;
                    case 2:
                      $str .= "<a".($parent?" class='sw_parent'":"")." id='".($swmenupro['menu_id']?$swmenupro['menu_id'].$ordered[$counter]['ID']:'swmenu'.$ordered[$counter]['ID'])."' href=\"#\" onclick=\"javascript: window.open('" . $ordered[$counter]['URL'] . "', '', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=550'); return false\" >" . $name . "\n</a>\n";
                    break;
                    default:
                      $str .= "<a".($parent?" class='sw_parent'":"")." id='".($swmenupro['menu_id']?$swmenupro['menu_id'].$ordered[$counter]['ID']:'swmenu'.$ordered[$counter]['ID'])."' href='" . $ordered[$counter]['URL'] . "'>" . $name . "\n</a>\n";
                    break;
                  }
                    if (($counter + 1 == $number) || ($ordered[$counter + 1]['indent'] < $ordered[$counter]['indent'])) {
                        $str .= str_repeat("</li>\n</ul>\n", (($ordered[$counter]['indent']) - (@$ordered[$counter + 1]['indent'])));
                        if ((@$ordered[$counter + 1]['indent'] > 0)) {
                            $str .= "</li> \n";
                        }
                    } else if (($ordered[$counter + 1]['indent'] <= $ordered[$counter]['indent'])) {
                        $str .= "</li> \n";
                    }
                    $counter++;
                }
            }
            $str .= "</li> \n";
        }
       
        if ($counter == ($number)) {
            $doMenu = 0;
        }
    }
    $str .= "</ul>\n</div> \n";

  
$script="";
   
     if (($swmenupro['c_corner_style'] != 'none') && ($swmenupro['c_corner_style'])&& ($swmenupro['c_corner_style'] != 'curvycorner')){
         $script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."').corner('" . $swmenupro['c_corner_style'] . " " . (@$swmenupro['ctl_corner'] ? 'tl' : '') . " " . (@$swmenupro['ctr_corner'] ? 'tr' : '') . " " . (@$swmenupro['cbl_corner'] ? 'bl' : '') . " " . (@$swmenupro['cbr_corner'] ? 'br' : '') . " " . ($swmenupro['c_corner_size']) . "px');\n";
     }
     if (($swmenupro['t_corner_style'] != 'none') && ($swmenupro['t_corner_style'])&& ($swmenupro['t_corner_style'] != 'curvycorner')){
        $script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')." li a').corner('" . $swmenupro['t_corner_style'] . " " . (@$swmenupro['ttl_corner'] ? 'tl' : '') . " " . (@$swmenupro['ttr_corner'] ? 'tr' : '') . " " . (@$swmenupro['tbl_corner'] ? 'bl' : '') . " " . (@$swmenupro['tbr_corner'] ? 'br' : '') . " " . ($swmenupro['t_corner_size']) . "px');\n";
     }
     if (($swmenupro['s_corner_style'] != 'none') && ($swmenupro['s_corner_style'])&& ($swmenupro['s_corner_style'] != 'curvycorner')){
        $script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')." ul ').corner('" . $swmenupro['s_corner_style'] . " " . (@$swmenupro['stl_corner'] ? 'tl' : '') . " " . (@$swmenupro['str_corner'] ? 'tr' : '') . " " . (@$swmenupro['sbl_corner'] ? 'bl' : '') . " " . (@$swmenupro['sbr_corner'] ? 'br' : '') . " " . ($swmenupro['s_corner_size']) . "px');\n";
     }
     if (($swmenupro['si_corner_style'] != 'none') && ($swmenupro['si_corner_style'])&& ($swmenupro['si_corner_style'] != 'curvycorner')){
        if($swmenupro['sfl_corner']=="all"){
           $script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')." ul a').corner('" . $swmenupro['si_corner_style'] . " " . (@$swmenupro['sitl_corner'] ? 'tl' : '') . " " . (@$swmenupro['sitr_corner'] ? 'tr' : '') . " " . (@$swmenupro['sibl_corner'] ? 'bl' : '') . " " . (@$swmenupro['sibr_corner'] ? 'br' : '') . " " . ($swmenupro['si_corner_size']) . "px');\n";
         } else{
           $script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')." ul .sw_first a').corner('" . $swmenupro['si_corner_style'] . " " . (@$swmenupro['sitl_corner'] ? 'tl' : '') . " " . (@$swmenupro['sitr_corner'] ? 'tr' : '') . " " . ($swmenupro['si_corner_size']) . "px');\n";
           $script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')." ul .sw_last a').corner('" . $swmenupro['si_corner_style'] . " " . (@$swmenupro['sibl_corner'] ? 'bl' : '') . " " . (@$swmenupro['sibr_corner'] ? 'br' : '') . " " . ($swmenupro['si_corner_size']) . "px');\n";
         }
     }
    
if(@$swmenupro['responsive']==('flat'||'accordion')){ 
 $indicator = "<div class='sw_icon' style='float:" . $swmenupro['icon_align'] . "'><img src='" . $live_site . "/images/" . $swmenupro['closed_icon'] . "'  style='position:relative;left:" . $swmenupro['icon_left'] . "px;top:" . $swmenupro['icon_top'] . "px;float:left;' alt=''  border='0' class='seq3' />";
 $indicator .="<img src='" . $live_site . "/images/" . $swmenupro['open_icon'] . "'  style='position:relative;left:" . $swmenupro['icon_left'] . "px;top:" . $swmenupro['icon_top'] . "px;float:left;' alt=''  border='0' class='seq4' /></div>";
 
if($swmenupro['open_close_button']){ 
$script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."-wrap').prepend(\"<div class='".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."-trigger'>".$indicator . $swmenupro['open_close_text'] . "</div>\");\n";		
$script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."-trigger').click( function(){\n";
$script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')." ' ).slideToggle(200,function(){\n";
$script .= "if(jQuery(this).css('display')=='block'){jQuery(\".".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."-trigger\").addClass('sw_opened')}else{jQuery(\".".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."-trigger\").removeClass('sw_opened')}\n";
$script .= "});\n";
$script .= "});\n";
}
 if($swmenupro['responsive']=='accordion'){                       
$script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')." .sw_parent').append( \"".$indicator."\");\n";
$script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')." .sw_parent').click( function(){\n";
$script .= "if(jQuery(\"window\").width() <= ".$swmenupro['responsive_width']."){\n";
if($swmenupro['disable_parent']){ 
$script .= "jQuery('#'+this.id).attr( 'href','javascript:void(0)');\n";
}
$script .= "jQuery('#'+this.id).next().slideToggle(200,function(){\n";
$script .= "if(jQuery('#'+this.id).css('display')=='block'){jQuery('#'+this.id).prev().addClass('sw_opened')}else{jQuery('#'+this.id).prev().removeClass('sw_opened')}\n";
$script .= "});\n";
$script .= "//alert(p_height);\n}\n});\n";
}
}

if($swmenupro['overlay_hack']){ 
$script .= "jQuery('.".($swmenupro['menu_id']?$swmenupro['menu_id']:'swmenu')."-wrap').parents().css(\"overflow\",\"visible\");\n";		

}



if($script){  
   $str .= "<script type=\"text/javascript\">\n";
   $str .= "<!--\n";
   $str.=$script;
   $str .= "//-->\n";
   $str .= "</script>\n";
}
 

return $str;



    
    
    
    
    
    
    
    
    
    }
function chainswmenu($primary_field, $parent_field, $sort_field, $rows, $root_id = 0, $maxlevel = 25) {
    $c = new chainswmenu($primary_field, $parent_field, $sort_field, $rows, $root_id, $maxlevel);
    return $c->chainmenu_table;
}

class chainswmenu {

    var $table;
    var $rows;
    var $chainmenu_table;
    var $primary_field;
    var $parent_field;
    var $sort_field;

    function chainswmenu($primary_field, $parent_field, $sort_field, $rows, $root_id, $maxlevel) {
        $this->rows = $rows;
        $this->primary_field = $primary_field;
        $this->parent_field = $parent_field;
        $this->sort_field = $sort_field;
        $this->buildchain($root_id, $maxlevel);
    }

    function buildChain($rootcatid, $maxlevel) {
        foreach ($this->rows as $row) {
            $this->table[$row[$this->parent_field]][$row[$this->primary_field]] = $row;
        }
        $this->makeBranch($rootcatid, 0, $maxlevel);
    }

    function makeBranch($parent_id, $level, $maxlevel) {
        $rows = $this->table[$parent_id];
        $key_array1 = array_keys($rows);
        $key_array_size1 = sizeOf($key_array1);
        foreach ($rows as $key => $value) {
            $rows[$key]['key'] = $this->sort_field;
        }
        usort($rows, 'chainmenuCMPswmenu');
        $row_array = array_values($rows);
        $row_array_size = sizeOf($row_array);
        $i = 0;
        foreach ($rows as $item) {
            $item['ORDER'] = ($i + 1);
            $item['indent'] = $level;
            $i++;
            $this->chainmenu_table[] = $item;
            if ((isset($this->table[$item[$this->primary_field]])) && (($maxlevel > $level + 1) || ($maxlevel == 0))) {
                $this->makeBranch($item[$this->primary_field], $level + 1, $maxlevel);
            }
        }
    }

}

function chainmenuCMPswmenu($a, $b) {
    if ($a[$a['key']] == $b[$b['key']]) {
        return 0;
    }
    return ($a[$a['key']] < $b[$b['key']]) ? -1 : 1;
}
?>