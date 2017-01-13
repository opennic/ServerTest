<?php

  // Check to see if PEAR libraries are in the include_path
  // if not, let's add it ourselves so we can load up the Net_DNS2 library
  $path = ini_get( 'include_path' );
  $paths = explode( ':', $path );
  if( !in_array( '/usr/share/php', $paths ) ) $paths[] = '/usr/share/php';
  ini_set( 'include_path', implode( ':', $paths ) );

  // script can take some time to run
  set_time_limit( 0 );

  // The following is to inform nginx not to wait till buffer to complete and use gzip
  // but instead flush as soon as information appears and not compress the data
  header( 'X-Accel-Buffering: no' );
  header( 'Content-Encoding: none' );
  ob_implicit_flush( 1 );
  ob_end_clean();

  // Load Net_DNS2 library
  include_once 'Net/DNS2.php';
  include_once 'Net/DNS2/Resolver.php';

  // master server for validation checking
  $testServer = 'ns0.opennic.glue';
  $testServerIP = '173.160.58.202'; // backup if domain resolving fails

  $myDir = '/OpenNIC/testT2';
  $reportDir = '/OpenNIC/testT2';
  if( !is_dir( $myDir ) ) $myDir = __DIR__;
  if( !is_dir( $reportDir ) ) $reportDir = __DIR__;

  $TLDinfo = $myDir . '/tld.info';

  $DEBUG = false;

  // Settings for log.php
  $minScale = 15;

  // Settings for writing data to mySQL
  // @todo
  //$db_name = 't2stats';
  //$db_host = 'localhost';
  //$db_user = 'opennic';
  //$db_pass = 'password';