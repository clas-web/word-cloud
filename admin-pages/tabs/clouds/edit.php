<?php
/**
 * Controls the admin page "Cloud" when in edit user mode.
 * 
 * @package    word-cloud
 * @subpackage admin-pages/tabs/users
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('WordCloud_CloudsEditTabAdminPage') ):
class WordCloud_CloudsEditTabAdminPage extends APL_TabAdminPage
{
	/**
	 * The main model for the Word Cloud.
	 * @var  WordCloud_Model
	 */	
	private $model = null;
	
	
	/**
	 * Controller.
	 */
	public function __construct(
		$parent,
		$name = 'edit', 
		$tab_title = 'Edit', 
		$page_title = 'Edit Cloud' )
	{
		parent::__construct( $parent, $name, $tab_title, $page_title );
		$this->model = WordCloud_Model::get_instance();
		$this->display_tab = false;
	}
	
	
	/**
	 * Enqueues all the scripts or styles needed for the admin page. 
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script( 'word-cloud-cache-terms', WORD_CLOUD_PLUGIN_URL . '/admin-pages/scripts/clouds.js', array( 'jquery' ) );
	}
	
	
	/**
	 * Processes the current admin page.
	 */
	public function process()
	{
		if( empty( $_REQUEST['action'] ) ) {
			return;
		}
		
		switch( $_REQUEST['action'] )
		{
			case 'update':
				if( isset( $_REQUEST['cloud_settings'] ) ) {
					$this->update_cloud( $_REQUEST['name'], $_REQUEST['cloud_settings'] );
				}
				break;
		}
	}
	
	
	/**
	 * Update the cloud settings.
	 * @param  string  $name  The original name of the cloud.
	 * @param  array  $cloud_settings  The new settings.
	 */
	protected function update_cloud( $name, $cloud_settings )
	{
		$cloud = $this->model->update_cloud( $name, $cloud_settings );
		if( 0 < count( $cloud['errors'] ) ) {
			foreach( $cloud['errors'] as $k => $v ) {
				$this->add_error( $k . ': ' . $v, true );
			}
			$_SESSION['cloud_settings'] = $cloud;
		}
		else {
			$this->add_notice( 'Cloud updated.', true );
			$name = $_REQUEST['cloud_settings']['name'];
		}
		
		wp_redirect( 
			$this->get_page_url( 
				array( 
					'tab' => 'edit', 
					'name' => $name,
				) 
			) 
		);
		exit;
	}
		

	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		?>
		<a href="<?php echo esc_attr( $this->get_page_url( array( 'tab' => 'list' ) ) ); ?>">
			Return to List
		</a>
		<?php
		
		// Determine if the name of the cloud is set.
		if( empty( $_REQUEST['name'] ) ) {
			?><p class="no-name">No name provided.</p><?php
			return;
		}
		
		// Get the cloud settings.
		if( ! empty( $_SESSION['cloud_settings'] ) ) {
			$cloud_settings = $_SESSION['cloud_settings'];
			unset( $_SESSION['cloud_settings'] );
		} else {
			$cloud_settings = $this->model->get_cloud_settings( $_REQUEST['name'] );
		}
		
		// No cloud found.
		if( ! $cloud_settings ) {
			?><p class="no-cloud">The name does not match a current cloud.</p><?php
			return;
		}
		
		// Display the update cloud form.
		$this->form_start( 'update', null, 'update', array( 'name' => $_REQUEST['name'] ) );
			submit_button( 'Update' );
			$this->model->print_edit_form( $_REQUEST['name'], $cloud_settings );
			submit_button( 'Update' );
		$this->form_end();
		
		?>
		<div id="cache-terms-last-update">
			<?php
			$cloud_cache = $this->model->get_cloud_cache( $_REQUEST['name'], true );
			if( ! $cloud_cache ) {
				echo 'No complete cache found.';
			} else {
				echo $cloud_cache['datetime'];
			}
			?>
		</div>
		<?php
		
		$this->form_start_get( 'cache-post-count', null, 'cache-post-count' );
			?><input type="hidden" name="cloud_name" value="<?php echo esc_attr( $_REQUEST['name'] ); ?>" /><?php
			$this->create_ajax_submit_button(
				'Cache Post Count',
				'cache-all-terms',
				null,
				null,
				'cache_all_terms_start',
				'cache_all_terms_end',
				'cache_all_terms_loop_start',
				'cache_all_terms_loop_end'
			);
		$this->form_end();
		?>
		<div id="cache-terms-status"></div>
		<div id="cache-terms-substatus"></div>
		<div id="cache-terms-results"></div>
		<?php
		
		apl_print( $cloud_cache );
	}


	/**
	 * Processes and displays the output of an ajax request.
	 * @param  string  $action  The AJAX action.
	 * @param  array  $input  The AJAX input array.
	 * @param  int  $count  When multiple AJAX calls are made, the current count.
	 * @param  int  $total  When multiple AJAX calls are made, the total count.
	 */
	public function ajax_request( $action, $input, $count, $total )
	{
		switch( $action )
		{
			case 'cache-all-terms':
				// Need the cloud name to start caching.
				if( ! isset( $input['cloud_name'] ) ) {
					$this->ajax_failed( 'No Cloud Name given.' );
					return;
				}
				
				// Initialize the cache and get the term ids to cache.
				$terms = $this->model->initialize_cloud_cache( $input['cloud_name'] );
				$message = ( is_array( $terms ) ? 'OK' : $this->model->last_error );
				$status = ( is_array( $terms ) ? 'success' : 'failure' );
				
				if( is_array( $terms ) )
				{
					// Add final term id that signals the end of the term list.
					array_push( $terms, -1 );
					
					// Format the terms list for return to client JS.
					foreach( $terms as &$term ) {
						$term = array( 'term_id' => $term );
					}
					
					// Set the return values for the client JS.
					$this->ajax_set_items(
						'cache-term',
						array_values( $terms ),
						'cache_term_start',
						'cache_term_end',
						'cache_term_loop_start',
						'cache_term_loop_end'
					);
				}
				
				$this->ajax_set( 'status', $status );
				$this->ajax_set( 'message', $message );
				break;
				
			case 'cache-term':
				// Need the cloud name to cache.
				if( ! isset( $input['cloud_name'] ) ) {
					$this->ajax_failed( 'No Cloud Name given.' );
					return;
				}
				
				// Need the term id to cache.
				if( ! isset( $input['term_id'] ) ) {
					$this->ajax_failed( 'No term id given.' );
					return;
				}
				
				$term_id = intval( $input['term_id'] );
				if( -1 != $term_id )
				{
					// Cache the term id for the cloud.
					$status = $this->model->update_cache_term( $input['cloud_name'], $term_id );
					$message = ( false !== $status ? 'OK' : $this->model->last_error );
					$status = ( false !== $status ? 'success' : 'failure' );
				}
				else
				{
					// Complete the caching for the cloud.
					$status = $this->model->update_cache_complete( $input['cloud_name'] );
					$message = ( false !== $status ? 'OK' : $this->model->last_error );
					$status = ( false !== $status ? 'success' : 'failure' );
				}
				
				$this->ajax_set( 'status', $status );
				$this->ajax_set( 'message', $message );
				break;
				
			default:
				$this->ajax_failed( 'No valid action was given.' );
				break;
		}
	}
	
	
} // class WordCloud_CloudsEditTabAdminPage extends APL_TabAdminPage
endif; // if( !class_exists('WordCloud_CloudsEditTabAdminPage') )

