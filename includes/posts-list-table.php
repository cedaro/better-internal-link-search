<?php
/**
 * Class for instant search on Manage Posts screens.
 *
 * @package Better_Internal_Link_Search
 *
 * @since 1.2.0
 */
class Better_Internal_Link_Search_Posts_List_Table {
	/**
	 * Load the post list table instant search feature.
	 *
	 * @since 1.2.0
	 */
	public static function load() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Setup the post list table search functionality.
	 *
	 * @since 1.2.0
	 */
	public static function init() {
		add_action( 'wp_ajax_bils_get_posts_list_table', array( __CLASS__, 'ajax_get_posts_list_table' ) );
		add_action( 'admin_head-edit.php', array( __CLASS__, 'admin_head_edit' ) );
		add_action( 'admin_head-upload.php', array( __CLASS__, 'admin_head_edit' ) );
	}

	/**
	 * Enqueue javascript and output CSS for post list table searching.
	 *
	 * @todo Implement paging if necessary.
	 *
	 * @since 1.2.0
	 */
	public static function admin_head_edit() {
		$screen = get_current_screen();

		wp_enqueue_script(
			'bils-posts-list-table',
			BETTER_INTERNAL_LINK_SEARCH_URL . 'js/posts-list-table.js',
			array( 'jquery' )
		);

		wp_localize_script( 'bils-posts-list-table', 'BilsListTable', array(
			'nonce'          => wp_create_nonce( 'bils-posts-list-table-instant-search' ),
			'postMimeType'   => ( isset( $_REQUEST['post_mime_type'] ) ) ? $_REQUEST['post_mime_type'] : null,
			'postType'       => ( 'upload' == $screen->id ) ? 'attachment' : $screen->post_type,
			'screen'         => $screen->id,
			'subtitlePrefix' => __( 'Search results for &#8220;%s&#8221;', 'better-internal-link-search' ),
		) );
		?>
		<style type="text/css">
		.wp-list-table #the-list .no-items .spinner,
		#posts-filter .search-box .spinner { float: left;}
		</style>
		<?php
	}

	/**
	 * Get the post list table rows for the searched term.
	 *
	 * Mimics admin/edit.php without all the chrome elements.
	 *
	 * @since 1.2.0
	 * @todo Account for private status on media items?
	 */
	public static function ajax_get_posts_list_table() {
		global $hook_suffix, $pagenow, $post_type, $post_type_object, $per_page, $mode, $wp_query;

		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'bils-posts-list-table-instant-search' ) ) {
			echo '<tr class="no-items bils-error"><td class="colspanchange">Invalid nonce.</td></tr>';
			wp_die();
		}

		$post_type = $_REQUEST['post_type'];
		$post_type_object = get_post_type_object( $post_type );

		// Determine the orderby argument.
		if ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) {
			$orderby = $_REQUEST['orderby'];
		} else {
			$orderby = ( $post_type_object->hierarchical ) ? 'title' : 'post_date';
		}

		// Determine the order argument.
		if ( isset( $_REQUEST['order'] ) && ! empty( $_REQUEST['order'] ) ) {
			$order = ( 'asc' == strtolower( $_REQUEST['order'] ) ) ? 'asc' : 'desc';
		} else {
			$order = ( $post_type_object->hierarchical ) ? 'asc' : 'desc';
		}

		$args = array(
			's'              => $_REQUEST['s'],
			'post_type'      => $post_type,
			'post_status'    => $_REQUEST['post_status'],
			'orderby'        => $orderby,
			'order'          => $order,
			'posts_per_page' => 20,
		);

		if ( 'attachment' == $post_type ) {
			$args['post_status'] = 'inherit';
			$args['post_mime_type'] = $_REQUEST['post_mime_type'];
		}

		// WordPress SEO compatibility.
		if ( function_exists( 'wpseo_admin_init' ) ) {
			$pagenow = 'edit.php';
			wpseo_admin_init();

			if ( class_exists( 'WPSEO_Metabox' ) ) {
				$wpseo_metabox = new WPSEO_Metabox();
				$wpseo_metabox->setup_page_analysis();
			}
		}

		set_current_screen( $_REQUEST['screen'] );

		// Posts 2 Posts column compatibility.
		do_action( 'load-edit.php' );

		add_filter( 'posts_search', array( 'Better_Internal_Link_Search', 'limit_search_to_title' ), 10, 2 );

		wp_edit_posts_query( $args );

		if ( 'attachment' == $post_type ) {
			$wp_list_table = _get_list_table( 'WP_Media_List_Table', array( 'screen' => $_REQUEST['screen'] ) );
		} else {
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => $_REQUEST['screen'] ) );
		}

		$wp_list_table->prepare_items();
		$wp_list_table->display_rows_or_placeholder();

		wp_die();
	}
}
