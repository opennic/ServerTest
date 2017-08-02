#!/usr/bin/env node

'use strict';

const assert = require( 'assert' )
const config = require( '../configs/server.json' )
const functions = require( '../libs/functions' )

const express = require( 'express' )
const parser = require( 'body-parser' )
const app = express()
const base = express()

base
  .use( parser.json() )
  .use( parser.urlencoded( { extended: true } ) )

const ldap = require( 'ldapjs' )
const dns = require( 'dns' )
const ip = require( 'range_check' )
const uuidv4 = require( 'uuid/v4' )

const testTypes = [
  'general',
  'openic',
  'icann',
  'dnscrypt'
]

const ldap_client = ldap.createClient( {
  url: config.server.ldap.host,
  tlsOptions: config.server.ldap.tlsOptions
} )

ldap_client
  .once( 'error', ( err ) => {
    assert.ifError( err )
  } )
  .once( 'connect', () => {
    ldap_client.bind( config.server.ldap.auth.username, config.server.ldap.auth.password, ( err ) => {
      assert.ifError( err )

      // @todo:
      // maybe have a fail safe if ldap is done that we can still do manual tests
      start_server() // obviously start the server now we are connected to ldap
    } )
  } )

function start_server()
{

  // this simply checks to see if the the submitted address exists in the system
  base.post( '/check', ( req, res ) => {
    console.log( req.body )

    var address = req.body.address
    var type = req.body.type

    dns.lookup( address, { all: true }, ( err, addresses ) => {
      assert.ifError( err )

      addresses.every( ( a ) => {
        // @todo:
        // Make this emit different events based on found and not found
        functions.find_record( ldap_client, a.family, a.address, ( err, record ) => {
          assert.ifError( err )

          console.log( record )
          res.send( { 'status': 'ok', 'data': record } )
          return true
        } )
      } )
    } )
  } )

  // place holder to run each test section
  base.post( '/test', ( req, res ) => {

    var id = req.body.id
    var test = req.body.test

    // get test info from redis
    functions.getTest( id, ( err, data ) => {
      assert.ifError( err )

      var json = JSON.parse( data )

      console.log( json )

      res.send( { 'status': 'ok', 'data': json } )
    } )

  } )

  app.use( '/node', base )

  app.listen( 4096, () => {
    console.log( 'Server running...' )
  } )
}