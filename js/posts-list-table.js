/*global ajaxurl:true, BilsListTable:true, userSettings:true */

(function( window, $, undefined ) {
	'use strict';

	$( document ).ready(function() {
		var $postFilters = $( '#posts-filter' ),
			$pagesNav = $postFilters.find( '.tablenav-pages' ),
			$searchField = $( '#post-search-input, #media-search-input' ),
			$searchStatus = $( '<table><tr class="no-items"><td class="colspanchange"><span class="spinner is-active"></span></td></tr></table>' ),
			$spinner = $searchField.before( '<span class="spinner"></span>' ).siblings( '.spinner' ),
			$theList = $( '#the-list' ),
			$title = $( '#wpbody h2:eq(0)' ),
			$subtitle = $title.find( '.subtitle' ),
			subtitlePrefix = BilsListTable.subtitlePrefix,
			theListOriginal = $theList.html(),
			currentSubtitle, requestArgs, searchPosts, timeout;

		// Hidden table contains a row with a spinner.
		// Hiding this on screen allows the colspan of the cell to be updated as columns are toggled.
		$searchStatus.hide().find( 'td.colspanchange' ).attr( 'colspan', $( '.wp-list-table thead tr' ).children().length );
		$( '.wrap' ).append( $searchStatus );

		// Save the current subtitle to restore later, or add a subtitle span.
		if ( $subtitle.length ) {
			currentSubtitle = $subtitle.text();
		} else {
			$subtitle = $title.append( '<span class="subtitle"></span>' ).find( '.subtitle' );
		}

		// Construct an object of args to send via AJAX.
		requestArgs = {
			action:         'bils_get_posts_list_table',
			mode:           $postFilters.find( 'input[name="mode"]' ).val() || null,
			nonce:          BilsListTable.nonce,
			order:          $postFilters.find( 'input[name="order"]' ).val() || null,
			orderby:        $postFilters.find( 'input[name="orderby"]' ).val() || null,
			post_mime_type: BilsListTable.postMimeType,
			post_status:    $postFilters.find( 'input.post_status_page' ).val() || null,
			post_type:      BilsListTable.postType,
			screen:         BilsListTable.screen,
			uid:            userSettings.uid // For determining hidden columns.
		};

		searchPosts = function() {
			var searchTerm = $searchField.val();

			// Restore the original screen state if the text in the search field is less than 3 characters.
			if ( searchTerm.length < 3 ) {
				if ( currentSubtitle ) {
					$subtitle.text( currentSubtitle );
				} else {
					$subtitle.hide();
				}

				$pagesNav.show();
				$theList.html( theListOriginal );
				return;
			}

			// Begin searching.
			$spinner.addClass( 'is-active' );
			$pagesNav.hide();
			$theList.html( $searchStatus.find( 'tr' ).parent().html() );

			requestArgs['s'] = $searchField.val();

			$.ajax({
				url: ajaxurl,
				data: requestArgs,
				success : function( data ) {
					$theList.html( data ).find( '.bils-error .colspanchange' ).attr( 'colspan', $( '.wp-list-table thead tr' ).children().length );

					$subtitle.html( subtitlePrefix.replace( '%s', searchTerm ) ).show();

					$spinner.removeClass( 'is-active' );
				}
			});
		};

		$searchField.on( 'input.bils', function( e ) {
			var code = e.keyCode || e.which;

			clearTimeout(timeout);

			if ( 13 !== code ) { // Enter key.
				timeout = setTimeout( searchPosts, 300 );
			}
		});
	});
})( this, jQuery );
