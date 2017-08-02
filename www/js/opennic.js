$( function() {
  var resp1 = $( 'div#ajax1' );
  var resp2 = $( 'div#ajax2' );

  $( 'button#test_fqdn' ).on( 'click', function( ev ) {
    resp1.html(''); resp2.html('');
    $.ajax( {
      url: '/node/check',
      method: 'POST',
      dataType: 'json',
      data: {
        'address': 'ns4.ca.dns.opennic.glue',
        'type': 't2'
      }
    } )
    .done( function( json ) {
      if( json.status == 'ok' )
      {
        resp1.html( 'Found: ' + json.data.dc + ' (' + json.data.aRecord + ')' );
        $.ajax( {
          url: '/node/test',
          method: 'POST',
          datatype: 'json',
          data: {
            'id': json.data.testId,
            'test': 'general'
          }
        } )
        .done( function( json ) {
          resp2.html( 'Confirm: ' + json.data.dc + ' (' + json.data.aRecord + ')' );
        } );
      }
      else
      {
        resp1.html( 'Unable to find record' );
      }
    } );
  } );

  $( 'button#test_ip' ).on( 'click', function( ev ) {
    resp1.html(''); resp2.html('');
    $.ajax( {
      url: '/node/check',
      method: 'POST',
      dataType: 'json',
      data: {
        'address': '142.4.204.111',
        'type': 't2'
      }
    } )
    .done( function( json ) {
      if( json.status == 'ok' )
      {
        resp1.html( 'Found: ' + json.data.dc + ' (' + json.data.aRecord + ')' );
        $.ajax( {
          url: '/node/test',
          method: 'POST',
          datatype: 'json',
          data: {
            'id': json.data.testId,
            'test': 'general'
          }
        } )
        .done( function( json ) {
          resp2.html( 'Confirm: ' + json.data.dc + ' (' + json.data.aRecord + ')' );
        } );
      }
      else
      {
        resp1.html( 'Unable to find record' );
      }
    } );
  } );

  $( 'button#test_unfqdn' ).on( 'click', function( ev ) {
    resp1.html(''); resp2.html('');
    $.ajax( {
      url: '/node/check',
      method: 'POST',
      dataType: 'json',
      data: {
        'address': 'ns3.laodc.com',
        'type': 't2'
      }
    } )
    .done( function( json ) {
      if( json.status == 'ok' )
      {
        resp1.html( 'Found: ' + json.data.dc + ' (' + json.data.aRecord + ')' );
        $.ajax( {
          url: '/node/test',
          method: 'POST',
          datatype: 'json',
          data: {
            'id': json.data.testId,
            'test': 'general'
          }
        } )
        .done( function( json ) {
          resp2.html( 'Confirm: ' + json.data.dc + ' (' + json.data.aRecord + ')' );
        } );
      }
      else
      {
        resp1.html( 'Unable to find record' );
      }
    } );
  } );

  $( 'button#test_unip' ).on( 'click', function( ev ) {
    resp1.html(''); resp2.html('');
    $.ajax( {
      url: '/node/check',
      method: 'POST',
      dataType: 'json',
      data: {
        'address': '185.109.87.20',
        'type': 't2'
      }
    } )
    .done( function( json ) {
      if( json.status == 'ok' )
      {
        resp1.html( 'Found: ' + json.data.dc + ' (' + json.data.aRecord + ')' );
        $.ajax( {
          url: '/node/test',
          method: 'POST',
          datatype: 'json',
          data: {
            'id': json.data.testId,
            'test': 'general'
          }
        } )
        .done( function( json ) {
          resp2.html( 'Confirm: ' + json.data.dc + ' (' + json.data.aRecord + ')' );
        } );
      }
      else
      {
        resp1.html( 'Unable to find record' );
      }
    } );
  } );
} );