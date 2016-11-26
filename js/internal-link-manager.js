/*global _:false, BilsSettings:true, QTags:false, tinymce:false, wpLink:true */

(function( window, $, _, undefined ) {
	'use strict';

	var Editor, Manager;

	Editor = {
		/**
		 * Retrieve text selected in the editor.
		 *
		 * @global QTags
		 * @global wpActiveEditor
		 *
		 * @return {string} Selected text.
		 */
		getSelection: function() {
			var ed = this.getTinyMce(),
				selection = '',
				wpActiveEditor = window.wpActiveEditor,
				end, start;

			if ( ! ed ) {
				return '';
			}

			if ( ed && ! ed.isHidden() ) {
				selection = ed.selection.getContent();
			} else if ( 'undefined' !== typeof QTags ) {
				ed = QTags.getInstance( wpActiveEditor );
				start = ed.canvas.selectionStart;
				end = ed.canvas.selectionEnd;

				if ( end - start > 0 ) {
					selection = ed.canvas.value.substring( start, end );
				}
			}

			return selection;
		},

		/**
		 * Retrieve the current instance of TinyMCE.
		 *
		 * @global QTags
		 * @global tinymce
		 * @global wpActiveEditor
		 *
		 * @return {Object|bool} TinyMCE instance, null, or false.
		 */
		getTinyMce: function() {
			var mce = 'undefined' !== typeof tinymce,
				qt = 'undefined' !== typeof QTags,
				wpActiveEditor = window.wpActiveEditor,
				ed;

			if ( ! wpActiveEditor ) {
				if ( mce && tinymce.activeEditor ) {
					ed = tinymce.activeEditor;
					wpActiveEditor = window.wpActiveEditor = ed.id;
				} else if ( ! qt ) {
					return false;
				}
			} else if ( mce ) {
				if ( tinymce.activeEditor && ( 'mce_fullscreen' === tinymce.activeEditor.id || 'wp_mce_fullscreen' === tinymce.activeEditor.id ) ) {
					ed = tinymce.activeEditor;
				} else {
					ed = tinymce.get( wpActiveEditor );
				}
			}

			return ed;
		}
	};

	Manager = {
		$searchField: $(),

		/**
		 * Initialize the wpLink manager.
		 */
		init: function() {
			this.$searchField = $( '#search-field, #wp-link-search' );

			// Proxy the internal link search method.
			this._wpLinkSearchInternalLinks = wpLink.searchInternalLinks;
			wpLink.searchInternalLinks = this.searchInternalLinks;

			_.bindAll( this, 'searchSelection' );
			$( '#wp-link' ).on( 'wpdialogbeforeopen', this.searchSelection );
			$( document ).on( 'wplink-open', this.searchSelection );
		},

		/**
		 * Search for internal links.
		 */
		searchInternalLinks: function() {
			if ( '-' === Manager.$searchField.val() || 0 === Manager.$searchField.val().indexOf( '-help' ) ) {
				// Ugly hack to reset the river...
				Manager.$searchField.val( 'better-internal-link-search-reset-river-flag' );
				Manager._wpLinkSearchInternalLinks.apply( this );

				// And then bypass the three character minimum requirement.
				Manager.$searchField.val( '-help' );
			}

			Manager._wpLinkSearchInternalLinks.apply( this );
		},

		/**
		 * Trigger a search for the text selected in the active editor.
		 */
		searchSelection: function() {
			var searchTerm = Editor.getSelection();

			// Strip any html to get a clean search term.
			if ( -1 !== searchTerm.indexOf( '<' ) ) {
				searchTerm = searchTerm.replace( /(<[^>]+>)/ig, '' );
			}

			if ( 'yes' === BilsSettings.automatically_search_selection && searchTerm.length ) {
				this.$searchField.val( $.trim( searchTerm ) ).keyup();
			}
		}
	};

	/**
	 * Filter AJAX requests.
	 */
	$.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
		if (
			'data' in options &&
			'string' === typeof options.data &&
			-1 !== options.data.indexOf( 'action=wp-link-ajax' ) &&
			-1 !== options.data.indexOf( 'search=' )
		) {
			// Abort the request if it's just for resetting the river.
			if ( -1 !== options.data.indexOf( 'better-internal-link-search-reset-river-flag' ) ) {
				jqXHR.abort();
			}

			// Reset the search field to a single dash.
			if ( -1 !== options.data.indexOf( 'search=-help' ) ) {
				Manager.$searchField.val( '-' );
			}
		}
	});

	$( document ).ready(function() {
		Manager.init();
	});
})( this, jQuery, _ );
