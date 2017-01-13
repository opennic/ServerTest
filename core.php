<?php

  if( empty( $testtype ) )
  {
    $testtype = 'T2';
    $title = 'Tier-2';
  }

  include_once __DIR__ . '/server.conf.php';
  include_once __DIR__ . '/functions.inc.php';

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?=$title;?> OpenNIC Server Test</title>
    <link href="style.min.css" media="all" rel="stylesheet" />
  </head>
  <body>
    <div class="header">
      <?=$title;?> OpenNIC DNS Server Test
    </div>
<?php

    $resolv = new Net_DNS2_Resolver( [ 'timeout' => 2, 'cache_type' => 'none' ] );

    $mode = intval( @$_REQUEST['mode'] );

    $md = '';
    if( $mode == 4 )
      $md = '-4';
    if( $mode == 6 )
      $md = '-6';

    if( empty( $_REQUEST['ip_addr'] ) )
    {
      echo <<< HTML
    <div class="form">
      Please enter in a FQDN/IP Address to run tests with OpenNIC.
      <form method="get" action="{$_SERVER['PHP_SELF']}">
        <input type="text" name="ip_addr" />
        <input type="submit" value="Test server" />
      </form>
    </div>
  </body>
</html>
HTML;
      die();
    }

    // Parse $ip_addr to remove any command injections
    $ip_addr = filter_var( trim( @$_REQUEST['ip_addr'] ), FILTER_VALIDATE_IP );
    $domain = '';

    $v4 = $resolv->isIPv4( $ip_addr );
    $v6 = $resolv->isIPv6( $ip_addr );

    // Possible FQDN supplied instead of IP
    if( $v4 || $mode == 4 )
      $A = 'A';

    if( $v6 || $mode == 6 )
      $A = 'AAAA';

    if( $ip_addr === false || ( $v4 === false && $v6 === false ) )
    {
      try
      {
        $ip = $resolv->query( trim( @$_REQUEST['ip_addr'] ), 'A', 'IN' );
        if( empty( $ip->answer ) )
        {
          $ip = $resolv->query( trim( @$_REQUEST['ip_addr'] ), 'AAAA', 'IN' );
        }

        if( !empty( $ip->answer ) )
        {
          $ip_addr = $ip->answer[0]->address;
          $v4 = $resolv->isIPv4( $ip_addr );
          $v6 = $resolv->isIPv6( $ip_addr );
          $domain = trim( @$_REQUEST['ip_addr'] );
        }
      }
      catch( Net_DNS2_Exception $e )
      {
        $ip_addr = false;
      }
    }

    if( empty( $ip_addr ) )
    {
      echo <<< HTML
    <b>Please return to the previous page and enter a valid IP address or hostname</b>
    <br />
  </body>
</html>

HTML;
      die();
    }

    $failed_tests = array();
    foreach( scandir( __DIR__ . '/tests/' ) as $filename )
    {
      // don't try to process any folders (including . and ..)
      if( is_dir( __DIR__ . '/tests/' . $filename ) ) continue;

      // include our test script
      include_once __DIR__ . '/tests/' . $filename;
    }

    echo <<< HTML
    <br />
    <br />
    <b>Test results:

HTML;

    if( count( $failed_tests ) > 0 || empty( $qTime ) )
    {
      echo <<< HTML
    <div class="red glass" style="display: inline-block;">Failed</div>
    </b>
    <br />

HTML;
    }
    else
    {
      echo <<< HTML
    <div class="grn glass">Passed</div>
    </b>
    <br />

HTML;
        }

?>
  </body>
</html>