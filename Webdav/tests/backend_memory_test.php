<?php
/**
 * Basic test cases for the memory backend.
 *
 * @package Webdav
 * @subpackage Tests
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Reqiuire base test
 */
require_once 'test_case.php';

/**
 * Tests for ezcWebdavMemoryBackend class.
 * 
 * @package Webdav
 * @subpackage Tests
 */
class ezcWebdavMemoryBackendTest extends ezcWebdavTestCase
{
	public static function suite()
	{
		return new PHPUnit_Framework_TestSuite( 'ezcWebdavMemoryBackendTest' );
	}

    public function testEmptyMemoryServerCreation()
    {
        $backend = new ezcWebdavMemoryBackend();

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(),
            ),
            'Expected empty content array.'
        );

        $props = $this->readAttribute( $backend, 'props' );
        $this->assertEquals(
            $props,
            array(),
            'Expected empty property array.'
        );

        $this->assertSame(
            0,
            $backend->getFeatures(),
            'Memory backend should not support any special features.'
        );
    }

    public function testFileListMemoryServerCreation()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'blubb' => 'Somme blubb blubbs.',
            'ignored',
            'ignored' => true,
        ) );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/foo',
                    '/blubb',
                ),
                '/foo' => 'bar',
                '/blubb' => 'Somme blubb blubbs.',
            )
        );

        $props = $this->readAttribute( $backend, 'props' );
        $this->assertEquals(
            $props,
            array(
                '/foo' => new ezcWebdavPropertyStorage(),
                '/blubb' => new ezcWebdavPropertyStorage(),
            ),
            'Expected empty property array.'
        );
    }

    public function testCollectionMemoryServerCreation()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/foo',
                    '/bar',
                ),
                '/foo' => 'bar',
                '/bar' => array(
                    '/bar/blubb',
                ),
                '/bar/blubb' => 'Somme blubb blubbs.',
            )
        );

        $props = $this->readAttribute( $backend, 'props' );
        $this->assertEquals(
            $props,
            array(
                '/foo' => new ezcWebdavPropertyStorage(),
                '/bar' => new ezcWebdavPropertyStorage(),
                '/bar/blubb' => new ezcWebdavPropertyStorage(),
            ),
            'Expected empty property array.'
        );
    }

    public function testFakedLiveProperties()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->options->fakeLiveProperties = true;
        $backend->addContents( array(
            'foo' => 'bar',
        ) );

        // Expected properties
        $propertyStorage = new ezcWebdavPropertyStorage();
        $propertyStorage->attach(
            new ezcWebdavCreationDateProperty( new DateTime( '@1054034820' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavDisplayNameProperty( 'foo' )
        );
        $propertyStorage->attach(
            new ezcWebdavGetContentLanguageProperty( array( 'en' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavGetContentTypeProperty( 'application/octet-stream' )
        );
        $propertyStorage->attach(
            new ezcWebdavGetEtagProperty( md5( '/foo' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavGetLastModifiedProperty( new DateTime( '@1124118780' ) )
        );

        $props = $this->readAttribute( $backend, 'props' );
        $this->assertEquals(
            $props,
            array(
                '/foo' => $propertyStorage,
            ),
            'Expected filled property array.'
        );
    }

    public function testMemoryBackendOptionsInMemoryBackend()
    {
        $server = new ezcWebdavMemoryBackend();

        $this->assertEquals(
            $server->options,
            new ezcWebdavMemoryBackendOptions(),
            'Expected initially unmodified backend options class.'
        );

        $this->assertSame(
            $server->options->fakeLiveProperties,
            false,
            'Expected successfull access on option.'
        );

        try
        {
            // Read access
            $server->unknownProperty;
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            return true;
        }

        $this->fail( 'Expected ezcBasePropertyNotFoundException.' );
    }

    public function testMemoryBackendOptionsSetInMemoryBackend()
    {
        $server = new ezcWebdavMemoryBackend();

        $options = new ezcWebdavMemoryBackendOptions();
        $options->fakeLiveProperties = true;

        $this->assertSame(
            $server->options->fakeLiveProperties,
            false,
            'Wrong initial value before changed option class.'
        );

        $server->options = $options;

        $this->assertSame(
            $server->options->fakeLiveProperties,
            true,
            'Expected modified value, because of changed option class.'
        );

        try
        {
            $server->unknownProperty = $options;
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            return true;
        }

        $this->fail( 'Expected ezcBasePropertyNotFoundException.' );
    }

    public function testSettingProperty()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->options->fakeLiveProperties = true;
        $backend->addContents( array(
            'foo' => 'bar',
        ) );

        $backend->setProperty( 
            '/foo',
            new ezcWebdavDeadProperty( 'wcv:', 'ctime', '123456' )
        );

        // Expected properties
        $propertyStorage = new ezcWebdavPropertyStorage();
        $propertyStorage->attach(
            new ezcWebdavCreationDateProperty( new DateTime( '@1054034820' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavDisplayNameProperty( 'foo' )
        );
        $propertyStorage->attach(
            new ezcWebdavGetContentLanguageProperty( array( 'en' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavGetContentTypeProperty( 'application/octet-stream' )
        );
        $propertyStorage->attach(
            new ezcWebdavGetEtagProperty( md5( '/foo' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavGetLastModifiedProperty( new DateTime( '@1124118780' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavDeadProperty( 'wcv:', 'ctime', '123456' )
        );

        $props = $this->readAttribute( $backend, 'props' );
        $this->assertEquals(
            $props,
            array(
                '/foo' => $propertyStorage,
            ),
            'Expected filled property array.'
        );
    }

    public function testSettingPropertyOnUnknownRessource()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->options->fakeLiveProperties = true;
        $backend->addContents( array(
            'foo' => 'bar',
        ) );

        $this->assertFalse( 
            $backend->setProperty( 
                '/bar',
                new ezcWebdavDeadProperty( 'wcv:', 'ctime', '123456' )
            ),
            'Setting on unknown ressource sould return false.'
        );
    }

    public function testResourceHead()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavHeadRequest( '/foo' );
        $request->validateHeaders();
        $response = $backend->head( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavHeadResponse(
                new ezcWebdavResource(
                    '/foo', new ezcWebdavPropertyStorage()
                )
            ),
            'Expected response does not match real response.'
        );
    }

    public function testCollectionHead()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavHeadRequest( '/bar' );
        $request->validateHeaders();
        $response = $backend->head( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavHeadResponse(
                new ezcWebdavCollection(
                    '/bar', new ezcWebdavPropertyStorage()
                )
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceHeadError()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavHeadRequest( '/unknown' );
        $request->validateHeaders();
        $response = $backend->head( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_404,
                '/unknown'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceGet()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavGetRequest( '/foo' );
        $request->validateHeaders();
        $response = $backend->get( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavGetResourceResponse(
                new ezcWebdavResource(
                    '/foo', new ezcWebdavPropertyStorage(), 'bar'
                )
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceGetError()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavGetRequest( '/unknown' );
        $request->validateHeaders();
        $response = $backend->get( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_404,
                '/unknown'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceGetWithProperties()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->options->fakeLiveProperties = true;
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        // Expected properties
        $propertyStorage = new ezcWebdavPropertyStorage();
        $propertyStorage->attach(
            new ezcWebdavCreationDateProperty( new DateTime( '@1054034820' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavDisplayNameProperty( 'foo' )
        );
        $propertyStorage->attach(
            new ezcWebdavGetContentLanguageProperty( array( 'en' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavGetContentTypeProperty( 'application/octet-stream' )
        );
        $propertyStorage->attach(
            new ezcWebdavGetEtagProperty( md5( '/foo' ) )
        );
        $propertyStorage->attach(
            new ezcWebdavGetLastModifiedProperty( new DateTime( '@1124118780' ) )
        );

        $request = new ezcWebdavGetRequest( '/foo' );
        $request->validateHeaders();
        $response = $backend->get( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavGetResourceResponse(
                new ezcWebdavResource(
                    '/foo', 
                    $propertyStorage,
                    'bar'
                )
            ),
            'Expected response does not match real response.'
        );
    }

    public function testCollectionGet()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
                'blah' => array(
                    'fumdiidudel.txt' => 'Willst du an \'was Rundes denken, denk\' an einen Plastikball. Willst du \'was gesundes schenken, schenke einen Plastikball. Plastikball, Plastikball, ...',
                ),
            )
        ) );

        $request = new ezcWebdavGetRequest( '/bar' );
        $request->validateHeaders();
        $response = $backend->get( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavGetCollectionResponse(
                new ezcWebdavCollection(
                    '/bar', new ezcWebdavPropertyStorage(), array(
                        new ezcWebdavResource(
                            '/bar/blubb', new ezcWebdavPropertyStorage()
                        ),
                        new ezcWebdavCollection(
                            '/bar/blah', new ezcWebdavPropertyStorage()
                        ),
                    )
                )
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceDeepGet()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
                'blah' => array(
                    'fumdiidudel.txt' => 'Willst du an \'was Rundes denken, denk\' an einen Plastikball. Willst du \'was gesundes schenken, schenke einen Plastikball. Plastikball, Plastikball, ...',
                ),
            )
        ) );

        $request = new ezcWebdavGetRequest( '/bar/blah/fumdiidudel.txt' );
        $request->validateHeaders();
        $response = $backend->get( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavGetResourceResponse(
                new ezcWebdavResource(
                    '/bar/blah/fumdiidudel.txt', 
                    new ezcWebdavPropertyStorage(), 
                    'Willst du an \'was Rundes denken, denk\' an einen Plastikball. Willst du \'was gesundes schenken, schenke einen Plastikball. Plastikball, Plastikball, ...'
                )
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceCopy()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/foo', '/dest' );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavCopyResponse(
                false
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceCopyError()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/unknown', '/irrelevant' );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_404,
                '/unknown'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceCopyF()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/foo', '/dest' );
        $request->setHeader( 'Overwrite', 'F' );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavCopyResponse(
                false
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceCopyOverwrite()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/foo', '/bar' );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavCopyResponse(
                true
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceCopyOverwriteFailed()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/foo', '/bar' );
        $request->setHeader( 'Overwrite', 'F' );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_412,
                '/bar'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceCopyDestinationNotExisting()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/foo', '/dum/di' );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_409,
                '/dum/di'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceCopySourceEqualsDest()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/foo', '/foo' );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_403,
                '/foo'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceCopyDepthZero()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'bar' => array(
                '_1' => 'contents',
                '_2' => 'contents',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/bar', '/foo' );
        $request->setHeader( 'Depth', ezcWebdavRequest::DEPTH_ZERO );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavCopyResponse(
                false
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/bar',
                    '/foo',
                ),
                '/bar' => array(
                    '/bar/_1',
                    '/bar/_2',
                ),
                '/bar/_1' => 'contents',
                '/bar/_2' => 'contents',
                '/foo' => array(),
            )
        );
    }

    public function testResourceCopyDepthInfinity()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'bar' => array(
                '_1' => 'contents',
                '_2' => 'contents',
            )
        ) );

        $request = new ezcWebdavCopyRequest( '/bar', '/foo' );
        $request->setHeader( 'Depth', ezcWebdavRequest::DEPTH_INFINITY );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavCopyResponse(
                false
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/bar',
                    '/foo',
                ),
                '/bar' => array(
                    '/bar/_1',
                    '/bar/_2',
                ),
                '/bar/_1' => 'contents',
                '/bar/_2' => 'contents',
                '/foo' => array(
                    '/foo/_1',
                    '/foo/_2',
                ),
                '/foo/_1' => 'contents',
                '/foo/_2' => 'contents',
            )
        );
    }

    public function testResourceCopyDepthInfinityErrors()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'bar' => array(
                '_1' => 'contents',
                '_2' => 'contents',
                '_3' => 'contents',
                '_4' => 'contents',
                '_5' => 'contents',
            )
        ) );

        $backend->options->failingOperations = ezcWebdavMemoryBackendOptions::REQUEST_COPY;
        $backend->options->failForRegexp = '(_[24]$)';

        $request = new ezcWebdavCopyRequest( '/bar', '/foo' );
        $request->setHeader( 'Depth', ezcWebdavRequest::DEPTH_INFINITY );
        $request->validateHeaders();
        $response = $backend->copy( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavMultistatusResponse(
                new ezcWebdavErrorResponse(
                    ezcWebdavResponse::STATUS_423,
                    '/bar/_2'
                ),
                new ezcWebdavErrorResponse(
                    ezcWebdavResponse::STATUS_423,
                    '/bar/_4'
                )
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/bar',
                    '/foo',
                ),
                '/bar' => array(
                    '/bar/_1',
                    '/bar/_2',
                    '/bar/_3',
                    '/bar/_4',
                    '/bar/_5',
                ),
                '/bar/_1' => 'contents',
                '/bar/_2' => 'contents',
                '/bar/_3' => 'contents',
                '/bar/_4' => 'contents',
                '/bar/_5' => 'contents',
                '/foo' => array(
                    '/foo/_1',
                    '/foo/_3',
                    '/foo/_5',
                ),
                '/foo/_1' => 'contents',
                '/foo/_3' => 'contents',
                '/foo/_5' => 'contents',
            )
        );
    }

    public function testResourceMove()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMoveRequest( '/foo', '/dest' );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavMoveResponse(
                false
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceMoveError()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMoveRequest( '/unknown', '/irrelevant' );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_404,
                '/unknown'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceMoveF()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMoveRequest( '/foo', '/dest' );
        $request->setHeader( 'Overwrite', 'F' );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavMoveResponse(
                false
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceMoveOverwrite()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMoveRequest( '/foo', '/bar' );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavMoveResponse(
                true
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceMoveOverwriteFailed()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMoveRequest( '/foo', '/bar' );
        $request->setHeader( 'Overwrite', 'F' );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_412,
                '/bar'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceMoveDestinationNotExisting()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMoveRequest( '/foo', '/dum/di' );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_409,
                '/dum/di'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceMoveSourceEqualsDest()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMoveRequest( '/foo', '/foo' );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_403,
                '/foo'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceMoveDepthInfinity()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'bar' => array(
                '_1' => 'contents',
                '_2' => 'contents',
            )
        ) );

        $request = new ezcWebdavMoveRequest( '/bar', '/foo' );
        $request->setHeader( 'Depth', ezcWebdavRequest::DEPTH_INFINITY );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavMoveResponse(
                false
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/foo',
                ),
                '/foo' => array(
                    '/foo/_1',
                    '/foo/_2',
                ),
                '/foo/_1' => 'contents',
                '/foo/_2' => 'contents',
            )
        );
    }

    public function testResourceMoveDepthInfinityErrors()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'bar' => array(
                '_1' => 'contents',
                '_2' => 'contents',
                '_3' => 'contents',
                '_4' => 'contents',
                '_5' => 'contents',
            )
        ) );

        $backend->options->failingOperations = ezcWebdavMemoryBackendOptions::REQUEST_COPY;
        $backend->options->failForRegexp = '(_[24]$)';

        $request = new ezcWebdavMoveRequest( '/bar', '/foo' );
        $request->setHeader( 'Depth', ezcWebdavRequest::DEPTH_INFINITY );
        $request->validateHeaders();
        $response = $backend->move( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavMultistatusResponse(
                new ezcWebdavErrorResponse(
                    ezcWebdavResponse::STATUS_423,
                    '/bar/_2'
                ),
                new ezcWebdavErrorResponse(
                    ezcWebdavResponse::STATUS_423,
                    '/bar/_4'
                )
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/bar',
                    '/foo',
                ),
                '/bar' => array(
                    '/bar/_1',
                    '/bar/_2',
                    '/bar/_3',
                    '/bar/_4',
                    '/bar/_5',
                ),
                '/bar/_1' => 'contents',
                '/bar/_2' => 'contents',
                '/bar/_3' => 'contents',
                '/bar/_4' => 'contents',
                '/bar/_5' => 'contents',
                '/foo' => array(
                    '/foo/_1',
                    '/foo/_3',
                    '/foo/_5',
                ),
                '/foo/_1' => 'contents',
                '/foo/_3' => 'contents',
                '/foo/_5' => 'contents',
            )
        );
    }

    public function testResourceDelete()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavDeleteRequest( '/foo' );
        $request->validateHeaders();
        $response = $backend->delete( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavDeleteResponse(
                '/foo'
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/bar',
                ),
                '/bar' => array(
                    '/bar/blubb',
                ),
                '/bar/blubb' => 'Somme blubb blubbs.',
            )
        );
    }

    public function testCollectionDelete()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavDeleteRequest( '/bar' );
        $request->validateHeaders();
        $response = $backend->delete( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavDeleteResponse(
                '/bar'
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/foo',
                ),
                '/foo' => 'bar',
            )
        );
    }

    public function testResourceDeleteError404()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavDeleteRequest( '/unknown' );
        $request->validateHeaders();
        $response = $backend->delete( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_404,
                '/unknown'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testResourceDeleteCausedError()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $backend->options->failingOperations = ezcWebdavMemoryBackendOptions::REQUEST_DELETE;
        $backend->options->failForRegexp = '(foo)';

        $request = new ezcWebdavDeleteRequest( '/foo' );
        $request->validateHeaders();
        $response = $backend->delete( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_423,
                '/foo'
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/foo',
                    '/bar',
                ),
                '/foo' => 'bar',
                '/bar' => array(
                    '/bar/blubb',
                ),
                '/bar/blubb' => 'Somme blubb blubbs.',
            )
        );
    }

    public function testMakeCollectionOnExistingCollection()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMakeCollectionRequest( '/bar' );
        $request->validateHeaders();
        $response = $backend->makeCollection( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_405,
                '/bar'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testMakeCollectionOnExistingRessource()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMakeCollectionRequest( '/foo' );
        $request->validateHeaders();
        $response = $backend->makeCollection( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_405,
                '/foo'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testMakeCollectionMissingParent()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMakeCollectionRequest( '/dum/di' );
        $request->validateHeaders();
        $response = $backend->makeCollection( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_409,
                '/dum/di'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testMakeCollectionInRessource()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMakeCollectionRequest( '/foo/bar' );
        $request->validateHeaders();
        $response = $backend->makeCollection( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_403,
                '/foo/bar'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testMakeCollectionWithRequestBody()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMakeCollectionRequest( '/bar/foo', 'with request body' );
        $request->validateHeaders();
        $response = $backend->makeCollection( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_415,
                '/bar/foo'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testMakeCollection()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavMakeCollectionRequest( '/bar/foo' );
        $request->validateHeaders();
        $response = $backend->makeCollection( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavMakeCollectionResponse(
                '/bar/foo'
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/foo',
                    '/bar',
                ),
                '/foo' => 'bar',
                '/bar' => array(
                    '/bar/blubb',
                    '/bar/foo',
                ),
                '/bar/blubb' => 'Somme blubb blubbs.',
                '/bar/foo' => array(),
            )
        );
    }

    public function testPutOnExistingCollection()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavPutRequest( '/bar', 'some content' );
        $request->validateHeaders();
        $response = $backend->put( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_409,
                '/bar'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testPutMissingParent()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavPutRequest( '/dum/di', 'some content' );
        $request->validateHeaders();
        $response = $backend->put( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_409,
                '/dum/di'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testPutInRessource()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavPutRequest( '/foo/bar', 'some content' );
        $request->validateHeaders();
        $response = $backend->put( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavErrorResponse(
                ezcWebdavResponse::STATUS_409,
                '/foo/bar'
            ),
            'Expected response does not match real response.'
        );
    }

    public function testPut()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavPutRequest( '/bar/foo', 'some content' );
        $request->validateHeaders();
        $response = $backend->put( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavPutResponse(
                '/bar/foo'
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/foo',
                    '/bar',
                ),
                '/foo' => 'bar',
                '/bar' => array(
                    '/bar/blubb',
                    '/bar/foo',
                ),
                '/bar/blubb' => 'Somme blubb blubbs.',
                '/bar/foo' => 'some content',
            )
        );
    }

    public function testPutOnExistingRessource()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $request = new ezcWebdavPutRequest( '/foo', 'some content' );
        $request->validateHeaders();
        $response = $backend->put( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavPutResponse(
                '/foo'
            ),
            'Expected response does not match real response.'
        );

        $content = $this->readAttribute( $backend, 'content' );
        $this->assertEquals(
            $content,
            array(
                '/' => array(
                    '/foo',
                    '/bar',
                ),
                '/foo' => 'some content',
                '/bar' => array(
                    '/bar/blubb',
                ),
                '/bar/blubb' => 'Somme blubb blubbs.',
            )
        );
    }

    public function testPropFind()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->options->fakeLiveProperties = true;
        $backend->addContents( array(
            'foo' => 'bar',
            'bar' => array(
                'blubb' => 'Somme blubb blubbs.',
            )
        ) );

        $requestedProperties = new ezcWebdavPropertyStorage();
        $requestedProperties->attach(
            new ezcWebdavGetContentLengthProperty()
        );
        $requestedProperties->attach(
            new ezcWebdavGetLastModifiedProperty()
        );
        $requestedProperties->attach(
            new ezcWebdavDeadProperty( 'http://apache.org/dav/props/', 'executable' )
        );
/*
        $request = new ezcWebdavPropFindRequest( '/foo' );
        $request->prop = $requestedProperties;
        $request->validateHeaders();
        $response = $backend->propfind( $request );

        $this->assertEquals(
            $response,
            new ezcWebdavPropFindResponse(
                '/foo'
            ),
            'Expected response does not match real response.'
        );
*/
    }
}
?>
