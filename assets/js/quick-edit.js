/**
 * Projects list — carry the "Home" column state into Quick Edit.
 *
 * The inline editor builds one shared form and clones it per row, so the
 * checkbox is populated here from the hidden flag in the row's Home cell.
 */
( function ( $ ) {
	if ( ! window.inlineEditPost ) {
		return;
	}

	var edit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {
		edit.apply( this, arguments );

		var postId = 0;
		if ( typeof id === 'object' ) {
			postId = parseInt( this.getId( id ), 10 );
		} else {
			postId = parseInt( id, 10 );
		}

		if ( ! postId ) {
			return;
		}

		var featured =
			$( '#post-' + postId + ' .curly-featured-flag' ).attr( 'data-featured' ) === '1';

		$( '#edit-' + postId )
			.find( 'input[name="_project_featured"]' )
			.prop( 'checked', featured );
	};
} )( jQuery );
