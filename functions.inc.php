<?php

  function array_find( $needle, $haystack )
  {
    foreach( $haystack as $key => $value )
    {
      if( stripos( $value, $needle ) !== false )
        return $key;
    }
    return false;
  }

  function doflush()
  {
    //ob_flush();
    //flush();
  }

  function showerr( $err, $add = '' )
  {
    $output = $msg = '';
    $failtype = 0;

    $class = ( is_object( $err ) ) ? get_class( $err ) : null;

    switch( $class )
    {
      case 'Net_DNS2_Packet_Response':
        if( $err->header->ra == 0 )
        {
          $failtype = 1;
          $msg = 'WARNING: recursion requested but not available';
        }
        elseif( $err->header->rcode > 0 )
        {
          $failtype = 1;
          $msg = 'Recv: ';
          switch( $err->header->rcode )
          {
            case Net_DNS2_Lookups::RCODE_NOERROR:  // 0
              $msg .= 'NOERROR';
              break;
            case Net_DNS2_Lookups::RCODE_FORMERR:  // 1
              $msg .= 'FORMERR';
              break;
            case Net_DNS2_Lookups::RCODE_SERVFAIL: // 2
              $msg .= 'SERVFAIL';
              break;
            case Net_DNS2_Lookups::RCODE_NXDOMAIN: // 3
              $msg .= 'NXDOMAIN';
              break;
            case Net_DNS2_Lookups::RCODE_NOTIMP:   // 4
              $msg .= 'NOTIMP';
              break;
            case Net_DNS2_Lookups::RCODE_REFUSED:  // 5
              $msg .= 'REFUSED';
              break;
            case '':
              $msg .= 'NO RESULT';
              break;
            default:
              $msg .= 'UNKNOWN: ' . $out->header->rcode;
              break;
          }
        }
        break;
      case 'Net_DNS2_Exception':
        $failtype = 1;
        $msg = $err->getMessage();
        break;
    }

    switch( $failtype )
    {
      case 0: // unknown error, output info
        $output .= $add;
        foreach( $err->header as $k => $data )
        {
          if( $data )
          {
            $output .= '<li>Rcvd: ' . $k . ' = ' . $data . '</li>';
          }
        }
        break;

      case 1: // known error, output response
        $output .= '<li><i>' . $msg . '</i></li>';
        break;
    }

    return $output;
  }

  function getinfo( &$resolv, $target, $type = 'SOA', $match = array(), $option = array() )
  {
    global $testtype;

    $resolv->setServers( array( $option['ns'] ) );

    $target = trim( $target );

    $err = false;
    $msg = $tmp = $testerr = '';

    // ----
    // recurse feature disabled while testing, was getting odd results
    // ----

    //$resolv->recurse = false;
    //if( $option == 'recurse' )
    //  $resolv->recurse = true;

    try { $out = $resolv->query( $target, $type, 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e; $err = true; }

    if( $testtype == 'T2' && empty( $out ) )
    {
      //$recurse = $resolv->recurse;
      //$resolv->recurse = false;

      try { $out = $resolv->query( $target, $type, 'IN' ); } catch( Net_DNS2_Exception $e ) { $out = $e; $err = true; }

      //$resolv->recurse = $recurse;
      $msg = ' recursively';
    }

    $pass = false;

    $section = 'answer';
    $field = $match[0];

    // check to see if we check another section besides 'answer'
    if( is_array( $match[0] ) )
    {
      $section = $match[0][0];
      $field = $match[0][1];
    }

    if( !empty( $match[1] ) && !empty( $out ) && !empty( $out->{$section} ) )
    {
      // required amount of results to be classified as a pass
      $req = intval( count( $match[1] ) / 1.5 );

      $answers = array();

      foreach( $out->{$section} as $answer )
      {
        if( isset( $answer->{$field} ) ) $answers[] = $answer->{$field};
      }

      $count = 0;
      foreach( $match[1] as $answer )
      {
        if( is_object( $answer ) ) $answer = $answer->{$field};

        if( in_array( $answer, $answers ) )
        {
          $count++;
        }
      }

      if( $count >= $req ) $pass = true;
    }

    if( $pass === false )
    {
      $class = get_class( $out );

      switch( $class )
      {
        case 'Net_DNS2_Exception':
          // @todo
          // Maybe only use this if we were able to get any useful information from below
          // $tmp .= '<li>' . $out->getMessage() . '</li>';
          $out = $out->getResponse();
          break;
      }

      $tmp = '';

      if( is_object( $out->header ) )
      {
        switch( $out->header->rcode )
        {
          case Net_DNS2_Lookups::RCODE_FORMERR:  // 1
            $tmp .= '<li>Recv: FORMERR</li>';
            break;
          case Net_DNS2_Lookups::RCODE_SERVFAIL: // 2
            $tmp .= '<li>Recv: SERVFAIL</li>';
            break;
          case Net_DNS2_Lookups::RCODE_NXDOMAIN: // 3
            $tmp .= '<li>Recv: NXDOMAIN</li>';
            break;
          case Net_DNS2_Lookups::RCODE_NOTIMP:   // 4
            $tmp .= '<li>Recv: NOTIMP</li>';
            break;
          case Net_DNS2_Lookups::RCODE_REFUSED:  // 5
            $tmp .= '<li>Recv: REFUSED</li>';
            break;
          default:
            if( $out->header->ra == 0 )
            {
              $tmp .= '<li>WARNING: recursion requested but not available</li>';
            }
            break;
        }
      }

      if( !empty( $out->{$section} ) )
      {
        $tmp .= '<li>Recv: ';
        foreach( $out->{$section} as $answer )
        {
          $value = $answer->{$field};
          $tmp .= '"' . $value . '" ';
        }
        $tmp .= '</li>';
      }

      if( !empty( $match[1] ) )
      {
        $tmp .= '<li>';
        if( is_array( $match[1] ) )
        {
          $tmp .= 'Expecting: ';
          foreach( $match[1] as $ip )
          {
            $value = $ip->{$field};
            $tmp .= '"' . $value . '" ';
          }
        }
        else
        {
          $tmp .= 'Expecting "' . $match[1] . '"';
        }
        $tmp .= '</li>';
      }

      $testerr .= <<< HTML
    <div class="dig">dig $type $target @{$option['ns']} +short</div>
    <ul>
      {$tmp}
    </ul>

HTML;

      return $testerr;
    }

    return false;
  }