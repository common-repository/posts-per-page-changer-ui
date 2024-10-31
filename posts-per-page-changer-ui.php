<?php
/**
 * @package posts-per-page-changer-ui
 * @version 1.0.1
 */
/*
Plugin Name: Posts Per Page Changer UI
Version: 1.0.1
Description: Add a UI(select) to change posts displayed on the page.
Author: Toshiyuki Takahashi
Author URI: https://www.expexp.jp/
Plugin URI: https://www.expexp.jp/posts-per-page-changer/
License: GPLv2
Text Domain: posts-per-page-changer-ui
*/

// Scripts to use for plugin
add_action( 'wp_enqueue_scripts', 'posts_per_page_changer_ui_scripts' );
function posts_per_page_changer_ui_scripts() {
	wp_enqueue_script( 'posts_per_page_changer_ui', plugins_url( '', __FILE__ ).'/js/posts-per-page-changer-ui.js', array(), false, true );
}

// Add custom query
add_filter( 'query_vars', function ( $qvars ) {

	// Add query variable to $vars array
	$qvars[] = 'posts_per_page';

	return $qvars;
} );

// If there is a custom query variable (?posts_per_page=$num) in the URL, override the display count of the main query.
add_action( 'pre_get_posts', 'override_posts_per_page' );
function override_posts_per_page( $query ) {
	global $wp_query;
	if ( !empty( $wp_query->query['posts_per_page'] ) && is_numeric( $wp_query->query['posts_per_page'] ) && $query->is_main_query() ) {
		$query->set( 'posts_per_page', $wp_query->query['posts_per_page'] );
	}
}

// A function that outputs a select box that visitors can change the number of displayed items on the archive page.
function posts_per_page_changer_ui() {
	global $wp_query;

	// Get default posts_per_page
	$default_posts_per_page = get_option('posts_per_page');

	// Get current posts_per_page
	$posts_per_page = $wp_query->query_vars['posts_per_page'];

	// Get setting posts_per_page val
	$setting_val = get_option( 'posts_per_page_changer_ui_setting' );

	// Options displayed in select box
	if( !empty( $setting_val ) ) {
		$selectOptions = array_filter( array_map( 'trim', explode( "\n", $setting_val['changer_val'] ) ), 'strlen' );
		$display_num_array_full = array_merge( $selectOptions, array( $default_posts_per_page ) );
		$display_num_array = array_unique( $display_num_array_full );
		sort( $display_num_array );
	} else {
		$display_num_array = array( $default_posts_per_page );
	}

	// Select box HTML part
	?>
	<form name="postsperpageChanger">
		<select name="linkselect" onChange="overrideQuery()">
		    <?php
		    foreach( $display_num_array as $display_num ):
				$posts_per_page == $display_num ? $selected = ' selected' : $selected = '';
				?>
				<option value="./?posts_per_page=<?php echo $display_num; ?>"<?php echo $selected; ?>><?php echo $display_num; ?></option>
		    <?php endforeach; ?>
		</select>
	</form>
	<?php
}

class PostsPerPageChangerUISettingsPage {
	private $options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	public function add_plugin_page() {
		add_options_page( 'Posts Per Page Changer UI', 'Posts Per Page Changer UI', 'manage_options', 'posts_per_page_changer_ui_setting', array( $this, 'create_admin_page' ) );
	}

	public function page_init() {
		register_setting( 'posts_per_page_changer_ui_setting', 'posts_per_page_changer_ui_setting', array( $this, 'sanitize' ) );
		add_settings_section( 'posts_per_page_changer_ui_setting_section_id', '', '', 'posts_per_page_changer_ui_setting' );
		add_settings_field( 'changer_val', 'Value of select box', array( $this, 'changer_val_callback' ), 'posts_per_page_changer_ui_setting', 'posts_per_page_changer_ui_setting_section_id' );
	}

	public function create_admin_page() {
		$this->options = get_option( 'posts_per_page_changer_ui_setting' );
	?>
	<div class="wrap">
		<h2>Posts Per Page Changer UI Setting</h2>
		<?php
		global $parent_file;
		if ( $parent_file != 'options-general.php' ) {
			require(ABSPATH . 'wp-admin/options-head.php');
		}
		?>
		<p>Add a select box to change the main query posts_per_page.</p>
		<p><code>&lt;?php posts_per_page_changer_ui(); ?&gt;</code></p>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'posts_per_page_changer_ui_setting' );
			do_settings_sections( 'posts_per_page_changer_ui_setting' );
			submit_button();
			?>
		</form>
	</div>
	<?php
	}

	public function changer_val_callback() {
		$changer_val = isset( $this->options['changer_val'] ) ? $this->options['changer_val'] : '';
		?>
		<textarea id="changer_val" name="posts_per_page_changer_ui_setting[changer_val]" cols="10" rows="6" placeholder="5&#13;&#10;15&#13;&#10;25&#13;&#10;50"><?php echo esc_textarea( $changer_val ); ?></textarea>
		<p class="description">Please break it.</p><?php
	}

	public function sanitize( $input ) {
		$this->options = get_option( 'posts_per_page_changer_ui_setting' );
		$new_input = array();

		if( isset( $input['changer_val'] ) && trim( $input['changer_val'] ) !== '' ) {
			$new_input['changer_val'] = wp_unslash( $input['changer_val'] );
		}
		return $new_input;
	}
}

if( is_admin() ) {
  $posts_per_page_changer_ui_settings_page = new PostsPerPageChangerUISettingsPage();
}