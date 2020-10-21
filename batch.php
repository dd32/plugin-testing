<?php
/**
 * Batch runner, runs the github actions script over and over again. 
 * Parses the GitHub outputs.
 */

// PHP8 compat functions for testing.
require __DIR__ . '/vendor/symfony/polyfill-php80/Php80.php';
require __DIR__ . '/vendor/symfony/polyfill-php80/bootstrap.php';

$slug = $argv[1] ?? 'plugin';

$url = "https://downloads.wordpress.org/plugin/$slug.latest-stable.zip?nostats=1";

echo `curl $url -so $slug.zip`;
echo `mkdir $slug`;
echo `unzip -q $slug.zip -d $slug`;
echo `rm -f $slug.zip`;

`git config --global user.email "dion@wordpress.org"`;
`git config --global user.name "dd32"`;

exec( "php run_checks.php $slug", $output, $return_var );

$state = 0; // OK
$warnings = [];
$errors   = [];
foreach ( $output as $line ) {
	if ( ! str_starts_with( $line, '::' ) ) {
		continue;
	}
	if ( str_starts_with( $line, '::warning::Plugin' ) ) {
		continue;
	}

	$data = explode( '::', $line, 3 )[2] ?? '';
	$data = str_replace( '%0A', "\n", $data );

	if ( str_starts_with( $line, '::warning::' ) ) {
		$state = max( $state, 1 );
		$warnings[] = $data;
	}
	if ( str_starts_with( $line, '::error::' ) ) {
		$state = max( $state, 2 );
		$errors[] = $data;
	}

}

if ( 0 === $state ) {
	$state = 'ok';
} else if ( 1 === $state ) {
	$state = 'warning';
} else {
	$state = 'error';
}
`mkdir -p ./results/$state/$slug`;
file_put_contents( "./results/$state/$slug/raw.txt", implode( "\n", $output ) );
if ( $warnings ) {
	file_put_contents( "./results/$state/$slug/warnings.txt", implode( "\n", $warnings ) );
}
if ( $errors ) {
	file_put_contents( "./results/$state/$slug/errors.txt", implode( "\n", $errors ) );
}

$return_val = null;
$try = 0;
while ( 0 !== $return_val && $try++ <= 10 ) {
	exec( "cd results && ( git add $state/$slug || true ) && git commit $state/$slug -m '$state $slug'", $output, $return_val );
	sleep( 1 );
}

echo `rm -rf $slug`;

echo "Processed $slug \n";