<?php
/*
 * Plugin Name: Presentations 2Go
 * Plugin URI: http://support.presentations2go.eu/hc/en-us/articles/203873995-Presentations-2Go-Wordpress-plugin
 * Description: A plugin that makes it easier to embed Rich Media Recordings of Presentations 2Go.
 * Version: 1.0.6
 * Author: Presentations 2Go
 * Author URI: http://www.presentations2go.eu
 * Text Domain: UK
 * Network: true
 * License: GPL2
 */


require_once('class-p2go-web-api.php');
class P2Goplugin {

	protected $dbVersion  = 1.0;
	//define the database version.

	//constructor invokes all hooks.
	public function __construct() {
		register_activation_hook(__FILE__, array($this, 'install') );
		add_action( 'admin_print_footer_scripts', array( $this, 'addP2Goshortcode' ) );
		add_action( 'plugins_loaded', array( $this, 'internationalize' ) );
		add_action( 'admin_head', array( $this, 'P2Go_shortcode_init' ) );
		add_action( 'network_admin_menu', array( $this, 'add_multisite_P2Go_settings_page') );
		add_action( 'admin_menu', array( $this, 'add_P2Go_settings_page') );
		add_action( 'admin_init' , array( $this, 'P2Go_settings_page_init') );
		add_shortcode('Presentations2Go', array( $this, 'Presentations2Go_shortcode' ) );
	}
	
	public function install() {
	global $wpdb;
		//add option to the database.
		add_option( 'p2go_rich_media_settings' );
		add_site_option('p2go_db_version', $this->dbVersion);
		//add the table to save query's.
		if( ! empty ( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
		if( ! empty ( $wpdb->collate ) ) {
			$charset_collate = " COLLATE {$wpdb->collate}";
		}
		
		$table_name = $wpdb->prefix . "P2G_queries";
			
		if( $wpdb->get_var("show tables like '$table_name'") != $table_name ) {
			$query = "CREATE TABLE " . $table_name . "(
			id integer(10) AUTO_INCREMENT  PRIMARY KEY,
			query varchar(500) NOT NULL
			){$charset_collate};";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta($query);
		}
	}

	//add the multistie settings page
	public function add_multisite_P2Go_settings_page() {
		add_submenu_page( 'settings.php', 'P2Go Rich Media Recordings','Presentations 2Go', 'manage_options','p2go-rich-media-settings', array( $this, 'settings_page' ) );
	}
	
	//add the single site settings page.
	public function add_P2Go_settings_page() {
		add_submenu_page( 'options-general.php', 'P2Go Rich Media Recordings','Presentations 2Go', 'manage_options','p2go-rich-media-settings', array( $this, 'settings_page' ) );
	}
	
	public function Presentations2Go_shortcode($atts, $content, $tags) {
	//proces the shortcode.
	try {
	global $wpdb;
		$options = (array) get_option('p2go_rich_media_settings');
		
		$atts = shortcode_atts( array(
			'queryid' => 'null',
			'expiration' => '0',
			'server' => 'http://localhost',
			'key' => 'test',
			'total' => '3',
			'size' => 'medium',
			'layout' => 'tile',
			'display' => 1,
			'automaticResize' => false,
			'splashScreenUrl' => '',
			'background' => '000000',
			'accentcolor' => 'ffffff',
			'showminimalbuttons' => true,
		), $atts, 'Presentations2Go' );
		//set the default timezone to the server time, because WordPress changes the timezone itself.
		date_default_timezone_set('Europe/Berlin');
		$query = new P2GoWebApiQuery('', $wpdb);
		$query = new P2GoWebApiQuery( str_replace(' ', '+', $query->getQueryById($atts['queryid']) ) , $wpdb);
		$token = new P2GoWebApiToken();
		$user_settings = array(
		'automaticResize' => $atts['automaticResize'],
		'splashScreenUrl' => $atts['spashScreenUrl'],
		'background' => $atts['background'],
		'accentcolor' => $atts['accentcolor'],
		'showminimalbuttons' => $atts['showminimalbuttons']
		);
		$API = new P2GoWebApi($atts['server'], $options['group'], $atts['expiration'], $atts['key'], "advancedsearch");
		$API->addParams(
		array('automaticResize','background','accentcolor','showminimalbuttons', 'layout' ),
		array($atts['automaticResize'], $atts['background'], $atts['accentcolor'], $atts['showminimalbuttons'], $atts['display'] ) 
		);
		$response = json_decode( $API->makeRequest($token,$query) );
	} catch(\Exception $e) {
		_e("Error: Something went wrong, make sure you provided the correct parameters", "P2Go");
	}
		//if there was a response
		if($response->success)
		{
			$html = "";
			
			// in case of embed or inline video presentation scale to aspect ratio 2:1
			if ($atts['layout'] !== 'text' AND $atts['layout'] !== 'thumbs') {
			$html.= "<style>#container {display: inline-block;position: relative;";

				switch($atts['size']) {
					case "small":
					$html.= "width: 25%;";
					break;
					case "medium":
					$html.= "width: 50%;";
					break;
					case "large":
					$html.= "width: 100%;";
					break;
				}



			$html.="} #dummy {padding-top: 37%;} #element {position: absolute; top: 0; bottom: 15px; left: 0; right: 0;    }</style>";
			
			};

			



			$count = 0;
			//add each video as long as it isn't over the total.
			foreach($response->response as $video)
			{
				if($count === intval($atts['total']) ) {
					break;
				}
				// case play inline video
				if($options['display'] == 'inline' AND $atts['layout'] == 'inline' ) {
					$html.= "<h3 class='p2go_title'>" . $video->title . "</h3>";
					$html.= "<div id='container'> <div  id='dummy'></div><div  id='element'><iframe src='" . $video->playbackUrl . "&autoplay=false";
				} 
				//case show results as text only
				if($atts['layout'] == 'text') {
					$html.= "<p><div class='p2go_title'><a href='". $video->playbackUrl ."' target='_blank'>" . $video->title . "</a></div>";
					$html.= "<div class='p2go_contributors'>By: ". $video->contributors ."</div>";
					$html.= "<div class='p2go_views'>Views: ". $video->views ."</div></p>";
				} 

				//case show results as 4 thumbnails per row
				if($atts['layout'] == 'thumbs') {
					$html.= "<div class='p2go_thumb' style='display:inline-block;width:25%'; margin:5px;'> <a href='". $video->playbackUrl ."' target='_blank'><img src='" . $video->thumbnailUrl . "' title='". $video->title ." by: ". $video->contributors ."'></a>";
					$html.= "<div class='p2go_views' >Views: ". $video->views ."</div>";
					$html.= " </div>";
				} 

				//case show video and slide with playbutton
				if ($atts['layout'] == 'inline' AND $options['display'] == 'embed' ) {
					$html.= "<h3 class='p2go_title'>" . $video->title . "</h3>";
					$html.= "<div id='container'> <div  id='dummy'></div><div  id='element'><iframe src='" . $video->embedUrl . "&autoplay=true";
				}


				//case embed or inline video set player parameters
				if ($atts['layout'] !== 'text' AND $atts['layout'] !== 'thumbs') {
				foreach($API->getParams( array( 'automaticResize', 'splashScreenURL', 'background', 'accentcolor', 'showminimalbuttons' ) ) as $key => $value)
				{
					$html.= "&" . (string) $key . "=" . (string) $value;
				}
				
				$html.= "'";
				$html.= " style='width:100%;height:100%;display:inline-block;padding:2%;border: none;'></iframe></div></div><br>";
				$html.= "<div class='p2go_contributors'>By: ". $video->contributors ."; Views: ". $video->views ."</div></br>";
				}
				$count++;
			}
			return $html;
		} else {
			return "An error occured: " . $response->error;
		}
	}
	
	public function settings_page() {
		//create the content of the settings page
		?>
		<div class="wrap">
			<h2>Settings</h2>
			<form method="post" action="options.php">
			<?php
				settings_fields( 'P2Go_rich_media_setting_options_group' );
				do_settings_sections( 'p2go-rich-media-settings' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}
	public function verify_values( $input )
	{
		//function to validate the settings.
		$newinput = array();
		if( isset( $input['server_address'] ) ) {
			if( strlen( trim( $input['server_address'] ) ) > 7)
			{
				$newinput['server_address'] = $input['server_address'];
			} else {
				$newinput['server_address'] = 'http://localhost/';
			}
		}
		
		if( isset( $input['secret_key'] ) ) {
			if( strlen( trim( $input['secret_key'] ) ) > 4)
			{
				$newinput['secret_key'] = $input['secret_key'];
			} else {
				$newinput['secret_key'] = '';
			}
		}
		
		if( isset( $input['total_results'] ) ) {
			if( intval( $input['total_results'] ) > 0)
			{
				$newinput['total_results'] = $input['total_results'];
			} else {
				$newinput['total_results'] = 2;
			}
		}
		
		if( isset( $input['expiration'] ) ) {
			if( is_numeric( $input['expiration'] ) )
			{
				$newinput['expiration'] = $input['expiration'];
			} else {
				$newinput['expiration'] = 1;
			}
		}
		
		if( isset( $input['layout'] ) ) {
			if( in_array($input['layout'], array( 'text', 'tile', 'thumb' ) ) ) {
				$newinput['layout'] = $input['layout'];
			}
		}
		
		if( isset( $input['display'] ) ) {
			if( in_array($input['display'], array('inline', 'embed') ) ) {
				$newinput['display'] = $input['display'];
			}
		}
		if( isset( $input['group'] ) ) {
			if( strlen( trim( $input['group'] ) ) > 2) {
				$newinput['group'] = $input['group'];
			}
		}
		
		return $newinput;
	}
	
	//function to add all the fields and sections to the Settings page.
	public function P2Go_settings_page_init()
	{
		register_setting(
			'P2Go_rich_media_setting_options_group',
			'p2go_rich_media_settings',
			array( $this, 'verify_values')
		);
		
		add_settings_section(
			'P2Go_setting_options_id',
			'P2Go setting options',
			array( $this, 'print_section_info' ),
			'p2go-rich-media-settings'
			);
			
		add_settings_field(
			'P2go-server-address',
			'Server address:',
			array( $this, 'create_field' ),
			'p2go-rich-media-settings',
			'P2Go_setting_options_id',
			array('name' => 'server_address')
		);
		
		add_settings_field(
			'P2go-group',
			'Group:',
			array( $this, 'create_field' ),
			'p2go-rich-media-settings',
			'P2Go_setting_options_id',
			array( 'name' => 'group')
		);
		
		add_settings_field(
			'P2go-secret-key',
			'Secret Key:',
			array( $this, 'create_field' ),
			'p2go-rich-media-settings',
			'P2Go_setting_options_id',
			array('name' => 'secret_key')
		);
		
		add_settings_field(
			'P2go-amount-results',
			'Total amount of results:',
			array( $this, 'create_field' ),
			'p2go-rich-media-settings',
			'P2Go_setting_options_id',
			array('name' => 'total_results')
		);
		
		add_settings_field(
			'P2go-expiration',
			'Expiration (in Hours):',
			array( $this, 'create_field' ),
			'p2go-rich-media-settings',
			'P2Go_setting_options_id',
			array('name' => 'expiration')
		);
		
		add_settings_field(
			'P2go-layout',
			'Layout:',
			array( $this, 'create_dropdown'),
			'p2go-rich-media-settings',
			'P2Go_setting_options_id',
			array('name' => 'layout')
		);
		
		add_settings_field(
			'P2go-Embed',
			'Display:',
			array( $this, 'create_dropdown'),
			'p2go-rich-media-settings',
			'P2Go_setting_options_id',
			array('name' => 'display')
		);
		
	}
	
	//function to create and load the dropdown list in the settings page.
	public function create_dropdown($args) {
		extract($args);
		$optionsArray = (array) get_option('p2go_rich_media_settings');
		
		$current_value = $optionsArray[$name];
		$html = '<select name="p2go_rich_media_settings['.$name.']">';
		if($name=='layout') {
			$html.= '<option value="text">Text</option>';
			$html.= '<option value="tile"';
			if($current_value=='tile') {
				$html.= " selected='selected'>Tile</option>";
			} else {
				$html.= ">Tile</option>";
			}
			
			$html.= '<option value="thumbnail"';
			if($current_value=='thumbnail') {
				$html.= " selected='selected'>Thumbnail</option>";
			} else {
				$html.= ">Thumbnail</option>";
			}
		} elseif($name=='display')
		{
			$html.= '<option value="inline">inline-video</option>';
			$html.= '<option value="embed"';
			if($current_value=='embed') {
				$html.= " selected='selected'>Embed</option>";
			} else {
				$html.= '>Embed</option>';	
			}
		}
		$html.= '</select><span>' . __("description_{$name}", 'P2Go') . '</span>';
		echo $html;
	}
	
	//function to create the description on the settings page.
	public function print_section_info()
	{
		return '<p>' . _e('settings_page_description', 'P2Go') . '</p>';
	}
	
	//function to create the text fields in the settings page.
	public function create_field($args) {
		extract($args);
		$optionsArray = (array) get_option('p2go_rich_media_settings');
		
		$current_value = $optionsArray[$name];
		echo '<input type="text" name="p2go_rich_media_settings['.$name.']" value="' . $current_value . '"><span> ' . __("description_{$name}", "P2Go") . '</span>';
	}
	
	//function to add the tinyMCE button and plugin
	public function P2Go_shortcode_init() {
		if( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
			return;
		}
	
		if( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter('mce_external_plugins', array( $this, 'register_tinymce_plugin' ) );
			add_filter('mce_buttons', array( $this, 'register_tinymce_button') );
		}
	}
	
	//register the tinymce plugin
	public function register_tinymce_plugin( $plugins ) {
		$plugins['P2GoWA'] = plugins_url( '/P2GoSC.js', __FILE__ );
		return $plugins;
	}
	
	//register the tinyMCE button
	public function register_tinymce_button( $buttons ) {
		array_push( $buttons, 'P2GoWA_button' );
		return $buttons;
	}
	
	public function internationalize() {
		//load the translation if available.
		load_plugin_textdomain( 'P2Go', false, basename( dirname( __FILE__ ) ) . '/langs/' );
	}
	
	public function addP2Goshortcode() {
		$options = get_option('p2go_rich_media_settings');
		if( wp_script_is('quicktags') ) {
		?>
			<script type="text/javascript">
			var countQueryRows = 1;//used to count the amount of query fields, default is 1
			var response; //used to get the ajax response.
			var query; // used to define the query, this var is global because the .each can't modify it otherwise.
			var KeyList; //used to define the possible selecters of the query
			
			//check if HTML5 placeholder is supported
			function supports_html5_placeholder() {
				
				var test = document.createElement( 'input' );
				
				var result = test.hasOwnProperty( 'placeholder' ) || ( 'placeholder' in test );
				
				test = null;
				//do the check on the input element we created above and return the results of the check.
				return result;
			}
			
			//function to set the response of the ajax request to an other scope so it can be reached inside other functions.
			function setKeyList(data) {
				KeyList = data;
			}
			
			//function to set the response of the ajax request to an other scope so it can be reached inside other functions.
			function setResponse( value ) {
				response = value;
			}
			function getKeyList() {
				request_data  = {
					type: 'requestlist',
					server: '<?php echo $options['server_address'];?>',
					 //the encode is required because it could contain a + character or other characters which get lost during the stringify function
					key: encodeURIComponent('<?php echo $options['secret_key']; ?>' )
				}
				jQuery.ajax({
						type: 'POST',
						dataType: 'json',
						cache: false,
						url: '<?php echo plugins_url( '/api.php', __FILE__ ); ?>',
						data: "data=" + JSON.stringify(request_data),
						async: false
					}).done( function( data ) {
						setKeyList( data );
				});
			}
			//get the criteria to search on.
			getKeyList();
			
			//use this function to create the query which will be send to the database.
			function append_query( value, isNew ) {
				if(isNew) {
					query = value;
				} else {
					query+= ' ' + value;
				}
				return query;
			}

			function saveQuery( string ) {
				request_data = {
					type: 'query',
					method: 'save',
					query: string
					}
					jQuery.ajax({
						type: 'POST',
						dataType: 'json',
						cache: false,
						url: '<?php echo plugins_url( '/api.php', __FILE__ ); ?>',
						data: "data=" + JSON.stringify(request_data),
						async: false
					}).done( function( data ) {
						setResponse( data );
				});
			}
			
			function getQueryId( string ) {
				request_data = {
					type: 'query',
					method: 'getIdByQuery',
					query: string
				}
				jQuery.ajax({
					type: 'POST',
					dataType: 'json',
					cache: false,
					url: '<?php echo plugins_url( '/api.php', __FILE__ ); ?>',
					data: "data=" + JSON.stringify(request_data),
					async: false
				}).done( function( data ) {
					setResponse( data );
				});
			} 
			//function to make it easier to create selects.
			function createSelect( name, values ) {
				if( name !== 'language' && name !== 'group') {
					var html = '<select name="' + name + '" class="query">';
				} else {
					var html = '<select name="' + name + '">';
				}
				var iterations = values.length;
				for(var i = 0; i < iterations; i++) {
					html+= '<option value="' + values[i] + '">' + values[i] + '</option>';
				}
				html+= '</select>';
				return html;
			}
			
			//create the wizard to show
			form = '<form method="post" action="" id="P2GoWizard">';
			form+= '<table class="form-table"><tbody>';
			form+= '<tr><th scope="row"><label for="server-address"><?php _e( 'Server Address', 'P2Go' ); ?></label></th><td><input type="text" name="server-address" ';
			if( supports_html5_placeholder() ){
				form+= 'placeholder="';
			} else {
				form+= 'value="';
			}
			form+= '<?php echo $options['server_address']; ?>"><span><?php _e("description_server_address", "P2Go"); ?></span></td></tr>';
			form+= '<tr><th scope="row"><label for="secret-key"><?php _e( 'Secret Key', 'P2Go' ); ?></label></th><td><input type="text" name="secret-key" ';
			if( supports_html5_placeholder() ){
				form+= 'placeholder="';
			} else {
				form+= 'value="';
			}
			form+= '<?php echo $options['secret_key']; ?>"><span><?php _e("description_secret_key", "P2Go"); ?></span></td></tr>';
			form+= '<tr><th scope="row"><label for="expiration"><?php _e( 'Expiration(hours)', 'P2Go' ); ?></label></th><td><input type="text" name="expiration" '
			if( supports_html5_placeholder() ){
				form+= 'placeholder="';
			} else {
				form+= 'value="';
			}
			form+= '<?php echo $options['expiration']; ?>"><span><?php _e("description_expiration", "P2Go"); ?></span></td></tr>';
			form+= '<tr><th scope="row"><label for="totalPosts"><?php _e( 'Total Results', 'P2Go' ); ?></label></th><td><input type="text" name="totalPosts" ';
			if( supports_html5_placeholder() ){
				form+= 'placeholder="';
			} else {
				form+= 'value="';
			}
			form+= '<?php echo $options['total_results']; ?>"><span><?php _e("description_total_results", "P2Go"); ?></span></td></tr>';
			form+= '<tr><th scope="row"><label for="size"><?php _e( 'size', 'P2Go' ); ?></label></th><td><input type="radio" name="size" value="small"><span style="padding: 6px"><?php _e( 'small', 'P2Go' ); ?> </span><input type="radio" name="size" value="medium"><span style="padding: 6px"><?php _e( 'medium', 'P2Go' ); ?> </span><input type="radio" name="size" value="large"><span style="padding: 6px"><?php _e( 'large', 'P2Go' ); ?></span><span><?php _e("description_size", "P2Go"); ?></span></td></tr></td></tr>';
			form+= '<tr><th scope="row"><label for="layout"><?php _e( 'layout', 'P2Go' ); ?>:</label></th><td><input type="radio" name="layout" value="text" <?php echo ($options['layout']=="text") ? "checked" : "";?>><span style="padding: 6px"><?php _e( 'text', 'P2Go' ); ?> </span><input type="radio" name="layout" value="thumbs" <?php echo ($options['layout']=="thumbs") ? "checked" : "";?>><span style="padding: 6px"><?php _e( 'thumbs', 'P2Go' ); ?> </span><input type="radio" name="layout" value="tile" <?php echo ($options['layout']=="tile") ? "checked" : "";?>><span style="padding: 6px"><?php _e( 'tile', 'P2Go' ); ?></span><span><?php _e("description_display", "P2Go"); ?></span></td></tr>';
			form+= '<tr><th scope="row"><label for="query" style="font-weight: 400"><?php _e( 'Query wizard', 'P2Go' ); ?></label></th></tr>';
			form+= '<tr><td> </td><td>'+ createSelect( 'DBField', Array('title','contributor') ) +  createSelect( 'equation', Array( 'like','equal' ) ) + '<input type="text" class="query" name="query"></td></tr>';
			form+= '<tr><td><a href="javascript:extendQuery()" style="font-weight: 400"><?php _e('Add Criteria', 'P2Go'); ?></a></td></tr>';
			form+= '<tr><th scope="row"><label for="language"><?php _e( 'Language', 'P2Go' ); ?>:</label></th><td>' + createSelect( 'language', Array( 'Any Language', 'Nederlands', 'Engels' ) ) + '</td></tr>';
			form+= '<tr class="layoutoption"><th scope="row"><?php _e('accentcolor', 'P2Go'); ?></th><td><input type="color" name="accentcolor"></td></tr>';
			form+= '<tr class="layoutoption"><th scope="row"><?php _e('backgroundcolor', 'P2Go'); ?></th><td><input type="color" name="backgroundcolor"></td></tr>';
			form+= '<tr class="layoutoption"><th scope="row"><?php _e('autoresize', 'P2Go'); ?></th><td><input type="checkbox" name="autoresize"></td></tr>';
			form+= '<tr class="layoutoption"><th scope="row"><?php _e('minimal buttons', 'P2Go'); ?></th><td><input type="checkbox" name="minimalbuttons"></td></tr>';
			form+= '<tr class="layoutoption"><th scope="row"><?php _e('splashscreen','P2Go'); ?></th><td><input type="url" name="splashscreenurl"></td></tr>';
			form+= '<tr class="layoutoption"><th scope="row"><?php _e('display'); ?></th><td>';
			form+= '<input type="radio" name="display" value="1"><span style="padding: 3px"><?php _e( 'display_1', 'P2Go' ); ?> </span>';
			form+= '<input type="radio" name="display" value="2"><span style="padding: 3px"><?php _e( 'display_2', 'P2Go' ); ?> </span>';
			form+= '<input type="radio" name="display" value="3"><span style="padding: 3px"><?php _e( 'display_3', 'P2Go' ); ?> </span></td></tr>';
			form+= '<tr class="layoutoption"><th scope="row">&nbsp;</th><td><input type="radio" name="display" value="4"><span style="padding: 3px"><?php _e( 'display_4', 'P2Go' ); ?> </span>';
			form+= '<input type="radio" name="display" value="5"><span style="padding: 3px"><?php _e( 'display_5', 'P2Go' ); ?> </span>';
			form+= '<input type="radio" name="display" value="6"><span style="padding: 3px"><?php _e( 'display_6', 'P2Go' ); ?> </span>';
			form+= '<input type="radio" name="display" value="7"><span style="padding: 3px"><?php _e( 'display_7', 'P2Go' ); ?> </span>';
			form+= '</td></tr>';
			form+= '<tr><td><input type="button" class="P2GoWizardButton" value="<?php _e( 'Submit', 'P2Go' ); ?>" name="submit" id="p2go-wizard-submit"><input type="button" class="P2GoWizardButton" value="<?php _e( 'Clear', 'P2Go' ); ?>" name="clear" id="p2go-wizard-clear"><input type="button" class="LayoutOptions" value="layout"></td></tr>';
			form+= '</tbody></table>';
			
			wizard = jQuery(form);
			wizard.appendTo('body').hide();
			wizard.find('.layoutoption').hide();
			wizard.find('span').css('overflow', 'scroll');
			
				//create Some values for when the request fails.
			var errorHTML = '<option value="title"><?php _e("Title", "P2Go"); ?></option>';
			errorHTML+= '<option value="contributor"><?php _e("Contributor", "P2Go"); ?></option>';//HTML values to have some values in the select when the request fails
			var successHTML = "";//Used to create a list when the request is successful
			if(KeyList.success)
			{
				for(i=0;i< KeyList.response.length;i++)
				{
					successHTML+= '<option value="' + KeyList.response[i].value + '">' + KeyList.response[i].key + '</option>';
				}
				jQuery('[name^="DBFiel"]').html(successHTML);
			} else {
				jQuery('[name^="DBFiel"]').html(errorHTML);
			}
			
			wizard.find('.LayoutOptions').on('click', function() {
				jQuery('.layoutoption').toggle();
			});
			
			wizard.find('#p2go-wizard-submit').on('click', function() {
				formcontent = '[Presentations2Go queryId=';
				jQuery('.query').each(function (index) {
			
					
					//it is the first value so set it as value for query
					if(parseInt(index) === 0) {
						append_query( jQuery( this ).val() , true);
					} else {
						//the query is partially done so append to it.
						append_query( jQuery( this ).val(), false);
					}
				});
				//save the query to the database if it did not exist yet.
				saveQuery(query);
				if( response.succes) {
				//it succesfully saved the query to the database.
					getQueryId(query); //receive the queryID to add to the shortcode.
					//create the shortcode
					formcontent+= response.id;
					
					if( supports_html5_placeholder() && !jQuery('[name="server-address"]').val() ){
						formcontent+= ' server=' + jQuery('[name="server-address"]').attr('placeholder');
					} else {
						formcontent+= ' server=' + jQuery('[name="server-address"]').val();
					}
					
					if( supports_html5_placeholder() && !jQuery('[name="secret-key"]').val() ){
						formcontent+= ' key=' + jQuery('[name="secret-key"]').attr('placeholder');
					} else {
						formcontent+= ' key=' + jQuery('[name="secret-key"]').val();
					}
					
					if( supports_html5_placeholder() && !jQuery('[name="expiration"]').val() ){
						formcontent+= ' expiration=' + jQuery('[name="expiration"]').attr('placeholder');
					} else {
						formcontent+= ' expiration=' + jQuery('[name="expiration"]').val();
					}
					
					if( supports_html5_placeholder() && !jQuery('[name="totalPosts"]').val() ){
						formcontent+= ' total=' + jQuery('[name="totalPosts"]').attr('placeholder');
					} else {
						formcontent+= ' total=' + jQuery('[name="totalPosts"]').val();
					}
					
					formcontent+= ' automaticResize=' + jQuery('[name="autoresize"]').is(':checked');
					
					formcontent+= ' background=' + jQuery('[name="backgroundcolor"]').val().substr(1,6);;
										
					formcontent+= ' accentcolor=' + jQuery('[name="accentcolor"]').val().substr(1,6);
					
					formcontent+= ' showminimalbuttons=' + jQuery('[name="minimalbuttons"]').is(':checked');
					
					formcontent+= ' splashScreenUrl=' + jQuery('[name="splashScreenUrl"]').val();
					
					formcontent+= ' display=' + jQuery('[name="display"]:checked').val();
					
					formcontent+= ' size=' + jQuery('[name="size"]:checked').val();
					
					formcontent+= ' layout=' + jQuery('[name="layout"]:checked').val() + ']';
					
					//add the shortcode to the Editor
					if(tinymce != null && tinymce.activeEditor != null)
					{
						if(tinymce.activeEditor.insertContent != null) {
							tinymce.activeEditor.insertContent(formcontent);
						} else {
							var currentContent = tinymce.activeEditor.getContent();
							tinymce.activeEditor.setContent(currentContent + formcontent);
						}
					} else {
						QTags.insertContent(formcontent);
					}
					tb_remove();//remove the modal window.
				} else {
					//the query couldn't be saved. Display an error to the user.
					jQuery('#P2GoWizard .form-table').before('<div class="error">Something went terribly wrong.</div>');
					tb_remove();
				}
			});
			
			wizard.find('#p2go-wizard-clear').on( 'click', function() {
				//clear text fields
				jQuery('.form-table tr td input[type="text"]').val('');
				//clear radio buttons
				jQuery('.form-table tr td input[type="radio"]').attr( 'checked', false );
				//remove all rows for queries
				jQuery('.query').parent().parent().remove();
				//reset counter
				countQueryRows = 0;
				//add a single row for queries again.
				extendQuery();
			});
						
			function extendQuery() {
				//insert new row of forms.
				if(countQueryRows === 0) {
					jQuery('.form-table tr td a ').parent().parent().before('<tr><td> </td><td>' + createSelect( 'DBField' + countQueryRows, Array('title','contributor') ) +  createSelect( 'equation' + countQueryRows, Array( 'like','equal' ) ) + '<input type="text" class="query" name="query' + countQueryRows + '"></td></tr>');					
				} else {
					jQuery('.form-table tr td a ').parent().parent().before('<tr><td>' + createSelect( 'appending' + countQueryRows, Array('AND', 'OR') ) + '</td><td>' + createSelect( 'DBField' + countQueryRows, Array('title','contributor') ) +  createSelect( 'equation' + countQueryRows, Array( 'like','equal' ) ) + '<input type="text" class="query" name="query' + countQueryRows + '"></td></tr>');
				}
				
				//fill the new list.
				var errorHTML = '<option value="title"><?php _e("Title", "P2Go"); ?></option>';
				errorHTML+= '<option value="contributor"><?php _e("Contributor", "P2Go"); ?></option>';//HTML values to have some values in the select when the request fails
				var successHTML = "";//Used to create a list when the request is successful
				if(KeyList.success)
				{
					for(i=0;i< KeyList.response.length;i++)
					{
						successHTML+= '<option value="' + KeyList.response[i].value + '">' + KeyList.response[i].key + '</option>';
					}
					jQuery('[name="DBField' + countQueryRows + '"]').html(successHTML);
				} else {
					jQuery('[name="DBField' + countQueryRows + '"]').html(errorHTML);
				}
				
				//add 1 to rows to prevent duplicate names.
				countQueryRows++;
			}
			
			jQuery('body').on('change', '[name^="DBFiel"]', function () {
				var value = jQuery(this).val();
				//zoeken naar field met deze value, kijken of die vocabularies heeft, zoja zet het eerst volgende input veld naar select met opties anders niks doen.
				//look for a field with the according value.
				if(KeyList.success) {
					for(x=0;x < KeyList.response.length; x++)
					{
						if(KeyList.response[x].value == value)
						{
							filter = KeyList.response[x];
						}
					}
					//look if this option has vocabularies
					if(filter.useVocabularies && filter.vocabularies) {
						//is this an added field, if so appeend the name.
						if(parseInt(countQueryRows) > 1) {
							var select = "<select name='query" + (parseInt(countQueryRows) - 1).toString() + "' class='query'>";
						} else {
							var select = "<select name='query' class='query'>";
						}
						//replace the input field with a select containing each item of the vocabularies array.
						for(i=0;i<filter.vocabularies.length;i++)
						{
							select+= "<option value='" + filter.vocabularies[i].key + "'>" + filter.vocabularies[i].value + "</option>";
						}
						select+= "</select>";
						jQuery(this).parent().find('[name^="quer"]').replaceWith(select);
					} else {
						//turn the select in a text input
						jQuery(this).parent().find('[name^="quer"]').replaceWith("<input type='text' name='query' class='query'>");
					}
				}
			});
			//add the button on the screen.
			QTags.addButton('P2GoCode','P2Go', function() { 
				tb_show('Presentations 2Go','#TB_inline?height=707&width=900&inlineId=P2GoWizard&class=thickbox');
				window.setTimeout(function() {
				jQuery('#TB_window').css( {'overflow-y': 'scroll', 'overflow-x': 'scroll' } );
				}, 10);
			});
			</script>
		<?php
		}
	}
}
new P2Goplugin();