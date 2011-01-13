<?php
/**
 * Merge $wgExtensionMessagesFiles from various extensions to produce a
 * single array containing all message files.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */

# Start from scratch
define( 'MW_NO_EXTENSION_MESSAGES', 1 );

require_once( dirname( __FILE__ ) . '/Maintenance.php' );
$maintClass = 'MergeMessageFileList';
$mmfl = false;
class MergeMessageFileList extends Maintenance {

	function __construct() {
		$this->addOption( 'list-file', 'A file containing a list of extension setup files, one per line.', false, true );
		$this->addOption( 'output', 'Send output to this file (omit for stdout)', false, true );
		$this->mDescription = 'Merge $wgExtensionMessagesFiles from various extensions to produce a ' .
			'single array containing all message files.';
	}

	public function execute() {
		global $mmfl;
		if ( !$this->hasOption( 'list-file' ) ) {
			$this->error( 'The --list-file option must be specified.' );
			return;
		}

		$lines = file( $this->getOption( 'list-file' ) );
		if ( $lines === false ) {
			$this->error( 'Unable to open list file.' );
		}
		$mmfl = array( 'setupFiles' => array_map( 'trim', $lines ) );
		if ( $this->hasOption( 'output' ) ) {
			$mmfl['output'] = $this->getOption( 'output' );
		}
	}
}

require_once( RUN_MAINTENANCE_IF_MAIN );

foreach ( $mmfl['setupFiles'] as $fileName ) {
	if ( strval( $fileName ) === '' ) {
		continue;
	}
	$fileName = str_replace( '$IP', $IP, $fileName );
	fwrite( STDERR, "Loading data from $fileName\n" );
	include_once( $fileName );
}
fwrite( STDERR, "\n" );
$s =
	"<" . "?php\n" .
	"## This file is generated by mergeMessageFileList.php. Do not edit it directly.\n\n" .
	"if ( defined( 'MW_NO_EXTENSION_MESSAGES' ) ) return;\n\n" .
	'$wgExtensionMessagesFiles = ' . var_export( $wgExtensionMessagesFiles, true ) . ";\n\n" .
	'$wgExtensionAliasesFiles = ' . var_export( $wgExtensionAliasesFiles, true ) . ";\n";

$dirs = array(
	$IP,
	dirname( dirname( __FILE__ ) ),
	realpath( $IP )
);

foreach ( $dirs as $dir ) {
	$s = preg_replace(
		"/'" . preg_quote( $dir, '/' ) . "([^']*)'/",
		'"$IP\1"',
		$s );
}

if ( isset( $mmfl['output'] ) ) {
	file_put_contents( $mmfl['output'], $s );
} else {
	echo $s;
}

