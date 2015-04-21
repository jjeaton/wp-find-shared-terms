<?php

WP_CLI::add_command( 'shared-terms', 'Shared_Terms_CLI_Command' );

/**
 * List and split shared taxonomy terms.
 */
class Shared_Terms_CLI_Command extends WP_CLI_Command {

	/**
	 * List all shared terms
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count. Default: table
	 *
	 * ## EXAMPLES
	 *
	 *     wp shared-terms list
	 *     wp shared-terms list --format=json
	 *
	 * @synopsis [--format=<format>]
	 * @subcommand list
	 */
	public function do_list( $args, $assoc_args ) {
		$formatter = new \WP_CLI\Formatter( $assoc_args, array( 'term_taxonomy_id', 'term_id', 'name', 'taxonomy', 'count' ) );
		$formatter->display_items( wpfst_get_shared_terms() );
	}

	/**
	 * Split all shared terms
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : If present, no updates will be made.
	 *
	 * [--verbose]
	 * : If present, script will output additional details.
	 *
	 * ## EXAMPLES
	 *
	 *     wp shared-terms split
	 *
	 * @synopsis [--dry-run] [--verbose]
	 */
	public function split( $args, $assoc_args ) {
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$verbose = ! empty( $assoc_args['verbose'] );
		$indent = $verbose ? "   " : '';

		WP_CLI::line( "Splitting all shared terms" );
		if ( $dry_run ) {
			WP_CLI::warning( 'THIS IS A DRY RUN' );
		}

		if ( ! function_exists( '_split_shared_term' ) ) {
			WP_CLI::error( 'WordPress must be version 4.2 or higher to split terms' );
		}

		$terms = wpfst_get_shared_terms();
		if ( empty( $terms ) ) {
			WP_CLI::error( "There are no terms to split!" );
		}

		$processed = $split = $skipped = $errors = 0;
		foreach ( $terms as $term ) {
			$processed++;
			if ( $verbose ) {
				WP_CLI::line( "Splitting {$term->taxonomy} \"{$term->name}\"" );
			}
			if ( $dry_run ) {
				WP_CLI::line( $indent . "_split_shared_term( {$term->term_id}, {$term->term_taxonomy_id} );" );
			} else {
				$new_term_id = _split_shared_term( $term->term_id, $term->term_taxonomy_id );
				if ( ! is_wp_error( $new_term_id ) ) {
					if ( $new_term_id == $term->term_id ) {
						$skipped++;
						if ( $verbose ) {
							WP_CLI::line( $indent . "Term Taxonomy ID {$term->term_taxonomy_id} did not need splitting" );
						}
					} else {
						$split++;
						if ( $verbose ) {
							WP_CLI::line( $indent . "Term Taxonomy ID {$term->term_taxonomy_id} was split from {$term->term_id} -> {$new_term_id}" );
						}
					}
				} else {
					$errors++;
					WP_CLI::warning( $indent . "ERROR: Term Taxonomy ID {$term->term_taxonomy_id} could not be split!" );
				}
			}
		}

		// Print a success message
		WP_CLI::success( "Process complete!" );
		WP_CLI::line( "Processed:  {$processed}" );
		WP_CLI::line( "Split:      {$split}" );
		WP_CLI::line( "Skipped:    {$skipped}" );
		WP_CLI::line( "Errors:     {$errors}" );
	}

}