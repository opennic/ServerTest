<?php

  if( $testtype == 'T2' )
  {
    if( !empty( $qTime ) )
    {
      $tests = array(
        'A' => array(
          'yahoo.com',
          'wikipedia.org',
          '360.cn',
          'www.abs.gov.au',
          'biglobe.ne.jp',
          //'google.com',
          //'www.bbc.co.uk',
        ),
        'AAAA' => array(
          'ipv6.he.net',
          'facebook.com',
          'google.com',
          'bin6.it',
          'ipv6.netflix.com',
          //'ipv6.cnn.com',
          //'ipv6.plurk.com',
          //'ipv6.boioiong.com',
          //'testv6.cdlt.com.ar',
        ),
      );

      $A = 'A';
      $display = ' (IPv4)';

      if( $v6 || $mode == 6 )
      {
        $A = 'AAAA';
        $display = ' (IPv6)';
      }

      echo <<< HTML
    <br />
    <br />
    <b>ICANN Domains{$display}</b>
    <br />

HTML;
      doflush();

      $failed = 0;

      foreach( $tests[$A] as $url )
      {
        $err = false;
        $testerr = false;

        // reset server back to test server
        $resolv->setServers( array( $testServer ) );

        try { $out = $resolv->query( $url, $A, 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e; $err = true; }

        // master failed to get a result.
        if( empty( $out->answer ) ) { $err = true; }

        if( $err === false )
        {
          $result  = getinfo( $resolv, $url, $A, array( array( 'header', 'qa' ), $out->header ), array( 'ns' => $ip_addr ) );
          $result .= getinfo( $resolv, $url, $A, array( 'address', $out->answer ), array( 'ns' => $ip_addr ) );
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
  }