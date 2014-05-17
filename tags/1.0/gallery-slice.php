<?php
/*
Plugin Name: Gallery Slice
Plugin URI: http://www.honza.info/category/wordpress/
Description: Slices gallery to a "preview" on archive pages (date, category, tag and author based lists, usually including homepage)
Version: 1.0
Author: Honza Skypala
Author URI: http://www.honza.info/
*/

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

class GallerySlice {
  const version = "1.0";

  const ajax_devel_script    = 'ajax-devel.js';
  const ajax_minified_script = 'ajax.js';
  protected $ajaxscript;

  public function __construct() {
    $this->ajaxscript = $this->enforce_devel_script() ? self::ajax_devel_script : self::ajax_minified_script;
    add_action('init', create_function('', 'load_plugin_textdomain("gallery_slice", false, basename(dirname(__FILE__)));'));
    if (is_admin()) {
      add_action('admin_init', array($this, 'gallery_slice_admin_init'));
      add_action('admin_enqueue_scripts', array($this, 'gallery_slice_admin_enqueue_scripts'));
      add_action('save_post', array($this, 'gallery_slice_save_postdata'));
    } else {
      add_filter('the_content', array($this, 'gallery_slice'), 1);
      wp_register_script('gallery-slice-ajax', plugin_dir_url(__FILE__) . $this->ajaxscript, array('jquery'), false, !$this->ajaxscript_to_head());
      if ($this->ajaxscript_to_head())
        add_action('wp_enqueue_scripts', array($this, 'gallery_slice_enqueue_scripts'));
    }
    add_action('wp_ajax_nopriv_gallery_slice-full_gallery', array($this, 'gallery_slice_full_gallery'));
    add_action('wp_ajax_gallery_slice-full_gallery', array($this, 'gallery_slice_full_gallery'));
    register_activation_hook(__FILE__, array($this, 'gallery_slice_activate'));  // activation of plugin
    add_filter('plugin_action_links', array($this, 'gallery_slice_filter_plugin_actions'), 10, 2);  // link from Plugins list admin page to settings of this plugin
    add_action( 'add_meta_boxes', array($this, 'gallery_slice_add_custom_box'));
  }

  public function gallery_slice($content) {
    if (!is_singular() && has_shortcode($content, 'gallery')) {
      $pattern = self::gallery_shortcode_regex;
      return preg_replace_callback("/$pattern/s", array($this, 'process_gallery_shortcode_tag'), $content);
    }
    return($content);
  }

  public function process_gallery_shortcode_tag($m) {
    $post = get_post();
    // if noslice set in post meta, then do not slice the gallery
    if (get_post_meta($post->ID, '_gallery_noslice', true) == "1") return $m[0];
    
    // get tag name and attributes
    $tag = $m[2];
    $attr = shortcode_parse_atts( $m[3] );
    $orig_attr_json = json_encode($attr);
    
    if (!isset($attr['ids'])) {
      extract(shortcode_atts(array(
        'order'      => 'ASC',
        'orderby'    => 'menu_order ID',
        'id'         => $post ? $post->ID : 0,
        'itemtag'    => 'dl',
        'icontag'    => 'dt',
        'captiontag' => 'dd',
        'columns'    => 3,
        'size'       => 'thumbnail',
        'include'    => '',
        'exclude'    => '',
        'link'       => ''
      ), $attr, 'gallery'));
      $id = intval($id);
      if ( 'RAND' == $order )  $orderby = 'none';
      $attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
      foreach ( $attachments as $att_id => $attachment )
        $attr['ids'] = isset($attr['ids']) ? $attr['ids'] . "," . $att_id : $att_id;
    }
    
    if (!isset($attr['ids'])) return $m[0]; // empty gallery? nothing to do then
  
    // if attribute noslice is set, then do not slice
    foreach ($attr as $value)
      if ($value == "noslice") return $m[0];
    
    if (isset($attr['sliceto'])) {
      // if gallery tag has attribute sliceto set, prefer its value
      $slice_threshold = $attr['sliceto'];
      $slice_downto    = $attr['sliceto'];
    } else if (get_post_meta($post->ID, '_gallery_downto', true) != "") {
      // if downto define in post meta, then use it
      $slice_threshold = get_post_meta($post->ID, '_gallery_downto', true);
      $slice_downto    = $slice_threshold;
    } else {
      // otherwise use global slice settings
      $slice_threshold = get_option('gallery_slice_threshold');
      $slice_downto    = get_option('gallery_slice_downto');
    }
    
    $ids = explode(",", $attr['ids']);
    if (count($ids) <= $slice_threshold) return $m[0]; // threshold not reached, do not slice
  
    if (!$this->ajaxscript_to_head()) $this->gallery_slice_enqueue_scripts();
  
    $ids = array_slice($ids, 0, $slice_downto, true);
    $attr['ids'] = implode(",", $ids);
    
    // construct the attributes string again
    $ids_string = "";
    foreach ($attr as $key => $value)
      switch (gettype($key)) {
      case "integer":
        $ids_string .= ' ' . $value;
        break;
      default:
        $ids_string .= ' ' . $key . '="' . $value . '"';
      }
    
    // construct hyperlink to full gallery
    if (isset($attr['link2full'])) {
      $full_gallery_link = $attr['link2full'];
    } else if (get_post_meta($post->ID, '_gallery_link2full', true) != "") {
      $full_gallery_link = get_post_meta($post->ID, '_gallery_link2full', true);
    } else {
      $full_gallery_link = get_option('gallery_slice_link2full');
    }
    $full_gallery_link = trim($full_gallery_link);
    if ($full_gallery_link != "") {
      $full_gallery_link = "<div class=\"unsliced-gallery-link\"><a href=\"" . get_permalink() . "\" post_id=\"" . $post->ID . "\" orig_gallery_attrs=\"" . htmlspecialchars($orig_attr_json) . "\">$full_gallery_link</a></div>";
    }
  
    // return the tag back with sliced ids
    return "[" . $tag . $ids_string . "]" . $full_gallery_link;
  }

  const gallery_shortcode_regex = '\\[(\\[?)(gallery)(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*+(?:\\[(?!\\/\\2\\])[^\\[]*+)*+)\\[\\/\\2\\])?)(\\]?)';

/*  
      '\\['                              // Opening bracket
      . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
      . "(gallery)"                     // 2: Shortcode name
      . '(?![\\w-])'                       // Not followed by word character or hyphen
      . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
      .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
      .     '(?:'
      .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
      .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
      .     ')*?'
      . ')'
      . '(?:'
      .     '(\\/)'                        // 4: Self closing tag ...
      .     '\\]'                          // ... and closing bracket
      . '|'
      .     '\\]'                          // Closing bracket
      .     '(?:'
      .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
      .             '[^\\[]*+'             // Not an opening bracket
      .             '(?:'
      .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
      .                 '[^\\[]*+'         // Not an opening bracket
      .             ')*+'
      .         ')'
      .         '\\[\\/\\2\\]'             // Closing shortcode tag
      .     ')?'
      . ')'
      . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
*/

  public function gallery_slice_admin_init() {
    wp_register_script( 'gallery-slice-admin-script', plugins_url( '/admin.js', __FILE__ ) , array('jquery'), false, true);
    add_settings_section('gallery_slice_section', __('Gallery Slice', 'gallery_slice'), array($this, 'gallery_slice_settings_section'), 'media');
    register_setting('media', 'gallery_slice_threshold', create_function('$input', 'return(filter_var($input, FILTER_SANITIZE_NUMBER_INT));'));
    add_settings_field('gallery_slice_threshold', __('Maximum Threshold', 'gallery_slice'), create_function('', 'GallerySlice::gallery_slice_option_length("threshold", "If gallery contains more than this amount of pictures, it will be sliced down; otherwise it will be kept intact.");'), 'media', 'gallery_slice_section');
    register_setting('media', 'gallery_slice_downto', create_function('$input', 'return(filter_var($input, FILTER_SANITIZE_NUMBER_INT));'));
    add_settings_field('gallery_slice_downto', __('Slice down to', 'gallery_slice'), create_function('', 'GallerySlice::gallery_slice_option_length("downto", "If threshold surpassed, slice gallery down to this amount of pictures.");'), 'media', 'gallery_slice_section');
    register_setting('media', 'gallery_slice_link2full', create_function('$input', 'return(sanitize_text_field($input));'));
    add_settings_field('gallery_slice_link2full', __('Full gallery link text', 'gallery_slice'), create_function('', 'GallerySlice::gallery_slice_option_string("link2full", "The text that should be shown for displaying full gallery.");'), 'media', 'gallery_slice_section');
  }
  
  public function gallery_slice_settings_section() {
    echo(
      '<div id="gallery_slice_options_desc" style="margin:0 0 15px 10px;-webkit-border-radius:3px;border-radius:3px;border-width:1px;border-color:#e6db55;border-style:solid;float:right;background:#FFFBCC;text-align:center;width:200px">'
      . '<p style="line-height:1.5em;">Plugin <strong>Gallery Slice</strong><br />Autor: <a href="http://www.honza.info/" class="external" target="_blank" title="http://www.honza.info/">Honza Skýpala</a></p>'
      . '</div>'
      . '<p>' . __('You can slice the gallery on archive type pages — date-based, category-based, tag-based, author-based lists of posts. Typically a homepage is a date-based archive, then it applies also to homepage.', 'gallery_slice'). '</p>'
      . '<p>' . __('The purpose is that your post can contain a huge gallery of pictures (let\'s say like 100), but you want to display all of 100 only on a single post page; on a homepage / archive page you want to display just a couple of images, like a preview. This plugins slices the gallery only to a defined number.', 'gallery_slice'). '</p>'
    ); 
  }

  public function gallery_slice_option_length($option, $description) {
    echo(
      '<input name="gallery_slice_' . $option . '" type="number" min="1" id="gallery_slice_' . $option . '" value="' . get_option("gallery_slice_$option") . '" class="small-text" /> '
      . __($description, 'gallery_slice')
     );
  }

  public function gallery_slice_option_string($option, $description) {
    echo(
      '<input name="gallery_slice_' . $option . '" type="text" id="gallery_slice_' . $option . '" value="' . get_option("gallery_slice_$option") . '" class="regular-text" /> '
      . __($description, 'gallery_slice')
     );
  }

  protected static $script_enqueued = false;
  public function gallery_slice_enqueue_scripts() {
    if (!self::$script_enqueued) {
      wp_enqueue_script('gallery-slice-ajax');
      wp_localize_script('gallery-slice-ajax', 'GallerySliceAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
      self::$script_enqueued = true;
    }
  }
  
  public function gallery_slice_admin_enqueue_scripts($hook) {
    if ($hook != "post.php" && $hook != "post-new.php") return;
    wp_enqueue_script('gallery-slice-admin-script');
  }

  public function gallery_slice_activate() {
    update_option('gallery_slice_version', self::version); // store plug-in version, if we later need to provide specific actions during upgrade
    add_option('gallery_slice_threshold', 15);
    add_option('gallery_slice_downto', 9);
    add_option('gallery_slice_link2full', __("Full gallery →", 'gallery_slice'));
  }

  protected static $this_plugin;
  public function gallery_slice_filter_plugin_actions($links, $file) {
    // Add settings link to plugin list for this plugin
    if (!self::$this_plugin) self::$this_plugin = plugin_basename(__FILE__);
    
    if ($file == self::$this_plugin) {
      $settings_link = '<a href="options-media.php#gallery_slice_options_desc">' . __('Settings') . '</a>';
      array_unshift( $links, $settings_link ); // before other links
    }
    return $links;
  }

  public function gallery_slice_add_custom_box() {
    $screens = array('post', 'page');
    foreach ($screens as $screen) {
        add_meta_box(
            'gallery_slice_sectionid',
            __('Gallery Slice', 'gallery_slice'),
            array($this, 'gallery_slice_inner_custom_box'),
            $screen,
            'side',
            'low'
        );
    }
  }

  public function gallery_slice_inner_custom_box( $post ) {
    echo '<div class="misc-pub-section" id="gallery_slice_noslice_div">';
    $value = get_post_meta( $post->ID, '_gallery_noslice', true );
    echo '<label class="selectit"><input value="1" type="checkbox" name="gallery_noslice" id="gallery_noslice"' . ($value == "1" ? ' checked="checked"' : '' ) .'> '. __('Do not slice gallery in this post', 'gallery_slice') .'</label>';
    echo '</div>';
  
    echo '<div class="misc-pub-section" id="gallery_slice_downto_div">';
    $value = get_post_meta( $post->ID, '_gallery_downto', true );
    $global_label = str_replace('%1', get_option('gallery_slice_downto'), __('Slice to globally configured value (%1)', 'gallery_slice'));
    echo '<label class="selectit"><input value="1" type="checkbox" name="gallery_slice_downto_global" id="gallery_slice_downto_global"' . ($value == "" ? ' checked="checked"' : '' ) .'> '. $global_label . '</label>';
    echo '<br />';
    echo '<div id="gallery_slice_downto_text_div" style="margin-top:0.7em">';
    $downto_input = '<input type="number" id="gallery_slice_downto" name="gallery_slice_downto" value="' . $value . '" global-value="' . get_option('gallery_slice_downto') . '" class="small-text" min="1" />';
    $downto_full = str_replace('%1', $downto_input, __('Slice down to %1 thumbnails', 'gallery_slice'));
    echo '<label for="gallery_slice_downto">' . $downto_full . '</label> ';
    echo '</div>';
    echo '</div>';
  
    echo '<div class="misc-pub-section" id="gallery_slice_text2link_div" style="border-bottom-width:0px">';
    $value = get_post_meta( $post->ID, '_gallery_link2full', true );
    $global_label = str_replace('%1', get_option('gallery_slice_link2full'), __('Use globally configured fully gallery hyperlink text (%1)', 'gallery_slice'));
    echo '<label class="selectit"><input value="1" type="checkbox" name="gallery_slice_link2full_global" id="gallery_slice_link2full_global"' . ($value == "" ? ' checked="checked"' : '' ) .'> '. $global_label . '</label>';
    echo '<br />';
    echo '<div id="gallery_slice_link2full_text_div" style="margin-top:0.7em">';
    echo '<label for="gallery_slice_link2full">';
    _e("Full gallery link text", 'gallery_slice');
    echo ':</label> ';
    echo '<input type="text" id="gallery_slice_link2full" name="gallery_slice_link2full" value="' . $value . '" global-value="' . get_option('gallery_slice_link2full') . '" style="width: 100%"/>';
    echo '</div>';
    echo '</div>';
  }

  public function gallery_slice_save_postdata( $post_id ) {
    $mydata = sanitize_text_field( $_POST['gallery_noslice'] );
    update_post_meta( $post_id, '_gallery_noslice', $mydata );
  
    $mydata = sanitize_text_field( $_POST['gallery_slice_downto_global'] );
    if ($mydata == "1") {
      update_post_meta($post_id, '_gallery_downto', "");
    } else {
      $mydata = sanitize_text_field( $_POST['gallery_slice_downto'] );
      update_post_meta($post_id, '_gallery_downto', $mydata);
    }
  
    $mydata = sanitize_text_field( $_POST['gallery_slice_link2full_global'] );
    if ($mydata == "1") {
      update_post_meta($post_id, '_gallery_link2full', "");
    } else {
      $mydata = sanitize_text_field( $_POST['gallery_slice_link2full'] );
      update_post_meta($post_id, '_gallery_link2full', $mydata);
    }
  }

  public function gallery_slice_full_gallery() {
    query_posts('p=' . $_POST['postID']);
    if (have_posts()) {
      the_post();
      header( "Content-Type: application/json" );
      $atts = json_decode(stripslashes(htmlspecialchars_decode($_POST['origAttrs'])), true);
      if ($_POST['link_to_file'] != "") $atts["link"]="file";
      echo json_encode(array('gallery' => gallery_shortcode($atts)));
    }
    exit;
  }

  protected function ajaxscript_to_head() {
    return $this->is_wpminify_active();
  }

  protected function enforce_devel_script() {
    // Plugins WP Minify and Better WP Minify ruin the script compiled by Google Closure Compiler; we enforce devel version of script instead
    return ($this->is_wpminify_active() || $this->is_bwpminify_active());
  }

  protected function is_wpminify_active() {
    global $ajaxscript;
    $return_val = false;
    if (is_plugin_active("wp-minify/wp-minify.php")) {
      $wpm_options = get_option("wp_minify");
      if ($wpm_options['enable_js']) {
        $return_val = true;
        foreach ($wpm_options['js_exclude'] as $value) {
          if (strpos($ajaxscript, $value) !== false) {
            $return_val = false;
            break;
          }
        }
      }
    }
    return $return_val;
  }

  protected function is_bwpminify_active() {
    if (is_plugin_active("bwp-minify/bwp-minify.php")) {
      $wpm_options = get_option("bwp_minify_general");
      if ($wpm_options['enable_min_js'] != "" && strpos($wpm_options['input_ignore'], 'gallery-slice-ajax') === false)
          return true;
    }
    return false;
  }
}

$wpGallerySlice = new GallerySlice();
?>