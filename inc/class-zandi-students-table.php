<?php
/**
 * The students table.
 *
 * Required from inc/students.php inside the screen callback, never at file
 * scope: WP_List_Table is a wp-admin class and does not exist on the front end,
 * so a class declared against it at load time would be a fatal error on every
 * public page.
 *
 * THE QUERY IS FLAT. One WP_User_Query for the page of students, one
 * cache_users() that primes all of their meta in a single query, and then
 * nothing — every column below reads meta that is already in memory. That is
 * the reason the placement level and the courses owned are mirrored into scalar
 * meta when they change rather than recomputed here: twenty rows must not mean
 * twenty order queries.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lists students with their level, courses, payments and activity.
 */
class Zandi_Students_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'student',
				'plural'   => 'students',
				'ajax'     => false,
			)
		);
	}

	/**
	 * The columns, in reading order.
	 *
	 * @return array<string,string>
	 */
	public function get_columns() {
		$copy = zandi_students_copy();

		return array(
			'name'    => $copy['col_name'],
			'phone'   => $copy['col_phone'],
			'level'   => $copy['col_level'],
			'score'   => $copy['col_score'],
			'courses' => $copy['col_courses'],
			'paid'    => $copy['col_paid'],
			'joined'  => $copy['col_joined'],
			'seen'    => $copy['col_seen'],
		);
	}

	/**
	 * What may be sorted, and it is deliberately only these two.
	 *
	 * Both are real columns on the users table. Sorting by level, by payment or
	 * by last sign-in would mean `meta_key` plus `orderby => meta_value`, which
	 * WP_User_Query builds as an INNER JOIN on usermeta — so every student who
	 * has not taken the test, or has never bought anything, silently drops out
	 * of the list. A filter answers the same question without hiding anyone,
	 * which is why the level and course filters exist instead.
	 *
	 * @return array<string,array{0:string,1:bool}>
	 */
	public function get_sortable_columns() {
		return array(
			'name'   => array( 'display_name', false ),
			'joined' => array( 'registered', true ),
		);
	}

	/**
	 * The message when nothing matched.
	 *
	 * @return void
	 */
	public function no_items() {
		$copy = zandi_students_copy();

		echo esc_html( $this->is_filtered() ? $copy['empty_search'] : $copy['empty'] );
	}

	/**
	 * Whether a search or a filter is narrowing the list.
	 *
	 * @return bool
	 */
	protected function is_filtered() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
		return ! empty( $_REQUEST['s'] ) || ! empty( $_REQUEST['zandi_level'] ) || ! empty( $_REQUEST['zandi_course'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Runs the query.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'zandi_students_per_page', 20 );

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'name' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list state.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'registered';
		$order   = isset( $_REQUEST['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_REQUEST['order'] ) ) ) ? 'ASC' : 'DESC';
		$search  = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$course  = isset( $_REQUEST['zandi_course'] ) ? sanitize_key( wp_unslash( $_REQUEST['zandi_course'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$level = zandi_students_filter_level();

		$args = array(
			'role__in'    => zandi_students_roles(),
			'number'      => $per_page,
			'paged'       => $this->get_pagenum(),
			'count_total' => true,
			'fields'      => 'all',
			'orderby'     => in_array( $orderby, array( 'display_name', 'registered' ), true ) ? $orderby : 'registered',
			'order'       => $order,
		);

		$meta = array();

		if ( 'none' === $level ) {
			$meta[] = array(
				'key'     => 'zandi_placement_level',
				'compare' => 'NOT EXISTS',
			);
		} elseif ( '' !== $level ) {
			$meta[] = array(
				'key'   => 'zandi_placement_level',
				'value' => $level,
			);
		}

		if ( '' !== $course ) {
			$meta[] = array(
				'key'   => 'zandi_course_owned',
				'value' => $course,
			);
		}

		if ( $meta ) {
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Indexed equality on one admin screen.
		}

		if ( '' !== $search ) {
			$args = array_merge( $args, $this->search_args( $search ) );
		}

		$query = new WP_User_Query( $args );

		$this->items = $query->get_results();

		/*
		 * The single query that makes every column below free. Without it each
		 * get_user_meta() in each row is its own SELECT.
		 */
		if ( $this->items ) {
			cache_users( wp_list_pluck( $this->items, 'ID' ) );
		}

		$this->set_pagination_args(
			array(
				'total_items' => (int) $query->get_total(),
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Query arguments for a search term.
	 *
	 * A mobile number cannot be found by WP_User_Query's own `search`: it lives
	 * in user meta — under any of the four keys in zandi_phone_meta_keys() —
	 * and `search` and `meta_query` are ANDed together, so they cannot be used
	 * as alternatives to each other. So a numeric term is resolved to a set of
	 * IDs first and passed in as `include`.
	 *
	 * The number is normalised before it is used, which means «۰۹۱۲…» in
	 * Persian digits, «+98912…» and «0912…» all find the same student. The
	 * leading zero is dropped from the pattern so that a stored `+98…` matches
	 * too, and the theme's own accounts — whose login IS the number — are
	 * looked up separately, because those may predate the meta being written.
	 *
	 * @param string $search Raw search term.
	 * @return array<string,mixed>
	 */
	protected function search_args( $search ) {
		$phone = zandi_normalize_phone( $search );

		if ( ! zandi_is_valid_phone( $phone ) ) {
			return array(
				'search'         => '*' . $search . '*',
				'search_columns' => array( 'user_login', 'user_email', 'display_name', 'user_nicename' ),
			);
		}

		$bare  = ltrim( $phone, '0' );
		$clauses = array( 'relation' => 'OR' );

		foreach ( zandi_phone_meta_keys() as $key ) {
			$clauses[] = array(
				'key'     => $key,
				'value'   => $bare,
				'compare' => 'LIKE',
			);
		}

		$ids = get_users(
			array(
				'fields'     => 'ids',
				'number'     => 200,
				'meta_query' => $clauses, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Only when a number is searched for.
			)
		);

		$by_login = get_user_by( 'login', $phone );

		if ( $by_login ) {
			$ids[] = $by_login->ID;
		}

		// An empty include would be ignored, and would list everybody.
		return array( 'include' => $ids ? array_values( array_unique( array_map( 'intval', $ids ) ) ) : array( 0 ) );
	}

	/**
	 * The level and course filters.
	 *
	 * @param string $which Top or bottom tablenav.
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$copy = zandi_students_copy();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
		$course = isset( $_REQUEST['zandi_course'] ) ? sanitize_key( wp_unslash( $_REQUEST['zandi_course'] ) ) : '';
		$level  = zandi_students_filter_level();
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="zandi_level"><?php echo esc_html( $copy['col_level'] ); ?></label>
			<select name="zandi_level" id="zandi_level">
				<option value=""><?php echo esc_html( $copy['filter_all_levels'] ); ?></option>
				<option value="none" <?php selected( $level, 'none' ); ?>><?php echo esc_html( $copy['filter_no_test'] ); ?></option>
				<?php foreach ( zandi_students_levels() as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $level, $option ); ?>><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="screen-reader-text" for="zandi_course"><?php echo esc_html( $copy['col_courses'] ); ?></label>
			<select name="zandi_course" id="zandi_course">
				<option value=""><?php echo esc_html( $copy['filter_all_courses'] ); ?></option>
				<?php foreach ( zandi_courses_raw() as $slug => $data ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $course, $slug ); ?>>
						<?php echo esc_html( isset( $data['short_name'] ) ? $data['short_name'] : $data['title'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php submit_button( $copy['filter_apply'], '', 'zandi_filter', false ); ?>
		</div>
		<?php
	}

	/**
	 * Fallback for a column with no method of its own.
	 *
	 * @param WP_User $user   Student.
	 * @param string  $column Column key.
	 * @return string
	 */
	public function column_default( $user, $column ) {
		return '';
	}

	/**
	 * Name, linked to the student's own page.
	 *
	 * NO GRAVATAR ANYWHERE ON THIS SCREEN. secure.gravatar.com is not reliably
	 * reachable from Iran, and twenty rows each waiting on an avatar that will
	 * not arrive is a screen that looks broken. The initial is drawn in CSS.
	 *
	 * @param WP_User $user Student.
	 * @return string
	 */
	public function column_name( $user ) {
		$url  = zandi_students_url( array( 'student' => $user->ID ) );
		$name = $user->display_name ? $user->display_name : $user->user_login;

		$actions = array(
			'edit' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_user_link( $user->ID ) ),
				esc_html( zandi_students_text( 'edit_user' ) )
			),
		);

		return sprintf(
			'<span class="zandi-initial" aria-hidden="true">%1$s</span><strong><a class="row-title" href="%2$s">%3$s</a></strong>%4$s',
			esc_html( zandi_first_char( $name ) ),
			esc_url( $url ),
			esc_html( $name ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Mobile number.
	 *
	 * The cell is turned left-to-right in admin-students.css, on the column
	 * class rather than on a span in here — the same rule the placement pages
	 * follow, and the reason the digits do not end up reordered against the
	 * Persian around them.
	 *
	 * @param WP_User $user Student.
	 * @return string
	 */
	public function column_phone( $user ) {
		$phone = zandi_user_phone( $user->ID );

		if ( ! $phone ) {
			return esc_html( zandi_students_text( 'not_set' ) );
		}

		return sprintf(
			'<a href="tel:%1$s">%2$s</a>',
			esc_attr( $phone ),
			esc_html( zandi_format_phone( $phone ) )
		);
	}

	/**
	 * Placement level.
	 *
	 * @param WP_User $user Student.
	 * @return string
	 */
	public function column_level( $user ) {
		$level = zandi_students_level( $user->ID );

		if ( '' === $level ) {
			return '<span class="zandi-muted">' . esc_html( zandi_students_text( 'no_level' ) ) . '</span>';
		}

		// Already escaped, and isolates «A1+» so it does not read «+A1».
		return '<span class="zandi-chip">' . zandi_placement_level_label( $level ) . '</span>';
	}

	/**
	 * Score, and how much of the test they actually answered.
	 *
	 * @param WP_User $user Student.
	 * @return string
	 */
	public function column_score( $user ) {
		$result = zandi_students_result( $user->ID );

		if ( ! $result ) {
			return '<span class="zandi-muted">' . esc_html( zandi_students_text( 'nothing' ) ) . '</span>';
		}

		$total    = isset( $result['total'] ) ? (int) $result['total'] : 0;
		$answered = isset( $result['answered'] ) ? (int) $result['answered'] : 0;
		$blank    = max( 0, $total - $answered );
		$markup   = esc_html( zandi_students_score_label( $result ) );

		if ( $blank ) {
			$markup .= '<br><span class="zandi-muted">' . esc_html( sprintf( zandi_students_text( 'blanks' ), zandi_fa_digits( $blank ) ) ) . '</span>';
		}

		return $markup;
	}

	/**
	 * Courses owned, from the mirror.
	 *
	 * @param WP_User $user Student.
	 * @return string
	 */
	public function column_courses( $user ) {
		$slugs = zandi_students_owned_courses( $user->ID );

		if ( ! $slugs ) {
			return '<span class="zandi-muted">' . esc_html( zandi_students_text( 'nothing' ) ) . '</span>';
		}

		$names = array();

		foreach ( $slugs as $slug ) {
			$course  = zandi_get_course( $slug );
			$names[] = $course && isset( $course['short_name'] ) ? $course['short_name'] : $slug;
		}

		return esc_html( implode( '، ', $names ) );
	}

	/**
	 * Lifetime payment.
	 *
	 * @param WP_User $user Student.
	 * @return string
	 */
	public function column_paid( $user ) {
		$spent = zandi_students_spent( $user->ID );

		if ( ! $spent ) {
			return '<span class="zandi-muted">' . esc_html( zandi_students_text( 'nothing' ) ) . '</span>';
		}

		return esc_html( zandi_price_toman( $spent ) . ' ' . zandi_students_text( 'toman' ) );
	}

	/**
	 * Signup date.
	 *
	 * @param WP_User $user Student.
	 * @return string
	 */
	public function column_joined( $user ) {
		return esc_html( zandi_placement_date( strtotime( $user->user_registered ) ) );
	}

	/**
	 * Last sign-in.
	 *
	 * @param WP_User $user Student.
	 * @return string
	 */
	public function column_seen( $user ) {
		$seen = zandi_last_login( $user->ID );

		if ( ! $seen ) {
			return '<span class="zandi-muted">' . esc_html( zandi_students_text( 'never' ) ) . '</span>';
		}

		return esc_html( zandi_placement_date( $seen ) );
	}
}
