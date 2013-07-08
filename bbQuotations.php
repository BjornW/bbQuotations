<?php
/*******************************************************************************
Plugin Name: bbQuotations
Plugin URI: http://www.burobjorn.nl
Description: bbQuotations adds Quotes as a custom post type to WordPress
Author: Bjorn Wijers <burobjorn at burobjorn dot nl> 
Version: 1.1
Author URI: http://www.burobjorn.nl
*******************************************************************************/   
   
/*  Copyright 2011-2013
  

bbQuotations is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

bbQuotations is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if ( ! class_exists('bbQuotations')) {
  class bbQuotations {
  
    /**
     * @var string The options string name for this plugin
    */
    var $options_name = 'bbquotations_va_options';
    
    /**
     * @var string $localization_domain Domain used for localization
    */
    var $localization_domain = "bbquotations";
    
    /**
     * @var string $plugin_url The path to this plugin
    */ 
    var $plugin_url = '';
    
    /**
     * @var string $plugin_path The path to this plugin
    */
    var $plugin_path = '';
        
    /**
     * @var array $options Stores the options for this plugin
    */
    var $options = array();

    /**add_action( 'admin_print_footer_scripts', 'remove_save_button' );
function remove_save_button()
{   
?>
<script>
jQuery(document).ready(function($){$('#save-post').remove();});
</script><?php
}
     * @var string $custom_post_type_name
     */
    var $custom_post_type_name = 'bbquote';
    
    /**
     * PHP 4 Compatible Constructor
    */
    function bbQuotations(){ $this->__construct(); }
    
    /**
     * PHP 5 Constructor
    */        
    function __construct( $activate = true )
    {
      // language setup
      $locale = get_locale();
      $mo     = dirname(__FILE__) . "/languages/" . $this->localization_domain . "-".$locale.".mo";
      
      load_textdomain($this->localization_domain, $mo);

      // 'constants' setup
      $this->plugin_url  = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
      $this->plugin_path = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';

      if($activate) {
        $this->activate();
      }
    }

    /**
     * Sets the options, connects WordPress hooks
     * and install the database 
     * 
     * @access public
     * @return void
     */
    function activate() 
    {
      // prepare the options
      $this->get_options();
      // set the WordPress hooks
      $this->wp_hooks();
    }
    
    /**
     * Adds actions to callbacks
     * 
     * @access public
     * @return void
     */
    function wp_hooks() 
    {
      // Wordpress actions & filters        
      add_action( 'admin_menu', array(&$this,'admin_menu_link') );
      add_action( 'admin_head', array(&$this, 'remove_mediabuttons') );
      add_action( 'init', array(&$this, 'add_custom_post_type') );
      add_action( 'admin_init', array(&$this, 'add_metaboxes') );
      add_action( 'save_post', array(&$this, 'save_source_metabox'), 10, 2 );
      add_action( 'manage_posts_custom_column', array(&$this, 'add_custom_columns') ); // sets the row value
      
      $filter = 'manage_edit-' . $this->custom_post_type_name . '_columns';
      add_filter( $filter, array(&$this, 'add_header_columns') );
      add_filter( 'body_class', array(&$this, 'body_classes') );
      add_action('wp_print_styles', array(&$this, 'add_css') );

      add_filter( 'enter_title_here', array(&$this, 'change_title_text'), 10, 1 );
      add_filter( 'post_row_actions', array(&$this, 'remove_row_actions'), 10, 1 );
      add_filter( 'post_updated_messages', array(&$this, 'bbquotations_updated_messages') );

      add_action( 'admin_print_footer_scripts', array(&$this, 'remove_preview_button') );

      // add shortcode
      add_shortcode( 'bbquote', array(&$this, 'shortcode') );
    }


    function remove_preview_button()
    {   
      if( get_post_type() === 'bbquote' ) {
        $js = '<script>';
        $js .= "jQuery(document).ready(function($){ $('#post-preview').remove();});";
        $js .= '</script>';
        echo $js;
      } 
    }


    function add_css() 
    {
      // register the default style and enqueu it if the option in the settings allows it
      if( $this->options['use-css-file'] ) {
        wp_register_style( 'bbquotations-style' , $this->plugin_url . 'bbquotations-style.css');
        wp_enqueue_style( 'bbquotations-style' );
      } 
    }

    function bbquotations_updated_messages( $messages ) 
    {

      global $post_ID;
      
      $messages['bbquote'] = array(
        0 => '', // Unused. Messages start at index 1.
        1 => sprintf( __("Quote updated. Use this shortcode in your content to display it: [bbquote id='%d']", $this->localization_domain), $post_ID ),
        2 => __('Custom field updated.'),
        3 => __('Custom field deleted.'),
        4 => sprintf( __("Quote updated. Use this shortcode in your content to display it: [bbquote id='%d']", $this->localization_domain), $post_ID ),
        5 => '', /* revision msg, not applicable for this plugin */
        6 => sprintf( __("Quote published. Use this shortcode in your content to display it: [bbquote id='%d']", $this->localization_domain), $post_ID ),
        7 => sprintf( __("Quote saved. Use this shortcode in your content to display it: [bbquote id='%d']", $this->localization_domain), $post_ID ),
        8 => sprintf( __('Quote submitted, but before you can use this quote you\'ll need to publish it first.', $this->localization_domain), $post_ID ),
        9 => '', /* schedule msg, not applicable for this plugin */
        10 => sprintf( __('Quote draft updated, but before you can use this quote you\'ll need to publish it first.', $this->localization_domain), $post_ID ),
      );

    return $messages;
}


    function remove_row_actions( $actions )
    {
      if( get_post_type() === 'bbquote' ) {
        unset( $actions['view'] );
      }
      return $actions;
    }


    function change_title_text( $txt ) 
    {
      if( get_post_type() === 'bbquote' ) {
        $txt = __('Add quotation source here', $this->localization_domain);
      } 
      return $txt;
    }


    /**
     * Retrieves the plugin options from the database.
     * @return array
    */
    function get_options() 
    {
      // don't forget to set up the default options
      if ( ! $the_options = get_option( $this->options_name) ) {
        $the_options = array(
          'bbquotations-slug' =>'quotes',
          'use-css-file'      => false
        );
        update_option($this->options_name, $the_options);
      }
      $this->options = $the_options;
    }

    /**
     * Updates the plugin options in the database 
     * 
     * @access public
     * @return void
     */
    function save_admin_options()
    {
      return update_option($this->options_name, $this->options);
    }
    
    /**
     * @desc Adds the options subpanel
    */
    function admin_menu_link() 
    {
      // If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
      // reflect the page filename (ie - options-general.php) of the page your plugin is under!
      add_options_page('bbQuotations', 'bbQuotations', 'edit_plugins', basename(__FILE__), array(&$this,'admin_options_page'));
      add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
    }
    
    /**
     * @desc Adds the Settings link to the plugin activate/deactivate page
    */
    function filter_plugin_actions($links, $file) 
    {
      // If your plugin is under a different top-level menu than 
      // Settiongs (IE - you changed the function above to something other than add_options_page)
      // Then you're going to want to change options-general.php below to the name of your top-level page
      $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
      array_unshift( $links, $settings_link ); // before other links
      return $links;
    }
    
    /**
    * Adds settings/options page
    */
    function admin_options_page() 
    { 
      if( $_POST['bbquotations_save'] ) {
        if ( ! wp_verify_nonce($_POST['bbquotations_wpnonce'], 'bbquotations-update-options') ) { 
          die( __('Whoops! There was a problem with the data you posted. Please go back and try again.', $this->localization_domain) ) ; 
        }
        $this->options['bbquotations-slug'] = $_POST['bbquotations-slug'];                   
        $use_css = ( isset( $_POST['bbquotations_use_css'] ) && $_POST['bbquotations_use_css'] ) == 'true'  ? true : false;
        $this->options['use-css-file'] = $use_css;

        $this->save_admin_options();
          
        // flush permalinks in case the slug is changed 
        global $wp_rewrite;
        $wp_rewrite->flush_rules('false');
        $html = '<div class="updated"><p>' . __('Success! Your changes were sucessfully saved!', $this->localization_domain) . '</p></div>';
      }
      // build the options page  
      $html .= "<div class=\"wrap\">\n";
      $html .= __("<h2>bbQuotations settings</h2>", $this->localization_domain);
      
      $html .= "<form method=\"post\" id=\"bbquotations_options\">";
      $html .= wp_nonce_field('bbquotations-update-options', $name = 'bbquotations_wpnonce', $referer = true, $echo = false);
      $html .= "<table width=\"75%\" cellspacing=\"2\" cellpadding=\"5\" class=\"form-table\">\n"; 
      
      $html .= "<tr valign=\"top\">\n"; 
      $html .= "\t<th width=\"33%\" scope=\"row\">" .  __('Use supplied plugin css stylesheet:', $this->localization_domain) . "</th>\n"; 
      $html .= "\t<td>";
      $checked = ($this->options['use-css-file'] === true ) ? "checked='checked'" : ''; 
      $html .= "<input type='checkbox' name='bbquotations_use_css' value='true' $checked />"; 
      $html .= "</td>\n"; 
      $html .= "</tr>\n";

      // advanced      

      $html .= "<tr valign=\"top\">\n"; 
      $html .= "\t<th width=\"33%\" colspan=\"2\" scope=\"row\">" .  __('<strong>Advanced Options</strong>', $this->localization_domain) . "</th>\n"; 
      $html .= "</tr>\n";

      $html .= "<tr valign=\"top\">\n"; 
      $html .= "\t<td width=\"33%\" colspan=\"2\" scope=\"row\">" .  __("
        Only change the bbQuotations plugin slug if you know what you're doing. 
        In general you should not have to change (especially when you have been using this plugin already, 
        since the urls for your quotes will change!) the slug unless you have installed plugins that use 
        the events slug already. After changing the slug visit the Permalinks to make it active. 
        NB: It may only contain alphanumeric characters and is limted to 20 characters.") . "</td>\n"; 
      $html .= "</tr>\n";


      $html .= "<tr valign=\"top\">\n"; 
      $html .= "\t<th width=\"33%\" scope=\"row\">" .  __('bbQuotations slug:', $this->localization_domain) . "</th>\n"; 
      $html .= "\t<td>";
      $html .= "<input type='text' name='bbquotations-slug' value='{$this->options['bbquotations-slug']}' size='20' maxlength='20' />";  
      $html .= "</td>\n"; 
      $html .= "</tr>\n";

      $html .= "<tr>\n";
      $html .= "\t<th colspan=2><input type=\"submit\" name=\"bbquotations_save\" value=\"" . __('Save', $this->localization_domain) . "\"/></th>\n";
      $html .= "</tr>\n";
      $html .= "</table>\n";
      $html .= "</form>\n";
      // show the built page
      echo $html;  
    }

    
    
    /**
     * Adds a new post type named bbQuotations to 
     * WordPress 
     * 
     * @access public
     * @return void
     */
    function add_custom_post_type() 
    {
      // add custom type evenementen
      $labels = array(
        'name'          => _x('Quotes', 'post type general name', $this->localization_domain),
        'singular_name' => _x('Quote', 'post type singular name', $this->localization_domain),
        'add_new'       => _x('Add Quote', 'event', $this->localization_domain),
        'add_new_item'  => __('Add New Quote', $this->localization_domain),
        'edit_item'     => __('Edit Quote', $this->localization_domain),
        'new_item'      => __('New Quote', $this->localization_domain),
        'view_item'     => __('View Quote', $this->localization_domain),
        'search_items'  => __('Search Quotes', $this->localization_domain),
        'not_found'     => __('No Quotes found', $this->localization_domain),
        'not_found_in_trash' => __('No Quotes found in Trash', $this->localization_domain), 
        'parent_item_colon' => ''
      );
      $type_args = array(
        'labels'        => $labels,
        'description'   => __('bbQuotations offers a simple quotes custom post type. Useful for pull quotes or just random quotes on your site.',
        $this->localization_domain),
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true, 
        'query_var'           => true,
        'rewrite'             => array('slug' => $this->options['bbquotations-slug']),
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'menu_position'       => 5,
        'supports'            => array('title','editor','author')
      ); 
      register_post_type( $this->custom_post_type_name, $type_args);
    }


    /**
     * Adds a metabox to the 
     * bbQuotations post type
     *
     * @access public
     * @return void
     */
    function add_source_metabox() 
    {
      global $post;

      $source = get_post_meta($post->ID, 'bbquotations-source-url', true);

      // create the metabox
      $html = '';
      $html .= wp_nonce_field($action = 'bbquotations-source', $name = "bbquotations_wpnonce", $referer = true , $echo = false);
      $html .= "<table class='form-table'>\n"; 
      $html .= "<tr>\n";
      $html .= "<th style='width:20%'><label for='bbquotations-source-url'>" . __("Quote source url (optional). Empty will automatically link to author profile") . "</label></th>";
      $html .= "<td>";
      $html .= "<input type='text' name='bbquotations-source-url' id='bbquotations-source-url' value='$source' size='30' style='width:97%' />"; 
      $html .= '</td>';
      $html .= '</tr>';
      $html .= "</table>\n";
      echo $html;
    }

    function add_cheatsheet_metabox() 
    {
      $html .= "<div>"; 
      $html .= "<ul>";
      $html .="<li> -" . __('Use the title for the source of the quote.<br /> For example: Napoleon Boneparte, Battle of Waterloo 18 june 1815', 
        $this->localization_domain) . "</li>";

      $html .="<li> -" . __('Use the content for the quote itself.<br /> For example: Merde, I think I have lost the battle', 
        $this->localization_domain) . "</li>";

      $html .="<li> -" . __('Optionally use the <em>Quote source url</em> to add an url to the source. Leave empty to use the author profile.', 
        $this->localization_domain) . "</li>";
      $html .= "</ul>";
      $html .= "</div>";
      echo $html;
    }

    function shortcode( $atts) 
    {
      extract( shortcode_atts( array('id'  => null, 'random' => null), $atts ) );
      if( ! is_null($id) ) { 
         $quote = $this->get_quote($id); 
      } elseif( ! is_null($random) ) {
         $quote = $this->get_random_quote();
      } else {
        // do nothing
        return;
      }
        return $this->display_quote( $quote );
    }

    // @todo this could use more tweaking to make it perform better
    function get_random_quote() 
    {
      global $wpdb; 

      $q = "SELECT Id FROM $wpdb->posts 
        WHERE post_type='bbquote'
        AND post_status='publish'
        AND post_date <= NOW()";

      $ids = $wpdb->get_col($q);
      
      if( is_array( $ids ) ) {
        $nr_ids  = sizeof( $ids );
        // get a random number which can be used as an index key 
        // to retrieve an id from the ids array
        $rnd_key = mt_rand(0, ($nr_ids) -1 ) ;
        $id      = $ids[$rnd_key];
        return $this->get_quote( $id );
      }
      return false;
    }


    function get_quote( $id ) 
    {
      // if we don't have a numeric id, do nothing
      if( ! is_numeric($id) ) { return; }
        
      // not sure if this will work for custom post types as well...  
      $quote = get_post( $id );
      return $quote;
    }

    /** 
     * Expects a quote object similar to the database object 
     */
    function display_quote( $quote_object ) 
    {
      $quote = $quote_object;
      $html = '';
      if( is_object($quote) ) {
        $source_url = get_post_meta($id, 'bbquotations-source-url', true);
        // add the source url if available
        // or else link to the author of the quote 
        if( empty($source_url) ) {
          $url = get_author_posts_url($quote->post_author);
          $link = "<a href='$url'>$quote->post_title</a>"; 
        } else {
          $link = "<a href='$source_url'>$quote->post_title</a>"; 
        }
        $html .= "<blockquote class='bbquotations' cite='$link'>";
        $html .= "<p>";
        $html .= $quote->post_content;
        $html .= "</p>";
        
        $html .= "<cite>$link</cite>";
        $html .= "</blockquote>";  
      }
      return $html;
    }




    function remove_mediabuttons()
    {
      global $post;

      if($post->post_type == 'bbquote')
      {
        remove_action( 'media_buttons', 'media_buttons' );
      }
    }

    /**
     * Add one or more metaboxes
     * to the Event page
     * 
     * @access public
     * @return void
     */
    function add_metaboxes() 
    {
      // add a metabox to the Event using the callback add_time_date_metabox 
      add_meta_box( 
        "bbquotations-source",
        __("Quote source url", $this->localization_domain),
        array(&$this, "add_source_metabox"), $this->custom_post_type_name, "normal", "high"
      );

      add_meta_box( 
        "bbquotations-cheatsheet",
        __("Quotes usage cheatsheet", $this->localization_domain),
        array(&$this, "add_cheatsheet_metabox"), $this->custom_post_type_name, "side", "high"
      );
    }

    /**
     * Save the data from the add_time_date_metabox function 
     * 
     * @param mixed $post_id 
     * @param mixed $post 
     * @access public
     * @return void
     */
    function save_source_metabox($post_id, $post) 
    {
      // make sure our form data is sent 
      if( isset($_POST['bbquotations_wpnonce']) && ! wp_verify_nonce($_POST['bbquotations_wpnonce'], 'bbquotations-source') ) {
        return $post->ID;
      }

      // Is the user allowed to edit the post or page?
      if ( ! current_user_can('edit_post', $post->ID) ) {
        return $post->ID;
      }
      
      if( isset($_POST['bbquotations-source-url']) ) {
          update_post_meta($post->ID, 'bbquotations-source-url', $_POST['bbquotations-source-url']);
      }
    }


    /**
     * Add custom column headers to the Manage Events admin interface 
     * 
     * @param mixed $columns 
     * @access public
     * @return void
     */
    function add_header_columns($columns) 
    {
      $columns = array(
        "cb"           => "<input type=\"checkbox\" />",
        "id"           => __('Id'),
        "title"        => __('Title'),
        "quote"      => __('Quote', $this->localization_domain),
        "author"       => __('Author'),
        "date"         => __('Publish Date'),
      );
      return $columns;
    }


    /**
     * Fill the custom columns with data 
     * 
     * @param mixed $column 
     * @access public
     * @return void
     */
    function add_custom_columns($column) 
    {
      global $post;
      
      //var_dump($post);
      switch($column) {
        case 'id':
          echo $post->ID;
          break;
        case 'quote':
          echo $post->post_content;
          break;
      }
    }



  }
}

// instantiate the class
if ( class_exists('bbQuotations') ) { 
  $bbquote_var = new bbQuotations();
  register_activation_hook(__FILE__, array(&$bbquote_var,'activate') ); 
}


?>
