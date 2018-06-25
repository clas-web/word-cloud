<?php
/**
 * Controls the admin page "Cloud" when in add cloud mode.
 * 
 * @package    word-cloud
 * @subpackage admin-pages/tabs/clouds
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('WordCloud_CloudsAddTabAdminPage') ):
class WordCloud_CloudsAddTabAdminPage extends APL_TabAdminPage
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
		$name = 'add', 
		$tab_title = 'Add', 
		$page_title = 'Add Cloud' )
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
			case 'add':
				if( isset( $_REQUEST['cloud_settings'] ) ) {
					$this->add_cloud( $_REQUEST['cloud_settings'] );
				}
				break;
		}
	}	
	
	
	/**
	 * Attempt to add the cloud, then redirect.
	 * @param  array  $cloud_settings  The entered cloud settings.
	 */
	protected function add_cloud( $cloud_settings )
	{
		apl_print( $cloud_settings );
		$cloud = $this->model->add_cloud( $cloud_settings );
		if( 0 < count( $cloud['errors'] ) )
		{
			foreach( $cloud['errors'] as $k => $v ) {
				$this->add_error( $k . ': ' . $v, true );
			}
			
			$_SESSION['cloud_settings'] = $cloud;
			
			wp_redirect( 
				$this->get_page_url( 
					array( 
						'tab' => 'add', 
					) 
				) 
			);
			exit;
		}
		
		$this->page->add_notice( 'Cloud added.', true );
		$this->model->cache_cloud($cloud['name']);

		wp_redirect( 
			$this->get_page_url( 
				array( 
					'tab' => 'edit', 
					'name' => $cloud['name'], 
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

		// Get the cloud settings.
		if( ! empty( $_SESSION['cloud_settings'] ) ) {
			$cloud = $_SESSION['cloud_settings'];
			unset( $_SESSION['cloud_settings'] );
		} else {
			$cloud = null;
		}
		
		// Display the add cloud form.
		$this->form_start( 'add', null, 'add' );
			submit_button( 'Add' );
			$this->model->print_edit_form( '', $cloud );
			submit_button( 'Add' );
		$this->form_end();
	}

} // class WordCloud_CloudsAddTabAdminPage extends APL_TabAdminPage
endif; // if( !class_exists('WordCloud_CloudsAddTabAdminPage') )

