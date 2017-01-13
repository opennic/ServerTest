<?php

  if( $qTime )
  {
    $opennic_tests = [
      'opennic.oss',
      'grep.geek',
      'reg.for.free',
      'reg.pirate',
      'domaincoin.bit',
    ];

    echo <<< HTML
    <br />
    <b>OpenNIC root zones</b>
    <br />

HTML;

    doflush();

    if( file_exists( $TLDinfo ) )
    {
      $file = file( $TLDinfo );

      $i = 0;
      foreach( $file as $line )
      {
        $line = trim( $line );
        if( substr( $line, - 1, 1 ) != '.' )
          $line .= '.';

        $tests = [
          'SOA',
          'NS',
        ];

        $failed = true;
        $testerr = '';
        $msg = '';
        foreach( $tests as $TYPE )
        {
          $err = false;

          $resolv->setServers( [ $testServer ] );
          $resolv->use_tcp = true;
          try { $out = $resolv->query( $line, $TYPE, 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e; $err = true; }

          if( empty( $out ) || empty( $out->answer ) )
          {
            $err = false;
            $resolv->setServers( Net_DNS2::RESOLV_CONF );
            try { $out = $resolv->query( $line, $TYPE, 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e; $err = true; }
          }

          if( $err == false )
          {
            switch( $TYPE )
            {
              case 'SOA':
                $failed = getinfo( $resolv, $line, $TYPE, [ 'mname', $out->answer ], [ 'ns' => $ip_addr ] );
                if( $failed !== false )
                {
                        break 2;
                }
                break;
              case 'NS':
                $failed = getinfo( $resolv, $line, $TYPE, [ 'nsdname', $out->answer ], [ 'ns' => $ip_addr ] );
                if( $failed !== false )
                {
                        break 2;
                }
                break;
            }
          }
          else
          {
            // this scenario only happens when both testserver and NS assigned on resolv.conf are unable to return a response
            $failed = '<div class="dig">dig ' . $TYPE . ' ' . $line . ' @' . $testServer . ' +short</div>';
            $failed .= showerr( $out->getResponse() );
          }
        }

        if( $failed !== false )
          $failed_tests[$line] = 1;

        if( $line == '.' )
          $line = 'root zone';

        $line = trim( $line, '.' );

        if( $failed !== false )
        {
          echo <<< HTML
    <div class="fail">
      <span class="red glass">{$line}</span>
      {$failed}
    </div>

HTML;
        }
        else
        {
          echo <<< HTML
    <span class="grn glass">{$line}</span>

HTML;
        }

        doflush();
      }
    }

    echo <<< HTML
    <br />
    <br />
    <b>OpenNIC domains</b>
    <br />

HTML;

    doflush();

    $failed = 0;
    $A = 'A';

    foreach( $opennic_tests as $url )
    {
      $err = false;
      $testerr = false;

      // reset server back to test server
      $resolv->setServers( [ $testServer ] );

      try { $out = $resolv->query( $url, $A, 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e; $err = true; }

      // master failed to get a result.
      if( empty( $out->answer ) ) { $err = true; }

      if( $err === false )
      {
        // if authoritive (T1), we pass it
        if( $testtype == 'T1' )
        {
          $result  = getinfo( $resolv, $url, $A, [ [ 'header', 'aa' ], $out->header ], [ 'ns' => $ip_addr ] );
        }
        else
        {
          $result  = getinfo( $resolv, $url, $A, [ [ 'header', 'qa' ], $out->header ], [ 'ns' => $ip_addr ] );
          $result .= getinfo( $resolv, $url, $A, [ 'address', $out->answer ], [ 'ns' => $ip_addr ] );
        }

        if( !empty( $result ) ) { $testerr = $result; $failed++; }
      }
      else
      {
        $testerr = '<ul>' . showerr( $out ) . '</ul>';
      }

      $url = trim( $url, '.' );

      if( $testerr !== false )
      {
        echo <<< HTML
    <div class="fail">
      <span class="red glass">{$url}</span>
      {$testerr}
    </div>

HTML;
      }
      else
      {
        echo <<< HTML
    <span class="grn glass">{$url}</span>

HTML;
      }

      doflush();
    }

    if( $failed > ( count( $tests[$A] ) * 0.5 ) ) $failed_tests['ICANN'] = 1;
  }