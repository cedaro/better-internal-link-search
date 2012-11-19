jQuery(function($) {
	$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
		if ( -1 != options.data.indexOf('action=wp-link-ajax') && -1 != options.data.indexOf('search=') ) {
			// Abort the request if it's just for resetting the river.
			if ( -1 != options.data.indexOf('better-internal-link-search-reset-river-flag') ) {
				jqXHR.abort();
			}
			
			// Reset the search field to a single dash.
			if ( -1 != options.data.indexOf('search=-help') ) {
				$('#search-field').val('-');
			}
		}
	});
		
	$('#wp-link').bind('wpdialogbeforeopen', function() {
		var searchField = $('#search-field').width(200),
			searchTerm = '-',
			timeout;
		
		// Don't mind me, just debouncing, yo.
		searchField.off('keyup').on('keyup.bils', function() {
			var self = this
				$self = $(this);
			
			clearTimeout(timeout);
			timeout = setTimeout( function() {
				if ( '-' == $self.val() || 0 === $self.val().indexOf('-help') ) {
					// Ugly hack to reset the river...
					$self.val('better-internal-link-search-reset-river-flag');
					wpLink.searchInternalLinks.apply( self );
					// And then bypass the three character minimum requirement.
					$self.val('-help');
				}
				
				wpLink.searchInternalLinks.apply( self );
			}, 500 );
		});
		
		// Determine what text is selected in the editor.
		if ( 'undefined' != typeof tinyMCE && ( editor = tinyMCE.activeEditor ) && ! editor.isHidden() ) {
			var a = editor.dom.getParent(editor.selection.getNode(), 'A');
			if ( null == a ) {
				searchTerm = editor.selection.getContent();
			} else {
				searchTerm = $(a).text();
			}
		} else {
			var start = wpLink.textarea.selectionStart,
				end = wpLink.textarea.selectionEnd;
			
			if ( 0 < end-start ) {
				searchTerm = wpLink.textarea.value.substring(start, end);
			}
		}
		
		// Strip any html to get a clean search term.
		if ( -1 !== searchTerm.indexOf('<') ) {
			searchTerm = searchTerm.replace(/(<[^>]+>)/ig,'');
		}
		
		if ( 'yes' == BilsSettings.automatically_search_selection && searchTerm.length ) {
			searchField.val( $.trim(searchTerm) ).keyup();
		}
	});
});