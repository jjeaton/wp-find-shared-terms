<?php

WP_CLI::add_command( 'shared-terms', 'Shared_Terms_CLI_Command' );

/**
 * List and split shared taxonomy terms.
 *
 * @since 0.1.1
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

		$terms = wpfst_get_shared_terms();
		if ( empty( $terms ) ) {
			wpfst_print_line( "There are no terms to split!", 'error', 'cli' );
		}

		wpfst_split_shared_terms( $terms, 'cli', $dry_run, $verbose );
	}

}