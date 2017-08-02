'use strict';

const assert = require( 'assert' )
const config = require( '../configs/server.json' )

const ip = require( 'range_check' )
const uuidv4 = require( 'uuid/v4' )
const redis = require( 'redis' )
const redis_client = redis.createClient( { 'host': config.server.redis.host, 'port': config.server.redis.port, 'password': config.server.redis.password, 'prefix': config.server.redis.prefix } )

redis_client.on( 'error', ( err ) => {
  assert.ifError( err )
} )

var functions = {}
module.exports = functions

functions.setTest = ( id, data, cb ) => {
  redis_client.set( id, JSON.stringify( data ), 'EX', config.server.redis.expire, cb )
}

functions.getTest = ( id, cb ) => {
  redis_client.get( id, cb )
}

functions.find_record = ( ldap, family, address, cb ) => {
  var search_options = {
    scope: 'sub',
    sizeLimit: 1,
    attributes: [ 'dc', 'aRecord', 'aAAARecord', 'useDNSCrypt', 'DNSCryptServer', 'DNSCryptKey', 'listenDNSCryptPort', 'listenDNSCryptPort6' ]
  }

  var type = ( 4 == family ) ? 'aRecord' : 'aAAARecord'

  search_options.filter = '(&(' + type + '=' + address + ')(!(zonestatus=DELTETED)))'

  console.log( search_options )

  ldap.search( 'o=servers,dc=opennic,dc=glue', search_options, ( err, result ) => {
    assert.ifError( err )

    var found = false

    result
      .on( 'searchEntry', ( entry ) => {
        found = true
        var json = entry.object
        var id = uuidv4()

        json['testId'] = id

        // store record data so we can require it during multiple ajax calls
        functions.setTest( id, json, ( err, resp ) => {
          return cb( err, 'OK' == resp ? json : null )
        } )
      } )
      .on( 'end', ( result ) => {
        if( false === found )
        {
          // nothing was found return dummy json response
          var id = uuidv4()
          var json = {
            'testId': id,
            'dc': 'unknown',
          }

          json[type] = address

          // cache testId
          functions.setTest( id, json, ( err, resp ) => {
            return cb( err, 'OK' == resp ? json : null )
          } )
        }
      } )

  } )


}

