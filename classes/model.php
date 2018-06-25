<?php
require_once( WORD_CLOUD_PLUGIN_PATH . '/classes/output.php' );


/**
 * The main model for the Word Cloud plugin.
 * 
 * @package    word-cloud
 * @subpackage classes/model
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('WordCloud_Model') ):
class WordCloud_Model
{
	/**
	 * The only instance of the current model.
	 * @var  WordCloud_Model
	 */	
	private static $instance = null;
	
	/**
	 * The last error saved by the model.
	 * @var  string
	 */	
	public $last_error = null;
		
	
	/**
	 * Private Constructor.  Needed for a Singleton class.
	 */
	protected function __construct() { }
	
	
	/**
	 * Sets up the "children" models used by this model.
	 */
	protected function setup_models()
	{
	}
	

	/**
	 * Get the only instance of this class.
	 * @return  WordCloud_Model  A singleton instance of the model class.
	 */
	public static function get_instance()
	{
		if( self::$instance	=== null )
		{
			self::$instance = new WordCloud_Model();
			self::$instance->setup_models();
		}
		return self::$instance;
	}



//========================================================================================
//========================================================================= Log file =====


	/**
	 * Clear the log.
	 */
	public function clear_log()
	{
		file_put_contents( WORD_CLOUD_LOG_FILE );
	}
	

	/**
	 * Write the username followed by a log line.
	 * @param  string  $username  The user's username.
	 * @param  string  $text  The line of text to insert into the log.
	 * @param  bool  $newline  True if a new line character should be inserted after the line, otherwise False.
	 */
	public function write_to_log( $username = '', $text = '', $newline = true )
	{
		$text = print_r( $text, true );
		if( $newline ) $text .= "\n";
		$text = str_pad( $username, 8, ' ', STR_PAD_RIGHT ).' : '.$text;
		file_put_contents( WORD_CLOUD_LOG_FILE, $text, FILE_APPEND );
	}	



//========================================================================================
//========================================================================== Options =====
	
	
	/**
	 * Get a filtered list of clouds.
	 * @param  int  $offset  The offset of the cloud list.
	 * @param  int  $limit  The maximum number of clouds to return.
	 * @param bool  $get_settings  True if list of clouds should include settings.
	 * @return  arrray  The filtered list of the clouds.
	 */
	public function get_filtered_cloud_list( $offset, $limit, $get_settings = false )
	{
		$cloud_list = $this->get_cloud_list();
		if( count( $cloud_list ) < $offset ) {
			return array();
		}
		
		sort( $cloud_list );
		$cloud_list = array_splice( $cloud_list, $offset, $limit );
		
		if( $get_settings )
		{
			$clouds = array();
			foreach( $cloud_list as $cloud_name )
			{
				$clouds[ $cloud_name ] = $this->get_cloud_settings( $cloud_name );
				$cache = $this->get_cloud_cache( $cloud_name, true );
				$clouds[ $cloud_name ]['cached'] = ( false === $cache ? false : $cache['datetime'] );
			}
			
			$cloud_list = $clouds;
			$clouds = null;
		}
		
		return $cloud_list;
	}
	

	/**
	 * Adds a cloud to the cloud list.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @param  array  $settings  The cloud's settings.
	 * @return  array  The altered settings with any errors.
	 */
	public function add_cloud( $settings )
	{
		$cloud_list = $this->get_cloud_list();
		
		// Check for errors.
		$this->verify_cloud_settings( $settings );
		if( in_array( $settings['name'], $cloud_list ) ) {
			$settings['errors']['name_exists'] = $settings['name'] . ' already exists.';
		}
		
		// Errors were found.
		if( 0 < count( $settings['errors'] ) ) {
			return $settings;
		}
		
		// Add the name to the cloud list.
		$cloud_list[] = $settings['name'];
		$this->update_cloud_list( $cloud_list );
		
		// Add the cloud settings.
		update_option( WORD_CLOUD_SETTINGS . '-' . $settings['name'], $settings );
		return $settings;
	}


	/**
	 * Update a cloud and its settings.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @param  array  $settings  The cloud's settings.
	 * @return  array  The altered settings with any errors.
	 */
	public function update_cloud( $cloud_name, $settings )
	{
		$cloud_list = $this->get_cloud_list();
		
		// Check for errors.
		$this->verify_cloud_settings( $settings );
		if( ! in_array( $cloud_name, $cloud_list ) ) {
			$settings['errors']['name_exists'] = $settings['name'] . ' does not exist.';
		} elseif( $cloud_name !== $settings['name'] && 
		          in_array( $settings['name'], $cloud_list ) ) {
			$settings['errors']['name_exists'] = $settings['name'] . ' already exists.';
		}
		
		// Errors were found.
		if( 0 < count( $settings['errors'] ) ) {
			return $settings;
		}
		
		// Update the cloud settings.
		return $this->update_cloud_settings( $cloud_name, $settings );
	}
	
	
	/**
	 * Remove a cloud name, settings, and cache.
	 * @param  string  $cloud_name  The name of the cloud.
	 */
	public function remove_cloud( $cloud_name )
	{
		$this->remove_from_cloud_list( $cloud_name );
		$this->remove_cloud_settings( $cloud_name );
		$this->remove_cloud_cache( $cloud_name );
	}
	
	
	/**
	 * Get the number of clouds.
	 * @return  int  The number of clouds.
	 */
	public function get_cloud_count()
	{
		return count( $this->get_cloud_list() );
	}
	
	
	/**
	 * Determines if a cloud name is in the cloud list.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @return  bool  True if the name is in the cloud list, otherwise False.
	 */
	public function is_existing_cloud_name( $cloud_name )
	{
		return in_array( $cloud_name, $this->get_cloud_list() );
	}
	
	
	/**
	 * Get a list of cloud names.
	 * @return  array  A list of cloud names.
	 */
	public function get_cloud_list()
	{
		return get_option( WORD_CLOUD_LIST, array() );
	}
	
	
	/**
	 * Update the list of cloud names.
	 * @param  array  $list  A list of cloud names.
	 */
	protected function update_cloud_list( $list )
	{
		update_option( WORD_CLOUD_LIST, $list );
	}
	
	
	/**
	 * Add a cloud name to the cloud list.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @return  bool  True if cloud name existed and was removed, otherwise False.
	 */
	protected function add_to_cloud_list( $cloud_name )
	{
		$cloud_list = $this->get_cloud_list();
		
		if( in_array( $cloud_name, $cloud_list ) ) {
			return false;
		}
		
		$cloud_list[] = $cloud_name;
		$this->update_cloud_list( $cloud_list );
		return true;
	}
	
	
	/**
	 * Remove a cloud name from the cloud list.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @return  bool  True if the cloud exists and was removed, otherwise False.
	 */
	protected function remove_from_cloud_list( $cloud_name )
	{
		$cloud_list = $this->get_cloud_list();
		
		$index = -1;
		for( $i = 0; $i < count( $cloud_list ); $i++ ) {
			if( $cloud_list[ $i ] === $cloud_name ) {
				$index = $i;
				break;
			}
		}
		
		if( -1 === $index ) {
			return false;
		}
		
		array_splice( $cloud_list, $index, 1 );
		$this->update_cloud_list( $cloud_list );
		return true;
	}
	
		/**
	 * 
	 */
	public function get_all_clouds( $process = false )
	{
		$clouds = get_option( WORD_CLOUD_LIST, array() );
		
		if( ! $clouds || ! is_array( $clouds ) ) {
			return array();
		}
		
		if( $process ) {
			foreach( $clouds as &$cloud ) {
				$cloud = $this->get_cloud_settings( $cloud );
			}
		}
		
		return $clouds;
	}
	
	
	
	/**
	 * Get a list of cloud names.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @return  array  The settings for the cloud.
	 */
	public function get_cloud_settings( $cloud_name )
	{
		if( ! $this->is_existing_cloud_name( $cloud_name ) ) {
			return false;
		}
	
		return $this->merge_cloud_settings(
			get_option( WORD_CLOUD_SETTINGS . '-' . $cloud_name, array() )
		);
	}
	
	
	/**
	 * Update the settings for a cloud.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @param  array  $settings  The new settings.
	 * @return  array  The modified settings with errors.
	 */
	protected function update_cloud_settings( $cloud_name, $settings )
	{
		$cloud_settings = $this->get_cloud_settings( $cloud_name );
		if( $cloud_settings )
		{
			$check = array( 
				'post_types', 'taxonomies', 'filterby_taxonomy', 'filterby_terms' 
			);
			foreach( $check as $c )
			{
				if( $cloud_settings[ $c ] != $settings[ $c ] ) {
					$this->remove_cloud_cache( $cloud_name );
					break;
				}
			}
		}
		
		if( $cloud_name !== $settings['name'] ) {
			$this->remove_from_cloud_list( $cloud_name );
			$this->add_to_cloud_list( $settings['name'] );
			$this->rename_cloud_settings( $cloud_name, $settings['name'] );
			$this->rename_cloud_cache( $cloud_name, $settings['name'] );
		}
		
		update_option( WORD_CLOUD_SETTINGS . '-' . $settings['name'], $settings );
		return $settings;
	}
	
	
	/**
	 * Remove a cloud's settings.
	 * @param  string  $cloud_name  The name of the cloud.
	 */
	protected function remove_cloud_settings( $cloud_name )
	{
		delete_option( WORD_CLOUD_SETTINGS . '-' . $cloud_name );
	}
	
	
	/**
	 * Renames a cloud settings to the new cloud name.
	 * @param  string  $old_cloud_name  The cloud's old name.
	 * @param  string  $new_cloud_name  The cloud's new name.
	 */
	protected function rename_cloud_settings( $old_cloud_name, $new_cloud_name )
	{
		$settings = $this->get_cloud_settings( $old_cloud_name );
		$this->remove_cloud_settings( $old_cloud_name );
		update_option( $new_cloud_name, $settings );
	}
	
	
	/**
	 * Gets the cache for the cloud.  If validate is true, then will also try to determine
	 * if the found cache is valid.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @param  bool  $validate  True if validation of cache is needed, otherwise False.
	 * @return  array|bool  The cache for the cloud, or False if error occurs.
	 */
	public function get_cloud_cache( $cloud_name, $validate = false )
	{
		if( ! $this->is_existing_cloud_name( $cloud_name ) ) {
			return false;
		}
		
		$cloud_cache = get_option( WORD_CLOUD_CACHE . '-' . $cloud_name, false );
		if( ! $validate ) {
			return $cloud_cache;
		}

		$cloud_settings = $this->get_cloud_settings( $cloud_name );
		if( ! $cloud_settings ) {
			return false;
		}
		
		if( $this->is_valid_cache( $cloud_settings, $cloud_cache ) ) {
			return $cloud_cache;
		}
		
		return false;
	}
	
	
	/**
	 * Determines if the cloud cache is valid based on the cloud settings.
	 * @param  array  $cloud_settings  The current cloud settings.
	 * @param  array  $cloud_cache  The current cloud cache.
	 * @return  bool  True if the cache is valid, otherwise False.
	 */
	protected function is_valid_cache( $cloud_settings, $cloud_cache )
	{
		$check = array( 
			'post_types', 'taxonomies', 'filterby_taxonomy', 'filterby_terms' 
		);
		foreach( $check as $c )
		{
			if( $cloud_settings[ $c ] != $cloud_cache['settings'][ $c ] ) {
				return false;
			}
		}
		
		if( empty( $cloud_cache['datetime'] ) ) {
			return false;
		}
		
		return $cloud_cache;
	}
	
	
	/**
	 * Update the cache for a cloud.
	 * @param  string  $cloud  The name of the cloud.
	 * @param  array  $cache  The cache array.
	 */
	protected function update_cloud_cache( $cloud_name, $cache )
	{
		update_option( WORD_CLOUD_CACHE . '-' . $cloud_name, $cache );
	}
	

	/**
	 * Remove the cache for a cloud.
	 * @param  string  $cloud_name  The name of the cloud.
	 */
	public function remove_cloud_cache( $cloud_name )
	{
		return delete_option( WORD_CLOUD_CACHE . '-' . $cloud_name );
	}
	

	/**
	 * Renames the cache for a cloud.
	 * @param  string  $old_cloud_name  The old name of the cloud.
	 * @param  string  $new_cloud_name  The new name of the cloud.
	 */
	public function rename_cloud_cache( $old_cloud_name, $new_cloud_name )
	{
		if( ! $this->is_existing_cloud_name( $cloud_name ) ) {
			return false;
		}
		
		$cloud_cache = $this->get_cloud_cache( $old_cloud_name );
		if( false === $cloud_cache ) {
			return false;
		}
		
		$this->remove_cloud_cache( $old_cloud_name );
		$this->update_cloud_cache( $new_cloud_name, $cloud_cache );
	}
	
	
	/**
	 * Initialize the cache default values for a cloud.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @return  array|bool  The list of terms for the cache, or False on error.
	 */
	public function initialize_cloud_cache( $cloud_name )
	{
		$cloud_settings = $this->get_cloud_settings( $cloud_name );
		if( false === $cloud_settings ) {
			$this->last_error = 'Cloud does not exist.';
			return false;
		}
		
		$all_terms = array();
		$tax_query = array();
		
		if( 'none' != $cloud_settings['filterby_taxonomy'] )
		{
			$tax_query = array(
				array(
					'taxonomy' 	=> $cloud_settings['filterby_taxonomy'],
					'terms' 	=> explode( ';', $cloud_settings['filterby_terms'] ),
					'field' 	=> 'slug',
					'relation'  => 'OR',
				)
			);
		}
		
		$q = new WP_Query(
			array(
				'post_type' => $cloud_settings['post_types'],
				'tax_query' => $tax_query,
				'posts_per_page' => -1,
			)
		);
		
		while( $q->have_posts() )
		{
			$q->the_post();
			$post_terms = array();
			
			foreach( $cloud_settings['taxonomies'] as $tax_name )
			{
				$t = get_the_terms( get_the_ID(), $tax_name );
				if( is_array( $t ) ) {
					$t = array_map( function( $v ) { return $v->term_id; }, $t );
					$post_terms = array_merge( $post_terms, $t );
				}
			}

			$all_terms = array_merge( $all_terms, $post_terms );
			$post_terms = null;
		}

		wp_reset_postdata();			
		
		// The term ids should be a unique list.
		$all_terms = array_unique( $all_terms );		
		
		// Set the default cache values.
		$cache = array(
			'settings' => array(
				'post_types'        => $cloud_settings['post_types'],
				'taxonomies'        => $cloud_settings['taxonomies'],
				'filterby_taxonomy' => $cloud_settings['filterby_taxonomy'],
				'filterby_terms'    => $cloud_settings['filterby_terms'],
			),
			'datetime' => null,
			'terms' => array(),
		);
		
		// Update the cache for the cloud.
		$this->update_cloud_cache( $cloud_name, $cache );
		
		// Return the list of terms for the cache.
		return $all_terms;
	}
	
	
	/**
	 * Update/add a term to an existing cloud cache.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @param  int  $term_id  The id of the term.
	 * @return  int|bool  The number of posts associated with the term on success,
	 *                    otherwise False.
	 */
	public function update_cache_term( $cloud_name, $term_id )
	{
		// Check if cloud exists.
		if( ! $this->is_existing_cloud_name( $cloud_name ) ) {
			$this->last_error = 'Cloud does not exist.';
			return false;
		}
		
		// Get the cache.
		$cloud_cache = $this->get_cloud_cache( $cloud_name );
		if( false === $cloud_cache ) {
			$this->last_error = 'Cache does not exist.';
			return false;
		}
		
		// Get the term.
		$term = get_term_by( 'term_taxonomy_id', $term_id );
		if( ! $term ) {
			$this->last_error = 'Invalid term id.';
			return false;
		}
		
		// Get the cache settings.
		$post_types = $cloud_cache['settings']['post_types'];
		$taxonomies = $cloud_cache['settings']['taxonomies'];
		$filterby_taxonomy = $cloud_cache['settings']['filterby_taxonomy'];
		$filterby_terms = $cloud_cache['settings']['filterby_terms'];
		
		// Generate the tax_query based on the cache settings.
		$tax_query = array();
		
		if( 'none' != $filterby_taxonomy )
		{
			$tax_query = array(
				'relation'  => 'AND',
				array(
					'taxonomy' 	=> $filterby_taxonomy,
					'terms' 	=> explode( ';', $filterby_terms ),
					'field' 	=> 'slug',
					'relation'  => 'OR',
				),
			);
		}
		
		$tax_query[] = array(
			'taxonomy'  => $term->taxonomy,
			'terms'     => $term_id,
			'field'     => 'id',
		);
		
		// Get all posts that match the filtered term.
		$q = new WP_Query(
			array(
				'post_type' => $post_types,
				'tax_query' => $tax_query,
				'posts_per_page' => -1,
			)
		);
		
		// Store the number of found posts that match the filtered term.
		$cloud_cache['terms'][ $term_id ] = $q->found_posts;
		$this->update_cloud_cache( $cloud_name, $cloud_cache );
		
		return $q->found_posts;
	}


	/**
	 * Sort the terms and set the datetime for a cache.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @return  bool  True if completed successfully, otherwise False.
	 */
	public function update_cache_complete( $cloud_name )
	{
		// Check if cloud exists.
		if( ! $this->is_existing_cloud_name( $cloud_name ) ) {
			$this->last_error = 'Cloud does not exist.';
			return false;
		}
		
		// Get the cache.
		$cloud_cache = $this->get_cloud_cache( $cloud_name );
		if( false === $cloud_cache ) {
			$this->last_error = 'Cache does not exist.';
			return false;
		}
		
		// Sort term list.
		arsort( $cloud_cache['terms'] );
		
		// Set the complete datetime.
		$cloud_cache['datetime'] = date( 'Y-m-d H:i:s' );
		
		// Update the cache.
		$this->update_cloud_cache( $cloud_name, $cloud_cache );
	}
	
	
	/**
	 * Verify that the settings for the cloud are valid.  This does not check if the
	 * name conflicts with another existing cloud.  This is done in the add_cloud and
	 * update_cloud functions.
	 * @param  array  $settings  The settings for a cloud.
	 */
	protected function verify_cloud_settings( &$settings )
	{
		$settings['errors'] = array();
		
		// Make sure name is sanitized.
		$settings['name'] = sanitize_title( $settings['name'] );
		
		// Make sure name is not empty.
		if( empty( $settings['name'] ) ) {
			$settings['errors']['name'] = 'Please specify a cloud name.';
		}
	}
	
	
	/**
	 * Merge a cloud's settings with the default settings to ensure that all settings
	 * are set.
	 * @param  array  $settings  The settings for the cloud.
	 * @return  array  The complete, merged settings for a cloud.
	 */
	public function merge_cloud_settings( $settings )
	{
		if( ! is_array( $settings ) ) {
			return $this->get_default_cloud_settings();
		}
		
		return array_merge( $this->get_default_cloud_settings(), $settings );
	}
	
	
	/**
	 * Get the default settings for the cloud.
	 * @return  array  The default settings.
	 */
	public function get_default_cloud_settings()
	{
		$defaults = array();
		
		// name
		$defaults['name'] = '';
		
		// title
		$defaults['title'] = '';
		
		// post types
		$defaults['post_types'] = array( 'post' );
		
		// taxonomy types
		$defaults['taxonomies'] = array( 'post_tag' );
		
		// filter by
		$defaults['filterby_taxonomy'] = 'none';
		$defaults['filterby_terms'] = '';
		
		// minimum count
		$defaults['minimum_count'] = 1;
		
		// max words (# or none)
		$defaults['maximum_words'] = 250;
		
		// words orientation
		$defaults['orientation'] = 'horizontal';
		
		// font_family
		$defaults['font_family'] = 'Arial';
		
		// font-size (range or single)
		$defaults['font_size_type'] = 'range';
		$defaults['font_size_range'] = array( 'start' => 10, 'end' => 100 );
		$defaults['font_size_single'] = 60;
		
		// color (spanning, single color, none)
		$defaults['font_color_type'] = 'none';
		$defaults['font_color_single'] = '';
		$defaults['font_color_spanning'] = '';
		
		// canvas size (height and width)
		$defaults['canvas_size'] = array( 'width' => 960, 'height' => 420 );
		
		$defaults['hide_debug'] = 'yes';
		
		return $defaults;
	}
	
	
	/**
	 * Get the options for the admin interface.
	 * @return  array  A list of options.
	 */
	public function get_options()
	{
		$options = array();
		
		$options['all_post_types'] = get_post_types( array(), 'objects' );
		$options['exclude_post_types'] = array( 'attachment', 'revision', 'nav-menu-item' );
		
		$options['all_taxonomies'] = get_taxonomies( array(), 'objects' );
		$options['exclude_taxonomies'] = array( 'nav-menu', 'link-category', 'post-format' );
		
		return $options;
	}
	
	
	/**
	 * Prints the main admin interface for add or editing a cloud.
	 * @param  string  $cloud_name  The name of the cloud.
	 * @param  array|null  $cloud_settings  The cloud's current settings.
	 */
	public function print_edit_form( $cloud_name, $cloud_settings = null )
	{
		extract( $this->merge_cloud_settings( $cloud_settings ) );
		extract( $this->get_options() );
		?>
		<input type="hidden" name="name" value="<?php echo $cloud_name; ?>" />
		
		<p>
		<label for="txt_cloud_settings_name"><?php _e( 'Name:' ); ?></label>
		<br/>
		<input type="textbox" name="cloud_settings[name]" value="<?php echo esc_attr( $name ); ?>" />
		<br/>
		</p>
		
		<p>
		<label for="cloud_settings_title"><?php _e( 'Title:' ); ?></label> 
		<br/>
		<input id="cloud_settings_title" name="cloud_settings[title]" type="text" value="<?php echo esc_attr( $title ); ?>" class="widefat">
		<br/>
		</p>
	
		<p>
		<label for="cloud_settings_post_type"><?php _e( 'Post Type:' ); ?></label> 
		<br/>
		<?php foreach( $all_post_types as $pt ): ?>
			<?php
			if( in_array( $pt->name, $exclude_post_types ) ) {
				continue;
			} 
			?>
			<input type="checkbox" name="cloud_settings[post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $post_types ) ); ?> />
			<?php echo $pt->label; ?>
			<br/>
		<?php endforeach; ?>
		</p>

		<p>
		<label for="cloud_settings_taxonomies"><?php _e( 'Taxonomies:' ); ?></label>
		<br/>
		<?php foreach( $all_taxonomies as $tax ): ?>
			<?php if( in_array($tax->name, $exclude_taxonomies) ) continue; ?>
			<input type="checkbox" name="cloud_settings[taxonomies][]" value="<?php echo esc_attr( $tax->name ); ?>" <?php checked( in_array( $tax->name, $taxonomies ) ); ?> />
			<?php echo $tax->label; ?>
			<br/>
		<?php endforeach; ?>
		</p>
		
		<p>
		<label for="cloud_settings_filterby_taxonomy"><?php _e( 'Filter By:' ); ?></label>
		<br/>
		<input type="radio" name="cloud_settings[filterby_taxonomy]" value="none" <?php checked( 'none', $filterby_taxonomy ); ?> />
		None
		<br/>
		<?php foreach( $all_taxonomies as $tax ): ?>
			<?php if( in_array($tax->name, $exclude_taxonomies) ) continue; ?>
			<input type="radio" name="cloud_settings[filterby_taxonomy]" value="<?php echo esc_attr( $tax->name ); ?>" <?php checked( $tax->name, $filterby_taxonomy ); ?> />
			<?php echo $tax->label; ?>
			<br/>
		<?php endforeach; ?>
		<label for="cloud_settings_filterby_terms"><?php _e( 'Terms:' ); ?></label>
		<br/>
		<input type="text" name="cloud_settings[filterby_terms]" value="<?php echo esc_attr( $filterby_terms ); ?>" />
		</p>

		<p>
		<label for="cloud_settings_minimum_count"><?php _e( 'Minimum Post Count:' ); ?></label> 
		<input id="cloud_settings_minimum_count" name="cloud_settings[minimum_count]" type="text" value="<?php echo esc_attr( $minimum_count ); ?>">
		</p>
	
		<p>
		<label for="cloud_settings_maximum_words"><?php _e( 'Maximum Words:' ); ?></label> 
		<input id="cloud_settings_maximum_words" name="cloud_settings[maximum_words]" type="text" value="<?php echo esc_attr( $maximum_words ); ?>">
		</p>

		<p>
		<label for="cloud_settings_orientation"><?php _e( 'Words Orientation:' ); ?></label> 
		<br/>
		<input type="radio" name="cloud_settings[orientation]" value="horizontal" <?php checked( $orientation, 'horizontal' ); ?> />
		Horizontal
		<br/>
		<input type="radio" name="cloud_settings[orientation]" value="vertical" <?php checked( $orientation, 'vertical' ); ?> />
		Vertical
		<br/>
		<input type="radio" name="cloud_settings[orientation]" value="mixed" <?php checked( $orientation, 'mixed' ); ?> />
		Mixed Horizontal and Vertical
		<br/>
		<input type="radio" name="cloud_settings[orientation]" value="mostly-horizontal" <?php checked( $orientation, 'mostly-horizontal' ); ?> />
		Mostly Horizontal
		<br/>
		<input type="radio" name="cloud_settings[orientation]" value="mostly-vertical" <?php checked( $orientation, 'mostly-vertical' ); ?> />
		Mostly Vertical
		</p>

		<p>
		<label for="cloud_settings_font_family"><?php _e( 'Font Family:' ); ?></label> 
		<input class="widefat" id="cloud_settings_font_family" name="cloud_settings[font_family]" type="text" value="<?php echo esc_attr( $font_family ); ?>" class="widefat">
		</p>

		<p>
		<label for="cloud_settings_font_size"><?php _e( 'Font Size:' ); ?></label> 
		<br/>
		<input type="radio" name="cloud_settings[font_size_type]" value="single" <?php checked( $font_size_type, 'single' ); ?> />
			Single: 
			<input id="cloud_settings_font_size_single" name="cloud_settings[font_size_single]" type="text" value="<?php echo esc_attr( $font_size_single ); ?>" class="widefat">
		<br/>
		<input type="radio" name="cloud_settings[font_size_type]" value="range" <?php checked( $font_size_type, 'range' ); ?> />
			Range: 
			<input id="cloud_settings_font_size_range_start" name="cloud_settings[font_size_range][start]" type="text" value="<?php echo esc_attr( $font_size_range['start'] ); ?>" class="widefat">
			<input id="cloud_settings_font_size_range_end" name="cloud_settings[font_size_range][end]" type="text" value="<?php echo esc_attr( $font_size_range['end'] ); ?>" class="widefat">
		</p>		

		<p>
		<label for="cloud_settings_font_size"><?php _e( 'Font Color:' ); ?></label> 
		<br/>
		<input type="radio" name="cloud_settings[font_color_type]" value="none" <?php checked( $font_color_type, 'none' ); ?> />
		None
		<br/>
		<input type="radio" name="cloud_settings[font_color_type]" value="single" <?php checked( $font_color_type, 'single' ); ?> />
		Single:
			<input id="cloud_settings_font_color_single" name="cloud_settings[font_color_single]" type="text" value="<?php echo esc_attr( $font_color_single ); ?>" class="widefat">
		<br/>
		<input type="radio" name="cloud_settings[font_color_type]" value="spanning" <?php checked( $font_color_type, 'spanning' ); ?> />
		Spanning:
			<input class="widefat" id="cloud_settings_font_color_spanning" name="cloud_settings[font_color_spanning]" type="text" value="<?php echo esc_attr( $font_color_spanning ); ?>" class="widefat">
		</p>

		<p>
		<label for="cloud_settings_canvas_size"><?php _e( 'Canvas Size:' ); ?></label> 
		<input id="cloud_settings_canvas_size_width" name="cloud_settings[canvas_size][width]" type="text" value="<?php echo esc_attr( $canvas_size['width'] ); ?>" class="widefat">
		<input id="cloud_settings_canvas_size_height" name="cloud_settings[canvas_size][height]" type="text" value="<?php echo esc_attr( $canvas_size['height'] ); ?>" class="widefat">
		</p>

		<p>
		<input type="hidden" name="cloud_settings[hide_debug]" value="no" />
		<input type="checkbox" id="cloud_settings_hide_debug" name="cloud_settings[hide_debug]" value="yes" <?php checked( $hide_debug, 'yes' ); ?> />
		<label for="cloud_settings_hide_debug">Hide Debug Data</label>
		</p>
		
		<?php
	}
	

	/**
	 * Cache all clouds.  **Used for CRON job.**
	 */
	public function cache_all_clouds()
	{
		$output = WordCloud_Output::get_instance();
		$output->include_datetime = true;
		
		$cloud_list = $this->get_cloud_list();
		$total = count( $cloud_list );
		
		$output->write_line( 'Start caching ' . $total . ' clouds.' );
		$output->write_line();
		
		$count = 1;
		foreach( $cloud_list as $cloud_name ) {
			$output->write_line( 'Cloud ' . $count . ' of ' . $total . ': ' . $cloud_name );
			$this->cache_cloud( $cloud_name );
			$count++;
		}

		$output->write_line( 'Completed caching ' . count( $cloud_list ) . ' clouds.' );
		$output->write_line();
	}
	
	
	/**
	 * Cache a single cloud.  **Used for CRON job.**
	 * @param  string  $cloud_name  The name of the cloud.
	 */
	public function cache_cloud( $cloud_name )
	{
		$output = WordCloud_Output::get_instance();
		$output->include_datetime = true;
		
		$output->write_line( 'Start caching cloud: ' . $cloud_name );
		$terms = $this->initialize_cloud_cache( $cloud_name );
		if( ! $terms ) {
			$output->write_line( 'ERROR: ' . $this->last_error );
			$output->write_line();
			return false;
		}
		
		$count = 1;
		$total = count( $terms );
		foreach( $terms as $term_id )
		{	
			$output->write_line( 'Caching term ' . $count . ' of ' . $total . ': ' . $term_id );
			$status = $this->update_cache_term( $cloud_name, $term_id );
			if( ! $status ) {
				$output->write_line( 'ERROR: ' . $this->last_error );
				$output->write_line();
				return false;
			}
			$count++;
		}

		$output->write_line( 'Completing caching.' );
		$this->update_cache_complete( $cloud_name );
		
		$output->write_line( 'Completed caching cloud: ' . $cloud_name );
		$output->write_line();
	}
	
} // class WordCloud_Model
endif; // if( !class_exists('WordCloud_Model') ):

