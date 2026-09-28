( function () {
	document.addEventListener( 'DOMContentLoaded', function () {
		var wrap = document.getElementById( 'curly-projects-links-rows' );
		if ( ! wrap ) {
			return;
		}

		var addBtn = document.getElementById( 'curly-projects-add-link' );
		var tpl = document.getElementById( 'curly-projects-link-template' );

		function reindex() {
			var rows = wrap.querySelectorAll( '.curly-project-link-row' );
			Array.prototype.forEach.call( rows, function ( row, i ) {
				var inputs = row.querySelectorAll( '[data-name]' );
				Array.prototype.forEach.call( inputs, function ( input ) {
					input.name = 'project_links[' + i + '][' + input.getAttribute( 'data-name' ) + ']';
				} );
			} );
		}

		if ( addBtn && tpl ) {
			addBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var node = tpl.content.firstElementChild.cloneNode( true );
				wrap.appendChild( node );
				reindex();
			} );
		}

		wrap.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.curly-project-remove-link' );
			if ( ! btn ) {
				return;
			}
			e.preventDefault();

			var row = btn.closest( '.curly-project-link-row' );
			var rows = wrap.querySelectorAll( '.curly-project-link-row' );

			if ( rows.length > 1 ) {
				row.parentNode.removeChild( row );
			} else {
				Array.prototype.forEach.call( row.querySelectorAll( 'input' ), function ( input ) {
					if ( 'checkbox' === input.type ) {
						input.checked = true;
					} else {
						input.value = '';
					}
				} );
			}
			reindex();
		} );
	} );
}() );
