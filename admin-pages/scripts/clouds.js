

jQuery(document).ready(
	function() {
	}
);


//========================================================================================
//================================================================== Cache All Terms =====


/**
 * Start the processing of all users.
 * @param  array  settings  The AJAX buttons settings.
 */
function cache_all_terms_start( settings )
{
	jQuery('#cache-terms-status').html( 'Started caching terms.' );
	jQuery('#cache-terms-substatus')
		.removeClass('error')
		.html( '&nbsp;' );
	jQuery('#cache-terms-results').html( '' );
	jQuery('.apl-ajax-button').prop( 'disabled', true );
}


/**
 * Start the processing of all Terms.
 * @param  array  settings  The AJAX buttons settings.
 */
function cache_all_terms_end( settings )
{
	jQuery('#cache-terms-status').html( 'Done processing terms.' );
	jQuery('.apl-ajax-button').prop( 'disabled', false );
	window.location.reload();
}


/**
 * Start contacting the server via AJAX for Terms list.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 */
function cache_all_terms_loop_start( fi, settings )
{
	jQuery('#cache-terms-status').html( 'Getting the terms list.' );
}


/**
 * Finished contacting the server via AJAX for the Terms list.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  bool   success   True if the AJAX call was successful, otherwise false.
 * @param  array  data      The returned data on success, otherwise error information.
 */
function cache_all_terms_loop_end( fi, settings, success, data )
{
	if( !success || !data.success )
	{
		jQuery('#cache-terms-status')
			.html( 'Failed to get the terms count.' );

		jQuery('#cache-terms-substatus')
			.addClass('error')
			.html( data.message );
	}
	else
	{
		jQuery('#cache-terms-status').html( 'Received the terms list.' );
	}
}


/**
 * Start cycling through the Terms list.
 * @param  array  ajax  The AJAX settings returned from the server.
 */
function cache_term_start( ajax )
{
	jQuery('#cache-terms-status').html( 'Processing each term.' );
}


/**
 * Finished cycling through the Terms list.
 * @param  array  ajax  The AJAX settings returned from the server.
 */
function cache_term_end( ajax )
{
}


/**
 * Start contacting the server via AJAX to process one User.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  int    ai        The current ajax items count.
 * @param  array  ajax      The AJAX settings returned from the server.
 */
function cache_term_loop_start( fi, settings, ai, ajax )
{
	if( ai + 1 == ajax.items.length )
	{
		jQuery('#cache-terms-substatus')
			.removeClass('error')
			.html( 'Finishing caching.' );
	}
	else
	{
		jQuery('#cache-terms-substatus')
			.removeClass('error')
			.html( 'Caching term ' + (ai + 1) + ' of ' + (ajax.items.length - 1) + '.' );
	}
}


/**
 * Finished contacting the server via AJAX to process one User.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  int    ai        The current ajax items count.
 * @param  array  ajax      The AJAX settings returned from the server.
 * @param  bool   success   True if the AJAX call was successful, otherwise false.
 * @param  array  data      The returned data on success, otherwise error information.
 */
function cache_term_loop_end( fi, settings, ai, ajax, success, data )
{
	
}

