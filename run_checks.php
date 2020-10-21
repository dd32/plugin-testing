#!/usr/bin/env php
<?php
$exit_status = 0;

// PHP8 compat functions for testing.
require __DIR__ . '/vendor/symfony/polyfill-php80/Php80.php';
require __DIR__ . '/vendor/symfony/polyfill-php80/bootstrap.php';

// Prepend some useful links.. This should be an info rather than warning/error? Not sure GitHub has one though?
$slug    = $argv[1] ?? 'plugin';
$version = $argv[2] ?? '';
echo '::warning::' .
	"Plugin: https://wordpress.org/plugins/$slug/" . "%0A" .
	"Trac: https://plugins.trac.wordpress.org/browser/$slug/" . ( $version && 'trunk' != $version ? 'tags/' : '' ) . $version .
	"\n";

// Run PHP CS checks.
$output = []; // Clear it.
exec( ( file_exists( 'vendor/bin/phpcs' ) ? 'vendor/bin/phpcs' : 'phpcs' ) . " -s --basepath=$slug/$slug $slug", $output, $returnval );
echo implode( "\n", $output );
if ( $returnval > 0 ) {

	// We don't want the ... outputs.
	foreach ( $output as $i => $line ) {
		if ( $line ) {
			unset( $output[ $i ] );
		} else {
			break;
		}
	}
	// Remove empty lines.
	foreach ( $output as $i => $line ) {
		if ( '' == trim( $line ) ) {
			unset( $output[ $i ] );
		} else {
			break;
		}
	}

	// Strip color/bolding in output. We want the highlighting usually, just not here.
	$output = preg_replace('/\e[[][A-Za-z0-9];?[0-9]*m?/', '', $output );

	// Add trac links..
	$output = preg_replace( '/FILE: (.+?)$/', 'FILE: $1%0ATRAC: https://plugins.trac.wordpress.org/browser/' . $slug . '/trunk/$1', $output );

	// Remove the last two lines, they're the errata after the test.
	$output = array_slice( $output, 0, -2 );

	echo '::error::' . implode( '%0A', $output ) . "\n";
	$exit_status = 1;
}

exit( $exit_status );
