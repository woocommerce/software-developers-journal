<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Seriously_Simple_Real_Estate {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	public $token;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->token = 'property';

		// Regsiter post type
		add_action( 'init' , array( $this , 'register_post_type' ) );

		if ( is_admin() ) {

			// Handle custom fields for post
			add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
			add_action( 'save_post', array( $this, 'meta_box_save' ) );

			// Modify text in main title text box
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );

			// Display custom update messages for posts edits
			add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

			// Handle post columns
			add_filter( 'manage_edit-' . $this->token . '_columns', array( $this, 'register_custom_column_headings' ), 10, 1 );
			add_action( 'manage_pages_custom_column', array( $this, 'register_custom_columns' ), 10, 2 );

		} else {
			// Register shortcode for search UI
			add_shortcode( 'property_search', array( $this, 'property_search' ) );

			// Filter property search results
			add_action( 'pre_get_posts', array( $this, 'property_search_filter' ) );
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Flush rewrite rules on plugin activation
		register_activation_hook( $this->file, array( $this, 'rewrite_flush' ) );
	}

	public function rewrite_flush() {
		$this->register_post_type();
		flush_rewrite_rules();
	}

	public function register_post_type() {

		$labels = array(
			'name' => _x( 'Properties', 'post type general name' , 'ss_realestate' ),
			'singular_name' => _x( 'Property', 'post type singular name' , 'ss_realestate' ),
			'add_new' => __( 'Add New', 'ss_realestate' ),
			'add_new_item' => sprintf( __( 'Add New %s', 'ss_realestate' ), __( 'Property' , 'ss_realestate' ) ),
			'edit_item' => sprintf( __( 'Edit %s', 'ss_realestate' ), __( 'Property' , 'ss_realestate' ) ),
			'new_item' => sprintf( __( 'New %s', 'ss_realestate' ), __( 'Property' , 'ss_realestate' ) ),
			'all_items' => sprintf( __( 'All %s', 'ss_realestate' ), __( 'Properties' , 'ss_realestate' ) ),
			'view_item' => sprintf( __( 'View %s', 'ss_realestate' ), __( 'Property' , 'ss_realestate' ) ),
			'search_items' => sprintf( __( 'Search %s', 'ss_realestate' ), __( 'Properties' , 'ss_realestate' ) ),
			'not_found' =>  sprintf( __( 'No %s Found', 'ss_realestate' ), __( 'Properties' , 'ss_realestate' ) ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash' , 'ss_realestate' ), __( 'Properties' , 'ss_realestate' ) ),
			'parent_item_colon' => '',
			'menu_name' => _x( 'Properties', 'post type menu name', 'ss_realestate' )
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'query_var' => false,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => true,
			'supports' => array( 'title', 'editor', 'excerpt' ),
			'taxonomies' => array( 'property_area', 'property_type' ),
			'menu_position' => 5,
		);

		register_post_type( $this->token, $args );

		$this->register_taxonomies();
	}

	private function register_taxonomies() {
		$this->register_taxonomy( 'property_area', __( 'Areas', 'ss_realestate' ), __( 'Area', 'ss_realestate' ) );
		$this->register_taxonomy( 'property_type', __( 'Types', 'ss_realestate' ), __( 'Type', 'ss_realestate' ) );
	}

	private function register_taxonomy( $taxonomy, $general_name, $singular_name, $hierarchical = true ) {

        $labels = array(
            'name' => sprintf( _x( '%s', 'taxonomy general name', 'ss_realestate' ), $general_name ),
            'singular_name' => sprintf( _x( '%s', 'taxonomy singular name', 'ss_realestate' ), $singular_name ),
            'search_items' =>  sprintf( __( 'Search %s', 'ss_realestate' ), $general_name ),
            'all_items' => sprintf( __( 'All %s', 'ss_realestate' ), $general_name ),
            'parent_item' => sprintf( __( 'Parent %s', 'ss_realestate' ), $singular_name ),
            'parent_item_colon' => sprintf( __( 'Parent %s:', 'ss_realestate' ), $singular_name ),
            'edit_item' => sprintf( __( 'Edit %s', 'ss_realestate' ), $singular_name ),
            'update_item' => sprintf( __( 'Update %s', 'ss_realestate' ), $singular_name ),
            'add_new_item' => sprintf( __( 'Add New %s', 'ss_realestate' ), $singular_name ),
            'new_item_name' => sprintf( __( 'New %s Name', 'ss_realestate' ), $singular_name ),
            'menu_name' => sprintf( _x( '%s', 'taxonomy menu name', 'ss_realestate' ), $general_name ),
        );

        $args = array(
            'public' => true,
            'hierarchical' => $hierarchical,
            'rewrite' => true,
            'labels' => $labels
        );

        register_taxonomy( $taxonomy, $this->token, $args );
    }

    public function get_custom_fields() {
		$fields = array();

		$fields['property_price'] = array(
		    'name' => __( 'Price:' , 'ss_realestate' ),
		    'description' => __( 'Property price' , 'ss_realestate' ),
		    'type' => 'number',
		    'default' => '',
		    'section' => 'plugin-data'
		);

		return $fields;
	}

    public function register_custom_column_headings( $defaults ) {
		$new_columns = array(
			'property_price' => __( 'Price' , 'ss_realestate' )
		);

		$last_item = '';

		if ( count( $defaults ) > 2 ) {
			$last_item = array_slice( $defaults, -1 );

			array_pop( $defaults );
		}
		$defaults = array_merge( $defaults, $new_columns );

		if ( $last_item != '' ) {
			foreach ( $last_item as $k => $v ) {
				$defaults[$k] = $v;
				break;
			}
		}

		return $defaults;
	}

	public function register_custom_columns( $column_name, $id ) {

		switch ( $column_name ) {

			case 'property_price':
				$data = get_post_meta( $id , 'property_price' , true );
				echo number_format( $data );
			break;

			default:
			break;
		}

	}

	public function updated_messages( $messages ) {
	  global $post, $post_ID;

	  $messages[$this->token] = array(
	    0 => '', // Unused. Messages start at index 1.
	    1 => sprintf( __( 'Property updated. %sView property%s.' , 'ss_realestate' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    2 => __( 'Custom field updated.' , 'ss_realestate' ),
	    3 => __( 'Custom field deleted.' , 'ss_realestate' ),
	    4 => __( 'Property updated.' , 'ss_realestate' ),
	    /* translators: %s: date and time of the revision */
	    5 => isset( $_GET['revision'] ) ? sprintf( __( 'Property restored to revision from %s.' , 'ss_realestate' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	    6 => sprintf( __( 'Property published. %sView property%s.' , 'ss_realestate' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    7 => __( 'Property saved.' , 'ss_realestate' ),
	    8 => sprintf( __( 'Property submitted. %sPreview property%s.' , 'ss_realestate' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	    9 => sprintf( __( 'Property scheduled for: %1$s. %2$sPreview property%3$s.' , 'ss_realestate' ), '<strong>' . date_i18n( __( 'M j, Y @ G:i' , 'ss_realestate' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
	    10 => sprintf( __( 'Property draft updated. %sPreview property%s.' , 'ss_realestate' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
	  );

	  return $messages;
	}

	public function meta_box_setup() {
		add_meta_box( 'post-data', __( 'Property Details' , 'ss_realestate' ), array( $this, 'meta_box_content' ), $this->token, 'normal', 'high' );
	}

	public function meta_box_content() {
		global $post_id;
		$fields = get_post_custom( $post_id );
		$field_data = $this->get_custom_fields();

		$html = '';

		$html .= '<input type="hidden" name="' . $this->token . '_nonce" id="' . $this->token . '_nonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';

		if ( 0 < count( $field_data ) ) {
			$html .= '<table class="form-table">' . "\n";
			$html .= '<tbody>' . "\n";

			foreach ( $field_data as $k => $v ) {
				$data = $v['default'];

				if ( isset( $fields[$k] ) && isset( $fields[$k][0] ) ) {
					$data = $fields[$k][0];
				}

				switch( $v['type'] ) {
					case 'checkbox':
						$html .= '<tr valign="top"><th scope="row">' . $v['name'] . '</th><td><input name="' . esc_attr( $k ) . '" type="checkbox" id="' . esc_attr( $k ) . '" ' . checked( 'on' , $data , false ) . ' /> <label for="' . esc_attr( $k ) . '"><span class="description">' . $v['description'] . '</span></label>' . "\n";
						$html .= '</td><tr/>' . "\n";
					break;

					default:
						$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="' . esc_attr( $v['type'] ) . '" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
						$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
						$html .= '</td><tr/>' . "\n";
					break;
				}

			}

			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
		}

		echo $html;
	}

	public function meta_box_save( $post_id ) {
		global $post, $messages;

		// Verify nonce
		if ( ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST[ $this->token . '_nonce'], plugin_basename( $this->dir ) ) ) {
			return $post_id;
		}

		// Verify user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Handle custom fields
		$field_data = $this->get_custom_fields();
		$fields = array_keys( $field_data );

		foreach ( $fields as $f ) {

			if( isset( $_POST[$f] ) ) {
				${$f} = strip_tags( trim( $_POST[$f] ) );
			}

			// Escape the URLs.
			if ( 'url' == $field_data[$f]['type'] ) {
				${$f} = esc_url( ${$f} );
			}

			if ( ${$f} == '' ) {
				delete_post_meta( $post_id , $f , get_post_meta( $post_id , $f , true ) );
			} else {
				update_post_meta( $post_id , $f , ${$f} );
			}
		}

	}

	public function enter_title_here( $title ) {
		if ( get_post_type() == $this->token ) {
			$title = __( 'Enter the property title here', 'ss_realestate' );
		}
		return $title;
	}

	public function property_search() {

		$args = array(
			'hide_empty' => false
		);

		$areas = get_terms( 'property_area', $args );
		$types = get_terms( 'property_type', $args );

		$area_options = '';
		if( count( $areas ) ) {
			$area_options = '<option value="">-- ' . __( 'All areas', 'ss_realestate' ) . ' --</option>';
			foreach( $areas as $area ) {
				$area_options .= '<option value="' . esc_attr( $area->term_id ) . '">' . $area->name . '</option>';
			}
		}

		$type_options = '';
		if( count( $types ) ) {
			$type_options = '<option value="">-- ' . __( 'All types', 'ss_realestate' ) . ' --</option>';
			foreach( $types as $type ) {
				$type_options .= '<option value="' . esc_attr( $type->term_id ) . '">' . $type->name . '</option>';
			}
		}

		$prices = array();
		for( $i = 250000; $i <= 3500000; $i += 250000 ) {
			$prices[] = $i;
		}

		$price_options = '';
		foreach( $prices as $price ) {
			$price_options .= '<option value="' . esc_attr( absint( $price ) ) . '">' . number_format( $price ) . '</option>';
		}

		$title = get_option( 'property_search_form_title' );
		if( ! $title || strlen( $title ) == 0 || $title == '' ) {
			$title = __( 'Property Search', 'ss_realestate' );
		}

		$html = '<div class="property_search">
					<h3>' . $title . '</h3>
					<form name="property_search" action="' . get_site_url() . '" method="get">
						<input type="hidden" name="search" value="property" />
						<input type="text" name="s" value="" placeholder="Text search" />
						<br/>
						<select name="area">' . $area_options . '</select>
						<select name="tax_relation">
							<option value="AND">' . __( 'AND', 'ss_realestate' ) . '</option>
							<option value="OR">' . __( 'OR', 'ss_realestate' ) . '</option>
						</select>
						<select name="type">' . $type_options . '</select>
						<br/>
						' . __( 'Price:', 'ss_realestate' ) . '
						<select name="price_min">
							<option value="0">0</option>
							' . $price_options . '
						</select>
						' . __( 'to', 'ss_realestate' ) . '
						<select name="price_max">' . $price_options . '</select>
						<br/>
						<input type="submit" value="' . __( 'Search', 'ss_realestate' ) . '" />
					</form>
				</div>';

		return $html;
	}

	public function property_search_filter( $query ) {

		if( isset( $_GET['search'] ) && $_GET['search'] == 'property' ) {

			// Only apply the searc filter on the main page query
			if ( ! $query->is_main_query() )
				return;

			$query_args = array();

			// Basic arguments
			$query_args['post_type'] = $this->token;
			$query_args['post_status'] = 'publish';

			// Area taxonomy query
			if( isset( $_GET['area'] ) && absint( $_GET['area'] ) > 0 ) {
				$query_args['tax_query'][] = array(
			        'taxonomy' => 'property_area',
			        'terms' => absint( $_GET['area'] ),
			        'field' => 'id',
			        'operator' => 'IN'
			    );
			}

			// Type taxonomy query
			if( isset( $_GET['type'] ) && absint( $_GET['type'] ) > 0 ) {
				$query_args['tax_query'][] = array(
			        'taxonomy' => 'property_type',
			        'terms' => absint( $_GET['type'] ),
			        'field' => 'id',
			        'operator' => 'IN'
			    );
			}

			// Taxonomy relation
			if( isset( $_GET['tax_relation'] ) && in_array( $_GET['tax_relation'], array( 'AND', 'OR' ) ) ) {
		    	$query_args['tax_query']['relation'] = $_GET['tax_relation'];
		    }

		    // Price meta query
		    if( isset( $_GET['price_min'] ) && isset( $_GET['price_max'] ) ) {
		    	$query_args['meta_query'][] = array(
			        'key' => 'property_price',
			        'value' => array( absint( $_GET['price_min'] ), absint( $_GET['price_max'] ) ),
			        'compare' => 'BETWEEN',
			        'type' => 'NUMERIC'
			    );
		    }

			// Pagination
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		    $query_args['paged'] = $paged;

		    // Check text search string
		    if( get_query_var('s') ) {
			    $string = esc_attr( get_query_var('s') );
			    if( $string && strlen( $string ) > 0 ) {
			    	$query_args['s'] = $string;
			    } else {
			    	$query_args['s'] = '';
			    }
			}

		    // Set query variables
		    foreach ( $query_args as $key => $value ) {
				$query->set( $key, $value );
			}
		}

		return $query;
	}

	public function load_localisation() {
		load_plugin_textdomain( 'ss_realestate', false , dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	public function load_plugin_textdomain() {
	    $domain = 'ss_realestate';

	    $locale = apply_filters( 'plugin_locale', get_locale() , $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

}