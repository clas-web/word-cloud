<?php
/**
 * Controls the admin page "Cloud" when in delete cloud mode.
 * 
 * @package    word-cloud
 * @subpackage admin-pages/tabs/clouds
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('WordCloud_CloudsDeleteTabAdminPage') ):
class WordCloud_CloudsDeleteTabAdminPage extends APL_TabAdminPage
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
		$name = 'delete', 
		$tab_title = 'Delete', 
		$page_title = 'Delete Cloud' )
	{
		parent::__construct( $parent, $name, $tab_title, $page_title );
		$this->model = WordCloud_Model::get_instance();
		$this->display_tab = false;
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
			case 'delete':
				if( $_REQUEST['confirm'] == 'Yes' ) {
					$this->delete_cloud( $_REQUEST['name'] );
				}
				
				wp_redirect( 
					$this->get_page_url( 
						array( 
							'tab' => 'list', 
						) 
					) 
				);
				exit;
				break;
		}
	}
	
	
	/**
	 * Delete the cloud.
	 * @param  string  $name  The name of the cloud.
	 */
	protected function delete_cloud( $name )
	{
		$this->model->remove_cloud( $name );
		$this->add_notice( $name . ': Cloud deleted.', true );
		wp_redirect( 
			$this->get_page_url( 
				array( 
					'tab' => 'delete', 
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
		
		if( ! $this->model->is_existing_cloud_name( $_REQUEST['name'] ) ):
			// Cloud does not exist.
			?><p class="no-cloud">The cloud does not exists.</p><?php
			return;
		else:
			// Display the confirm deletion form.
			$this->form_start( 'delete', null, 'delete', array( 'name' => $_REQUEST['name'] ) );
			?>
		
			<input type="hidden" name="cloud_settings[name]" value="<?php echo esc_attr( $_REQUEST['name'] ); ?>" />
			<p>Are you sure you want to delete '<?php echo $_REQUEST['name']; ?>'?</p>
			<input type="submit" name="confirm" value="Yes" />
			<input type="submit" name="confirm" value="NO" />
		
			<?php 
			$this->form_end();
		endif;
	}

} // class WordCloud_CloudsDeleteTabAdminPage extends APL_TabAdminPage
endif; // if( !class_exists('WordCloud_CloudsDeleteTabAdminPage') )

