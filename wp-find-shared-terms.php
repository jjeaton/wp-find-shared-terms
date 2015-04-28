<?php
/**
 * @since             0.1.0
 * @package           WPFindSharedTerms
 *
 * @wordpress-plugin
 * Plugin Name: Find Shared Terms
 * Plugin URI: http://www.josheaton.org/
 * Description: Find shared terms in your WP install that may be split in WordPress 4.2+
 * Version: 0.1.0
 * Author: Josh Eaton
 * Author URI: http://www.josheaton.org/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-find-shared-terms
 * Domain Path: /languages
 */
/*  Copyright 2015 Josh Eaton (email : josh@josheaton.org)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once( dirname( __FILE__ ) . '/cli.php' );
}

add_action( 'admin_menu', 'wpfst_add_admin_page' );
/**
 * Add admin page
 *
 * @since 0.1.0
 * @return void
 */
function wpfst_add_admin_page() {
	add_management_page(
		__( 'Shared Terms', 'wp-find-shared-terms' ),
		__( 'Find Shared Terms', 'wp-find-shared-terms' ),
		'manage_options',
		'wpfst-show-terms',
		'wpfst_show_terms_page'
	);
}

/**
 * Render admin page
 *
 * @since 0.1.0
 * @return void
 */
function wpfst_show_terms_page() {
	?>
	<div class="wrap">
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<?php
		wpfst_process_form();

		$count_of_shared_terms = count( wpfst_get_shared_terms( true ) );
		if ( 1 > $count_of_shared_terms ) : ?>
			<p><?php _e( "You have no shared terms. If you're already on WordPress 4.1+, you shouldn't have any issues due to shared term splitting.", 'wp-find-shared-terms' ); ?></p>
		<?php else : ?>
			<?php wpfst_show_terms_page_table( $count_of_shared_terms ); ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render admin page table
 *
 * @since 0.1.0
 * @param int $count_of_shared_terms The number of distinct shared terms
 * @return void
 */
function wpfst_show_terms_page_table( $count_of_shared_terms ) {
	?>
	<p>
		<?php printf( _n( "There is <strong>1</strong> shared term in your database.", "There are <strong>%d</strong> shared terms in your database.", $count_of_shared_terms, 'wp-find-shared-terms' ), $count_of_shared_terms ); ?>
		<?php printf( _x( "If you are running any plugins or themes that store term IDs, you may be affected by <a href=\"%s\">shared term splitting</a> in WordPress 4.2+.", '%s=URL of according post on make.wordpress.org', 'wp-find-shared-terms' ), 'https://make.wordpress.org/core/2015/02/16/taxonomy-term-splitting-in-4-2-a-developer-guide/' ); ?>
	</p>
	<table class="widefat">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Term Taxonomy ID', 'wp-find-shared-terms' ); ?></th>
			<th><?php esc_html_e( 'Term ID', 'wp-find-shared-terms' ); ?></th>
			<th><?php esc_html_e( 'Name', 'wp-find-shared-terms' ); ?></th>
			<th><?php esc_html_e( 'Taxonomy', 'wp-find-shared-terms' ); ?></th>
			<th><?php esc_html_e( '# of Posts', 'wp-find-shared-terms' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( wpfst_get_shared_terms() as $shared_term ) {
			// Get the nice taxonomy label if it exists. It's possible you have old terms from taxonomies that are no longer active
			$taxonomy = get_taxonomy( $shared_term->taxonomy );
			$edit_link = '';
			if ( $taxonomy && ! empty( $taxonomy->labels->name ) ) {
				$taxonomy_name = $taxonomy->labels->name;
				// Get the term edit link
				$edit_link = get_edit_term_link( $shared_term->term_id, $shared_term->taxonomy );
			} else {
				$taxonomy_name = $shared_term->taxonomy;
			}

			?>
			<tr>
				<td><?php echo esc_html( $shared_term->term_taxonomy_id ); ?></td>
				<td><?php echo esc_html( $shared_term->term_id ); ?></td>
				<td>
					<?php if ( $edit_link ) { ?>
						<a href="<?php echo esc_url( $edit_link ); ?>" title="<?php esc_html_e( 'Edit Term', 'wp-find-shared-terms' ); ?>"><?php echo esc_html( $shared_term->name ); ?></a>
					<?php } else { ?>
						<?php echo esc_html( $shared_term->name ); ?>
					<?php } ?>
				</td>
				<td><abbr title="<?php echo esc_attr( $shared_term->taxonomy ); ?>"><?php echo esc_html( $taxonomy_name ); ?></abbr></td>
				<td><?php echo esc_html( $shared_term->count ); ?></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<?php if ( function_exists( '_split_shared_term' ) ) { ?>
		<form method="post">
			<p class="description"><?php _e( "If you'd like to split your shared terms all at once, instead of waiting for them to be split when a term is updated, you can do so by clicking the button below. <strong>Only do this if you know what this means and you have already updated your code accordingly</strong>, otherwise leave it alone.", 'wp-find-shared-terms' ); ?></p>
			<p><input type="submit" class="button secondary" name="wpfst_submit" value="<?php esc_attr_e( 'Split Shared Terms', 'wp-find-shared-terms' ); ?>"></p>
			<p><label for="wpfst-dry-run"><input type="checkbox" name="wpfst_dry_run" id="wpfst-dry-run" value="1"> Dry Run</label></p>
			<p><label for="wpfst-verbose"><input type="checkbox" name="wpfst_verbose" id="wpfst-verbose" value="1"> Verbose</label></p>
			<?php wp_nonce_field( 'wpfst-split-terms', 'wpfst_nonce' ); ?>
			<p><?php _e( 'There are also WP-CLI commands: <code>wp shared-terms list</code> and <code>wp shared-terms split</code>. Use these if you have a very large number of shared terms.', 'wp-find-shared-terms' ); ?></p>
		</form>
	<?php }
}

/**
 * Get a list of all shared terms.
 *
 * @since 0.1.1
 * @return array stdClass objects, with term_taxonomy_id, term_id, name,
 *               taxonomy, and count properties.
 */
function wpfst_get_shared_terms( $force = false ) {
	static $terms;
	if ( ! $force && isset( $terms ) ) {
		return $terms;
	}

	/** @var wpdb $wpdb */
	global $wpdb;
	$terms = array();
	$term_ids = $wpdb->get_col( "SELECT `term_id` FROM {$wpdb->term_taxonomy} GROUP BY `term_id` HAVING COUNT(*) > 1" );
	if ( ! empty( $term_ids ) ) {
		$terms = $wpdb->get_results(
			"SELECT tt.term_taxonomy_id, tt.term_id, t.name, tt.taxonomy, tt.count
			FROM {$wpdb->term_taxonomy} AS tt
			INNER JOIN {$wpdb->terms} AS t ON tt.term_id=t.term_id
			WHERE tt.term_id IN (" . implode( ',', $term_ids ) . ')'
		);
	}
	return $terms;
}

/**
 * Split all shared taxonomy terms.
 *
 * @since 0.1.2
 * @param  array  $terms    List of shared term objects
 * @param  string  $context 'admin' or 'cli'
 * @param  boolean $dry_run Run the function without actually splitting
 * @param  boolean $verbose show more output
 * @return void
 */
function wpfst_split_shared_terms( $terms, $context = 'admin', $dry_run = false, $verbose = true ) {
	if ( 'admin' === $context ) {
		$indent = $verbose ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" : '';
	} else {
		$indent = $verbose ? "   " : '';
	}

	wpfst_print_line( "Splitting all shared terms", 'line', $context );
	if ( $dry_run ) {
		wpfst_print_line( 'THIS IS A DRY RUN', 'warning', $context );
	}

	if ( ! function_exists( '_split_shared_term' ) ) {
		wpfst_print_line( 'WordPress must be version 4.2 or higher to split terms', 'error', $context );
	}

	$processed = $split = $skipped = $errors = 0;
	foreach ( $terms as $term ) {
		$processed++;
		if ( $verbose ) {
			wpfst_print_line( "Splitting {$term->taxonomy} \"{$term->name}\"", 'line', $context );
		}
		if ( $dry_run ) {
			wpfst_print_line( $indent . "_split_shared_term( {$term->term_id}, {$term->term_taxonomy_id} );", 'line', $context );
		} else {
			$new_term_id = _split_shared_term( $term->term_id, $term->term_taxonomy_id );
			if ( ! is_wp_error( $new_term_id ) ) {
				if ( $new_term_id == $term->term_id ) {
					$skipped++;
					if ( $verbose ) {
						wpfst_print_line( $indent . "Term Taxonomy ID {$term->term_taxonomy_id} did not need splitting", 'line', $context );
					}
				} else {
					$split++;
					if ( $verbose ) {
						wpfst_print_line( $indent . "Term Taxonomy ID {$term->term_taxonomy_id} was split from {$term->term_id} -> {$new_term_id}", 'line', $context );
					}
				}
			} else {
				$errors++;
				wpfst_print_line( $indent . "ERROR: Term Taxonomy ID {$term->term_taxonomy_id} could not be split!", 'warning', $context );
			}
		}
	}

	// Print a success message
	wpfst_print_line( "Process complete!", 'success', $context );
	wpfst_print_line( "Processed:  {$processed}", 'line', $context );
	wpfst_print_line( "Split:      {$split}", 'line', $context );
	wpfst_print_line( "Skipped:    {$skipped}", 'line', $context );
	wpfst_print_line( "Errors:     {$errors}", 'line', $context );
}

/**
 * Print out a message depending on the context and type.
 *
 * @since 0.1.2
 * @param  string $message Message to print
 * @param  string $type    Type of message to format: 'line', 'error', 'warning', 'success'
 * @param  string $context Either 'admin' or 'cli'
 *
 * @return void
 */
function wpfst_print_line( $message, $type = 'line', $context = 'admin' ) {
	switch ( $type ) {
		case 'line':
			if ( 'admin' === $context ) {
				echo '<li>' . esc_html( $message ) . '</li>';
			} else {
				WP_CLI::line( $message );
			}
			break;
		case 'error':
			if ( 'admin' === $context ) {
				echo '<li><span style="color:#FF0000;">' . esc_html( $message ) . '</span></li>';
			} else {
				WP_CLI::error( $message );
			}
			break;
		case 'warning':
			if ( 'admin' === $context ) {
				echo '<li><span style="color:#FF0000;">' . esc_html( $message ) . '</span></li>';
			} else {
				WP_CLI::warning( $message );
			}
			break;
		case 'success':
			if ( 'admin' === $context ) {
				echo '<li><span style="color:#088A08;">' . esc_html( $message ) . '</span></li>';
			} else {
				WP_CLI::success( $message );
			}
			break;

		default:
			if ( 'admin' === $context ) {
				echo '<li>' . esc_html( $message ) . '</li>';
			} else {
				WP_CLI::line( $message );
			}
			break;
	}
}

/**
 * Process the shared term splitting form.
 *
 * @since 0.1.2
 * @return void
 */
function wpfst_process_form() {
	if ( ! empty( $_POST['wpfst_submit'] ) ) {

		if ( check_admin_referer( 'wpfst-split-terms', 'wpfst_nonce' ) ) {
			$terms = wpfst_get_shared_terms();
			echo '<ul>';
				if ( empty( $terms ) ) {
					wpfst_print_line( "There are no terms to split!", 'error', 'admin' );
				} else {
					$dry_run = isset( $_POST['wpfst_dry_run'] ) && '1' === $_POST['wpfst_dry_run'] ? true : false;
					$verbose = isset( $_POST['wpfst_verbose'] ) && '1' === $_POST['wpfst_verbose'] ? true : false;

					wpfst_split_shared_terms( $terms, 'admin', $dry_run, $verbose );
				}
			echo '</ul>';
		}
	}
}