<?php
/**
 * File containing the basic standard compliant transport mecanism.
 *
 * @package Webdav
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * The transport handler parses the request and echos the response depending on
 * the client it has been written for.
 *
 * This basic implementation handles requests and responses as defined in RFC
 * 2518 and should be extended for misbehaving clients.
 *
 * @version //autogentag//
 * @package Webdav
 */
class ezcWebdavTransport
{

    /**
     * Regedx to parse the <getcontenttype /> XML elemens content.
     * Example: 'text/html; charset=UTF-8'
     */
    const GETCONTENTTYPE_REGEX = '(^(?P<mime>\w+/\w+)\s*;\s*charset\s*=\s*(?P<charset>.+)\s*$)i';

    /**
     * Parses the webserver environment variables to create a proper request
     * object from then containing all relevant information to handle the
     * request by the backend.
     *
     * @return ezcWebdavRequest
     */
    public function parseRequest( $uri )
    {
        $body = $this->retreiveBody();
        switch ( $_SERVER['REQUEST_METHOD'] )
        {
            case 'PROPFIND':
                return $this->parsePropFindRequest( $uri, $body );
            default:
                throw new ezcWebdavInvalidRequestMethodException(
                    $_SERVER['REQUEST_METHOD']
                );
        }
    }

    /**
     * Returns the body content of the request.
     * This method mainly exists for unittesting purpose. It reads the request
     * body and returns the contents.
     * 
     * @return string void
     */
    protected function retreiveBody()
    {
        $body = '';
        $in   = fopen( 'php://input', 'r' );

        while ( $data = fread( $in, 1024 ) )
        {
            $body .= $data;
        }
        return $body;
    }

    // PROPFIND

    /**
     * Parses the PROPFIN request and returns a request object.
     * This method is responsible for parsing the PROPFIND request. It
     * retrieves the current request URI in $uri and the request body as $body.
     * The return value, if no exception is thrown, is a valid {@link
     * ezcWebdavPropFindRequest} object.
     * 
     * @param string $uri 
     * @param string $body 
     * @return ezcWebdavPropFindRequest
     */
    protected function parsePropFindRequest( $uri, $body )
    {
        $request = new ezcWebdavPropFindRequest( $uri );

        $dom = new DOMDocument();
        if ( $dom->loadXML( $body, LIBXML_NOWARNING ) === false )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPFIND',
                "Could not open XML as DOMDocument: '{$body}'."
            );
        }

        if ( $dom->documentElement->tagName !== 'propfind' )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPFIND',
                "Expected XML element <propfind />, received <{$dom->documentElement->tagName} />."
            );
        }
        if ( $dom->documentElement->firstChild === null )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPFIND',
                "Element <propfind /> does not have a child element."
            );
        }

        // $_GLOBAL['log'] .= var_export( $dom->documentElement->firstChild->tagName )

        switch ( $dom->documentElement->firstChild->tagName )
        {
            case 'allprop':
                $request->allProp = true;
                break;
            case 'propname':
                $request->propName = true;
                break;
            case 'prop':
                $request->prop = $this->extractProperties(
                    $dom->documentElement->firstChild->childNodes
                );
                break;
            default:
                throw new ezcWebdavInvalidRequestBodyException(
                    'PROPFIND',
                    "XML element <{$dom->documentElement->firstChild->tagName} /> is not a valid child element for <propfind />."
                );
        }
        return $request;
    }

    /**
     * Returns extracted properties in an ezcWebdavPropertyStorage.
     * This method receives a DOMNodeList $domNodes, which must contain a set
     * of DOMElement objects, while each of those represents a WebDAV property.
     * The list may contain live properties as well as dead ones. Live
     * properties as defined in RFC 2518 are currently recognized. All other
     * properties in the DAV: namespace are silently ignored. Dead properties
     * are parsed.
     * 
     * @param DOMNodeList $domNodes 
     * @return ezcWebdavPropertyStorage
     */
    protected function extractProperties( DOMNodeList $domNodes )
    {
        $storage = new ezcWebdavPropertyStorage();

        for ( $i = 0; $i < $domNodes->length; ++$i )
        {
            $currentNode = $domNodes->item( $i );
            if ( $currentNode->nodeType !== XML_ELEMENT_NODE )
            {
                // Skip
                continue;
            }
            
            // DAV: namespace indicates live property!
            // Other RFCs allready intruded into this namespace, as 3253 does.
            if ( $currentNode->namespaceURI === 'DAV:' )
            {
                $property = $this->extractLiveProperty( $currentNode );
                // In case we don't know the property, we currently ignore it!
                if ( $property !== null )
                {
                    $storage->attach( $property );
                }
            }
            
            // Other namespaces are always dead properties
            else
            {
                // Create standalone XML for property
                // @TODO How do we need to take care about different namespaces here?
                // It may possibly occur, that shortcut clashes occur...
                $propDom = new DOMDocument();
                $copiedNode = $propDom->importNode( $currentNode, true );
                $propDom->appendChild( $copiedNode );
                $storage->attach(
                    new ezcWebdavDeadProperty(
                    // DEBUG!!!
                        'foo' . $currentNode->namespaceURI . 'bar',
                        $currentNode->nodeType . '---' . $currentNode->tagName,
                        $propDom->saveXML()
                    )
                );
            }
        }
        return $storage;
    }

    /**
     * Extracts a live property from an DOMElement.
     * This method is responsible for parsing WebDAV live properties. The
     * DOMElement $domElement must be an XML element in the DAV: namepsace. If
     * the received property is not defined in RFC 2518, null is returned.
     * 
     * @param DOMElement $domElement 
     * @return ezcWebdavLiveProperty|null
     */
    protected function extractLiveProperty( DOMElement $domElement )
    {
        switch ( $domElement->tagName )
        {
            case 'creationdate':
                $property = new ezcWebdavCreationDateProperty();
                if ( empty( $domElement->nodeValue ) === false )
                {
                    $property->date = new DateTime( $domElement->nodeValue );
                }
                break;
            case 'displayname':
                $property = new ezcWebdavDisplayNameProperty();
                if ( empty( $domElement->nodeValue ) === false )
                {
                    $property->displayName = $domElement->nodeValue;
                }
                break;
            case 'getcontentlanguage':
                $property = new ezcWebdavGetContentLanguageProperty();
                if ( empty( $domElement->nodeValue ) === false )
                {
                    // e.g. 'de, en'
                    $property->displayName = array_map( 'trim', explode( ',', $domElement->nodeValue ) );
                }
                break;
            case 'getcontentlength':
                $property = new ezcWebdavGetContentLengthProperty();
                if ( empty( $domElement->nodeValue ) === false )
                {
                    $property->length = trim( $domElement->nodeValue );
                }
                break;
            case 'getcontenttype':
                $property = new ezcWebdavGetContentTypeProperty();
                // @TODO: Should this throw an exception, if the match fails?
                // Currently, the property stays empty and the backend needs to handle this
                if ( empty( $domElement->nodeValue ) === false 
                  && preg_match( self::GETCONTENTTYPE_REGEX, $domElement->nodeValue, $matches ) > 0 )
                {
                    $property->mime    = $matches['mime'];
                    $property->charset = $matches['charset'];
                }
                break;
            case 'getetag':
                $property = new ezcWebdavGetEtagProperty();
                if ( empty( $domElement->nodeValue ) === false )
                {
                    $property->etag = $domElement->nodeValue;
                }
                break;
            case 'getlastmodified':
                $property = new ezcWebdavGetLastModifiedProperty();
                if ( empty( $domElement->nodeValue ) === false )
                {
                    $property->date = new DateTime( $domElement->nodeValue );
                }
                break;
            case 'lockdiscovery':
                $property = new ezcWebdavLockDiscoveryProperty();
                if ( $domElement->hasChildNodes() === true )
                {
                    $property->activeLock = $this->extractActiveLockContent( $domElement );
                }
                break;
            case 'resourcetype':
                $property = new ezcWebdavResourceTypeProperty();
                if ( empty( $domElement->nodeValue ) === false )
                {
                    $property->type = $domElement->nodeValue;
                }
                break;
            case 'source':
                $property = new ezcWebdavSourceProperty();
                if ( $domElement->hasChildNodes() === true )
                {
                    $property->links = $this->extractLinkContent( $domElemente );
                }
                break;
            case 'supportedlock':
                $property = new ezcWebdavSupportedLockProperty();
                if ( $domElement->hasChildNodes() === true )
                {
                    $property->links = $this->extractLockEntryContent( $domElemente );
                }
                break;
            default:
                // @TODO Implement extension plugins
                // Currently just ignore
                $property = null;
        }
        return $property;
    }

    /**
     * Extracts the <activelock /> XML elements.
     * This method extracts the <activelock /> XML elements from the
     * <lockdiscovery /> element and returns the corresponding
     * ezcWebdavLockDiscoveryPropertyActiveLock object to be used as the
     * content of ezcWebdavLockDiscoveryProperty.
     * 
     * @param DOMElement $domElement 
     * @return ezcWebdavLockDiscoveryPropertyActiveLock
     */
    protected function extractActiveLockContent( DOMElement $domElement )
    {
        // @TODO Implement
        return null;
    }

    /**
     * Extracts the <link /> XML elements.
     * This method extracts the <link /> XML elements from the <source />
     * element and returns the corresponding ezcWebdavSourcePropertyLink object
     * to be used as the content of ezcWebdavSourceProperty.
     * 
     * @param DOMElement $domElement 
     * @return ezcWebdavSourcePropertyLink
     */
    protected function extractLinkContent( DOMElement $domElement )
    {
        // @TODO Implement
        return null;
    }
    
    /**
     * Extracts the <lockentry /> XML elements.
     * This method extracts the <lockentry /> XML elements from the <supportedlock />
     * element and returns the corresponding
     * ezcWebdavSupportedLockPropertyLockentry object to be used as the content
     * of ezcWebdavSupportedLockProperty.
     * 
     * @param DOMElement $domElement 
     * @return ezcWebdavSupportedLockProperty
     */
    protected function extractLockEntryContent( DOMElement $domElement )
    {
        // @TODO Implement
        return null;
    }

    /**
     * Handle a response from the backend and output it depending on the
     * current transport mechanism.
     * 
     * @param ezcWebdavResponse $response
     * @return void
     */
    public function handleResponse( ezcWebdavResponse $response )
    {
        // @TODO: Implement
    }
}

?>
