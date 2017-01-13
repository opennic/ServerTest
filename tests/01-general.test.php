<?php

  /**
   *
   * Here is a general set of tests for OpenNIC DNS servers
   *
   * Tests:
   * 1) Determine the time it takes to execute a SOA on the root zone
   * 2) Check the reply size from a DNS query
   * 3) Verify DNS server uses random ports
   * 4) Make sure DNS server is obfuscating their version
   * 5) Invalid domain names should report back NXDOMAIN
   *
   **/

  // If a domain name was entered instead of an IP, display both to the user
  $display_domain = '';
  if( !empty( $domain ) )
    $display_domain = ' (' . $domain . ')';

  echo <<< HTML
    <b>Test results for {$ip_addr}{$display_domain}</b>
    <br />
    <br />
    <b>General tests</b> (does not affect server status)
    <br />

HTML;

  doflush();

  // check to see if $testServer is a FQDN, if so, resolve it for our DNS object
  if( !$resolv->isIPv4( $testServer ) && !$resolv->isIPv6( $testServer ) )
  {
    // Attempt to resolve testserver to an IP address
    try
    {
      $out = $resolv->query( $testServer, 'A', 'IN' );
      $testServer = $out->answer[0]->address; // we only need one IP
    }
    catch( Net_DNS2_Exception $e )
    {
      if( !empty( $testServerIP ) )
      {
        // revert to config $testServerIP (backup)
        $testServer = $testServerIP;
      }
    }
  }

  // assign NS to IP passed in from user
  $resolv->setServers( [ $ip_addr ] );
  $resolv->timeout = 10;

  // this is temp until Net_DNS2 implements $out->query_time
  try { $out = $resolv->query( '.', 'SOA', 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e->getResponse(); }

  $qTime = 0;
  if( !empty( $out ) )
  {
    $qTime = round( $out->response_time * 1000 );
    $qTime .= ' msec' . ( ( $qTime > 1 ) ? 's' : '' );
  }

  if( $qTime )
  {
    echo <<< HTML
    <div class="gen">
      <span class="clr glass">{$qTime}</span>
    </div>

HTML;
  }
  else
  {
    $output = showerr( $out );

    echo <<< HTML
    <div class="fail">
      <span class="red glass">Connection failed</span>
      <div class="dig">dig {$md} SOA . @{$ip_addr}</div>
      <ul>
        {$output}
      </ul>
    </div>

HTML;
  }
  doflush();

  if( $qTime )
  {
    // https://www.dns-oarc.net/oarc/services/replysizetest
    // To run this test with Nominum CNS resolver:
    // dig tcf.rs.dns-oarc.net TXT @$ip_addr
    $replySize = 0;

    try { $out = $resolv->query( 'rs.dns-oarc.net', 'TXT', 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e->getResponse(); }

    if( !empty( $out->answer ) )
    {
      foreach( $out->answer as $answer )
      {
        if( isset( $answer->text ) )
        {
          // DNS reply size limit is at least 4090
          // "208.115.243.34 DNS reply size limit is at least 4064"
          // "208.115.243.34 DNS reply size limit is at least 4064 bytes"
          if( preg_match( '/DNS\sreply\ssize\slimit\sis\sat\sleast\s(\d+(\sbytes)?)/i', $answer->text[0], $match ) )
          {
            $replySize = $match[1];
          }
        }
      }
    }

    if( empty( $replySize ) )
    {
      $tmp = showerr( $out );

      $output = '<li><i>no results returned</i></li>';
      if( $tmp )
        $output = $tmp;

      echo <<< HTML
    <div class="fail">
      <span class="yel glass">Reply size: ???</span>
      <div class="dig">dig {$md} +short rs.dns-oarc.net TXT @{$ip_addr}</div>
      <ul>
        {$output}
      </ul>
    </div>

HTML;
    }
    elseif( $replySize < 3500 )
    {
      $output = '<li>This value may indicate outdated DNS software</li>';
      if( $replySize )
        $output = '<li>Value should be around 4000</li>';
      elseif( $replySize < 512 )
        $output = '<li>Your server or router may not support EDNS</li>';
      elseif( $replySize < 1400 )
        $output = '<li>Firewall may be filtering IP fragments</li>';

      $output .= '<li><a href="https://www.dns-oarc.net/oarc/services/replysizetest">Please read this page for more information</a></li>';

      echo <<< HTML
    <div class="fail">
      <span class="yel glass">Reply size: {$replySize}</span>
      <ul>
        {$output}
      </ul>
    </div>

HTML;
    }
    else
    {
      echo <<< HTML
    <div class="gen">
      <span class="clr glass">Reply size: {$replySize}</span>
    </div>

HTML;
    }
    doflush();

    // https://www.dns-oarc.net/oarc/services/porttest
    $portRand = '';

    try { $out = $resolv->query( 'porttest.dns-oarc.net', 'TXT', 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e->getResponse(); }

    $portRand = '';
    if( !empty( $out ) && !empty( $out->answer ) )
    {
      foreach( $out->answer as $answer )
      {
        if( isset( $answer->text ) )
        {
          if( preg_match( '/is\s([^\s]+):/i', $answer->text[0], $match ) )
          {
            $portRand = $match[1];
          }
        }
      }
    }

    switch( $portRand )
    {
      case '':

        $tmp = showerr( $out );

        $output = '<li><i>no results returned</i></li>';
        if( $tmp )
          $output = $tmp;

        echo <<< HTML
    <div class="fail">
      <span class="yel glass">Port rand: ???</span>
      <div class="dig">dig {$md} +short porttest.dns-oarc.net TXT @{$ip_addr}</div>
      <ul>
        {$output}
      </ul>
    </div>

HTML;
      break;
      case 'POOR':
        $failed_tests['portrand'] = 1;
        echo <<< HTML
    <div class="fail">
      <span class="yel glass">Port rand: {$portRand}</span>
      <div class="dig">dig {$md} +short porttest.dns-oarc.net TXT @{$ip_addr}</div>
      <ul>
        <li><a href="https://www.dns-oarc.net/oarc/services/porttest">Please read this page for more information</a></li>
      </ul>
    </div>

HTML;
      default:
        echo <<< HTML
    <div class="gen">
      <span class="clr glass">Port rand: {$portRand}</span>
    </div>

HTML;
    }
    doflush();

    // Security testing //
    try { $out = $resolv->query( 'version.bind', 'TXT', 'CH' ); } catch( Net_DNS2_Exception $e ) { $out = false; }

    // check to see if there is some kind of version numbers in the reply, warn if we detect any
    $regex = '/(\d{1,2})\.(\d{1,2})(\.(\d{1,2})|\-([a-zA-Z0-9]+))?/i';

    if( !empty( $out ) && preg_match( $regex, $out->answer[0]->text[0], $r ) === 1 )
    {
      echo <<< HTML
    <div class="fail">
      <span class="blu glass">Version: WARNING</span>
      <div class="dig">dig {$md} version.bind TXT chaos +short @{$ip_addr}</div>
      <ul>
        <li>Potential version information: <i>"{$out->answer[0]->text[0]}"</i></li>
        <li><i>Add to your named.conf: version "[hidden]";</i></li>
      </ul>
    </div>

HTML;
    }
    else
    {
      echo <<< HTML
    <div class="gen">
      <span class="clr glass">Version: OK</span>
    </div>

HTML;
    }

    doflush();

    echo <<< HTML
    <br />
    <b>Results for unknown domains</b> (should be NXDOMAIN)<br />

HTML;
    doflush();

    // NXDOMAIN test //

    try { $out = $resolv->query( 'test.123', 'A', 'IN' ); $rcode = $out->header->rcode; } catch( Net_DNS2_Exception $e ) { $rcode = $e->getResponse()->header->rcode; }

    // rcode values
    // http://www.ietf.org/rfc/rfc1035.txt

    switch( $rcode )
    {
      case Net_DNS2_Lookups::RCODE_NXDOMAIN:        // 3
        echo <<< HTML
    <div class="gen">
      <span class="clr glass">NXDOMAIN</span>
    </div>

HTML;
        break;
      default:

        switch( $rcode )
        {
          case Net_DNS2_Lookups::RCODE_NOERROR:  // 0
            $output = 'NOERROR';
            break;
          case Net_DNS2_Lookups::RCODE_FORMERR:  // 1
            $output = 'FORMERR';
            break;
          case Net_DNS2_Lookups::RCODE_SERVFAIL: // 2
            $output = 'SERVFAIL';
            break;
          case Net_DNS2_Lookups::RCODE_NOTIMP:   // 4
            $output = 'NOTIMP';
            break;
          case Net_DNS2_Lookups::RCODE_REFUSED:  // 5
            $output = 'REFUSED';
            break;
          case '':
            $output = 'No Result';
            break;
          default:
            $output = 'Unknown: ' . $rcode;
            break;
        }

        echo <<< HTML
    <div class="gen">
      <span class="red glass">{$output}</span>
    </div>
    <div class="dig">dig test.123 @{$ip_addr}</div>

HTML;

        $failed_tests['NXDOMAIN'] = 1;
        break;
    }
  }
