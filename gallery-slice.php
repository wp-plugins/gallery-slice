<?php
/*
Plugin Name: Gallery Slice
Plugin URI: http://wordpress.org/plugins/gallery-slice/
Description: Slices gallery to a "preview" on archive pages (date, category, tag and author based lists, usually including homepage)
Version: 1.3.3
Author: Honza Skypala
Author URI: http://www.honza.info/
License: WTFPL 2.0
*/

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

class GallerySlice {
  const version = "1.3.3";

  const ajax_devel_script    = 'ajax-devel.js';
  const ajax_minified_script = 'ajax.js';
  protected $ajaxscript;

  public function __construct() {
    $this->ajaxscript = $this->enforce_devel_script() ? self::ajax_devel_script : self::ajax_minified_script;
    add_action('init', create_function('', 'load_plugin_textdomain("gallery_slice", false, basename(dirname(__FILE__)));'));
    if (is_admin()) {
      add_action('admin_init', array($this, 'admin_init'));
      add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
      add_action('save_post', array($this, 'save_postdata'));
    } else {
      add_filter('the_content', array($this, 'slice'), 1);
      add_filter('rajce-gallery-images', array($this, 'slice_rajce'), 10, 3);
      add_action('wp_enqueue_scripts', create_function('', "wp_register_script('gallery-slice-ajax', '" . plugin_dir_url(__FILE__) . $this->ajaxscript ."', array('jquery'), false, " . (!$this->ajaxscript_to_head() ? 'true' : 'false') .");"), 10);
      if ($this->ajaxscript_to_head())
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 20);
    }
    add_action('wp_ajax_nopriv_gallery_slice-full_gallery', array($this, 'full_gallery')); // ajax handler for annonymous users
    add_action('wp_ajax_gallery_slice-full_gallery', array($this, 'full_gallery'));        // ajax handler for logged-in users
    add_action('wp_ajax_nopriv_gallery_slice-full_rajce_gallery', array($this, 'full_rajce_gallery'));
    add_action('wp_ajax_gallery_slice-full_rajce_gallery', array($this, 'full_rajce_gallery'));
    register_activation_hook(__FILE__, array($this, 'activate'));  // activation of plugin
    add_filter('plugin_action_links', array($this, 'filter_plugin_actions'), 10, 2);  // link from Plugins list admin page to settings of this plugin
    add_action( 'add_meta_boxes', array($this, 'add_custom_box'));
  }

  public function slice($content) {
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

    if (GallerySlice::noslice_attr($attr)) return $m[0]; // if attribute noslice is set, then do not slice
    list($slice_downto, $slice_threshold) = GallerySlice::get_slice_downto_and_threshold($attr, $post);

    $ids = explode(",", $attr['ids']);
    if (count($ids) <= $slice_threshold) return $m[0]; // threshold not reached, do not slice

    if (!$this->ajaxscript_to_head()) $this->enqueue_scripts();

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
      $full_gallery_link .= "<div class=\"gallery-loading-animation\" style=\"display:none\"><img src=\"". get_option('gallery_slice_waiting_img') . "\"></div>";
    }

    // return the tag back with sliced ids
    return "[" . $tag . $ids_string . "]" . $full_gallery_link;
  }

  const gallery_shortcode_regex = '\\[(\\[?)(gallery)(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*+(?:\\[(?!\\/\\2\\])[^\\[]*+)*+)\\[\\/\\2\\])?)(\\]?)';

  public function admin_init() {
    if (stristr($_SERVER['REQUEST_URI'], 'options-media.php')) {
      wp_enqueue_style('editor-buttons'); // we need to do this here; if we do it in admin_enqueue_scripts, it does not load
    }
    self::check_plugin_update();
    wp_register_script( 'gallery-slice-admin-script', plugins_url( '/admin.js', __FILE__ ) , array('jquery'), false, true);
    add_settings_section('gallery_slice_section', __('Gallery Slice', 'gallery_slice'), array($this, 'settings_section'), 'media');
    register_setting('media', 'gallery_slice_threshold', create_function('$input', 'return(filter_var($input, FILTER_SANITIZE_NUMBER_INT));'));
    add_settings_field('gallery_slice_threshold', __('Maximum Threshold', 'gallery_slice'), 'GallerySlice::option', 'media', 'gallery_slice_section', array('option'=>"threshold", 'type'=>'number', 'description'=>"If gallery contains more than this amount of pictures, it will be sliced down; otherwise it will be kept intact."));
    register_setting('media', 'gallery_slice_downto', create_function('$input', 'return(filter_var($input, FILTER_SANITIZE_NUMBER_INT));'));
    add_settings_field('gallery_slice_downto', __('Slice down to', 'gallery_slice'), 'GallerySlice::option', 'media', 'gallery_slice_section', array('option'=>"downto", 'type'=>'number', 'description'=>"If threshold surpassed, slice gallery down to this amount of pictures."));
    register_setting('media', 'gallery_slice_link2full', create_function('$input', 'return(sanitize_text_field($input));'));
    add_settings_field('gallery_slice_link2full', __('Full gallery link text', 'gallery_slice'), 'GallerySlice::option', 'media', 'gallery_slice_section', array('option'=>"link2full", 'description'=>"The text that should be shown for displaying full gallery."));
    register_setting('media', 'gallery_slice_waiting_img', create_function('$input', 'return(filter_var($input, FILTER_SANITIZE_URL));'));
    add_settings_field('gallery_slice_waiting_img', __('Loading animation', 'gallery_slice'), 'GallerySlice::option_waiting_img', 'media', 'gallery_slice_section');
  }

  public static function settings_section() {
    echo(
      '<div id="gallery_slice_options_desc" style="margin:0 0 15px 10px;-webkit-border-radius:3px;border-radius:3px;border-width:1px;border-color:#e6db55;border-style:solid;float:right;background:#FFFBCC;text-align:center;width:200px">'
      . '<p style="line-height:1.5em;">Plugin <strong>Gallery Slice</strong><br />Autor: <a href="http://www.honza.info/" class="external" target="_blank" title="http://www.honza.info/">Honza Skýpala</a></p>'
      . '</div>'
      . '<p>' . __('You can slice the gallery on archive type pages — date-based, category-based, tag-based, author-based lists of posts. Typically a homepage is a date-based archive, then it applies also to homepage.', 'gallery_slice'). '</p>'
      . '<p>' . __('The purpose is that your post can contain a huge gallery of pictures (let\'s say like 100), but you want to display all of 100 only on a single post page; on a homepage / archive page you want to display just a couple of images, like a preview. This plugins slices the gallery only to a defined number.', 'gallery_slice'). '</p>'
    );
  }

  public static function option(array $args) {
    echo(
      '<input name="gallery_slice_' . $args['option'] . '" type="' . (array_key_exists('type',$args) ? $args['type'] : "text") . '" id="gallery_slice_' . $args['option'] . '" value="' . get_option("gallery_slice_" . $args['option']) . '" class="' . (array_key_exists('type',$args) && $args['type'] == "number" ? "small-text" : "regular-text") . '" ' . (array_key_exists('type',$args) && $args['type'] == "number" ? 'min="1" ' : "") . '/> '
      . __($args['description'], 'gallery_slice')
     );
  }

  public static function option_waiting_img() {
    echo(
      '<img id="gallery_slice_waiting_img_preview" src="' . get_option("gallery_slice_waiting_img") . '" style="margin:6px 0"/><br>'
      . '<div style="margin-bottom:0.5em;float:none" class="wp-media-buttons">'
      . '<style>.wp-media-buttons .add_media span.wp-media-buttons-icon-imageonly:before{content:\'\\f306\';}</style>'
      . '<a id="gallery_slice_waiting_img_media_library_button" class="button insert-media add_media" style="padding-left:5px;padding-right:7px;" selecttext="' . __('Select image', 'gallery_slice') . '"><span class="wp-media-buttons-icon wp-media-buttons-icon-imageonly"></span>' . __('Media Library') . '</a> '
      . '<a id="gallery_slice_waiting_img_set_default" class="button" defaultvalue="' . plugins_url( '/ajax-loader.gif', __FILE__ ) . '">' . __('Set Default', 'gallery_slice') . '</a>'
      . '</div>'
      . '<input name="gallery_slice_waiting_img" type="url" id="gallery_slice_waiting_img" value="' . get_option("gallery_slice_waiting_img") . '" class="regular-text" /> '
      . __("URL of the img showing when loading rest of gallery.", 'gallery_slice')
     );
  }

  protected static $script_enqueued = false;
  public function enqueue_scripts() {
    if (!self::$script_enqueued) {
      wp_enqueue_script('gallery-slice-ajax');
      wp_localize_script('gallery-slice-ajax', 'GallerySliceAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
      self::$script_enqueued = true;
    }
  }

  public function admin_enqueue_scripts($hook) {
    switch ($hook) {
    case "options-media.php":
      wp_enqueue_style('dashicons');
      wp_enqueue_media();
      wp_enqueue_style('media'); // notice we continue, we do not break
    case "post.php":
    case "post-new.php":
      wp_enqueue_script('gallery-slice-admin-script');
    }
  }

  public function slice_rajce($args, $album_URL, $attr) {
    $images   = $args[0];
    $appendix = $args[1];
    $post = get_post();

    if (!is_singular()) {

      if (GallerySlice::noslice_attr($attr)) return array($images, $appendix);
      list($slice_downto, $slice_threshold) = GallerySlice::get_slice_downto_and_threshold($attr, $post);       // add also params from post / tag

      if (count($images) > $slice_threshold) {
        if (!$this->ajaxscript_to_head()) $this->enqueue_scripts();

        $attrs = array();
        $attrs['images'] = array_keys($images);
        $images_keys = array_keys($images);
        $attrs['image_base_URL'] = substr($images[$images_keys[0]], 0, strrpos($images[$images_keys[0]], '/')+1);
        $attrs['album_URL'] = $album_URL;
        $attrs['attr'] = $attr;

        $images = array_slice($images, 0, $slice_downto);

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
          $appendix .= "<div class=\"unsliced-gallery-link\"><a href=\"" . get_permalink() . "\" post_id=\"" . $post->ID . "\" orig_gallery_attrs=\"" . htmlspecialchars(json_encode($attrs)) . "\">$full_gallery_link</a></div>";
          $appendix .= "<div class=\"gallery-loading-animation\" style=\"display:none\"><img src=\"". get_option('gallery_slice_waiting_img') . "\"></div>";
        }
      }
    }

    return array($images, $appendix);
  }

  private static function noslice_attr($attr) {
    foreach ($attr as $value)
      if ($value == "noslice") return true;
    return false;
  }

  private static function get_slice_downto_and_threshold($attr, $post) {
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
    return array($slice_downto, $slice_threshold);
  }

  public function full_rajce_gallery() {
    query_posts('p=' . $_POST['postID']);
    if (have_posts()) {
      the_post();
      header( "Content-Type: application/json" );
      $attrs = json_decode(stripslashes(htmlspecialchars_decode($_POST['origAttrs'])), true);
      $images = array_combine($attrs['images'], $attrs['images']);
      foreach($images as $key => &$image)
        $image = $attrs['image_base_URL'] . $image;
      echo json_encode(array('gallery' => Rajce_embed::full_gallery($attrs['album_URL'], $images, $attrs['attr'], 1)));
    }
    exit;
  }

  public function activate() {
    update_option('gallery_slice_version', self::version); // store plug-in version, if we later need to provide specific actions during upgrade
    add_option('gallery_slice_threshold', 15);
    add_option('gallery_slice_downto', 9);
    add_option('gallery_slice_link2full', __("Full gallery →", 'gallery_slice'));
    add_option('gallery_slice_waiting_img', plugins_url( '/ajax-loader.gif', __FILE__ ));
  }

  public function check_plugin_update() {
    $registered_version = get_option('gallery_slice_version', '0');
    if (version_compare($registered_version, self::version, '<')) {
      if (version_compare($registered_version, '1.1', '<')) {
        // new option in version 1.1
        add_option('gallery_slice_waiting_img', plugins_url( '/ajax-loader.gif', __FILE__ ));
      }
      update_option('gallery_slice_version', self::version);
    }
  }

  protected static $this_plugin;
  public function filter_plugin_actions($links, $file) {
    // Add settings link to plugin list for this plugin
    if (!self::$this_plugin) self::$this_plugin = plugin_basename(__FILE__);

    if ($file == self::$this_plugin) {
      $settings_link = '<a href="options-media.php#gallery_slice_options_desc">' . __('Settings') . '</a>';
      array_unshift( $links, $settings_link ); // before other links
    }
    return $links;
  }

  public function add_custom_box() {
    $screens = array('post', 'page');
    foreach ($screens as $screen) {
        add_meta_box(
            'gallery_slice_sectionid',
            __('Gallery Slice', 'gallery_slice'),
            array($this, 'inner_custom_box'),
            $screen,
            'side',
            'low'
        );
    }
  }

  public function inner_custom_box($post) {
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

  public function save_postdata( $post_id ) {
    $mydata = isset($_POST['gallery_noslice']) ? sanitize_text_field($_POST['gallery_noslice']) : '';
    update_post_meta( $post_id, '_gallery_noslice', $mydata );

    $mydata = isset($_POST['gallery_slice_downto_global']) ? sanitize_text_field($_POST['gallery_slice_downto_global']) : '';
    if ($mydata == "1") {
      update_post_meta($post_id, '_gallery_downto', "");
    } else {
      $mydata = isset($_POST['gallery_slice_downto']) ? sanitize_text_field($_POST['gallery_slice_downto']) : '';
      update_post_meta($post_id, '_gallery_downto', $mydata);
    }

    $mydata = isset($_POST['gallery_slice_link2full_global']) ? sanitize_text_field($_POST['gallery_slice_link2full_global']) : '';
    if ($mydata == "1") {
      update_post_meta($post_id, '_gallery_link2full', "");
    } else {
      $mydata = isset($_POST['gallery_slice_link2full']) ? sanitize_text_field($_POST['gallery_slice_link2full']) : '';
      update_post_meta($post_id, '_gallery_link2full', $mydata);
    }
  }

  public function full_gallery() {
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