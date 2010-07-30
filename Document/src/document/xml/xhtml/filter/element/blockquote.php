<?php
/**
 * File containing the ezcDocumentXhtmlBlockquoteElementFilter class
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Document
 * @version //autogen//
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @access private
 */

/**
 * Filter for XHtml blockquotes and blockquote attributions
 *
 * The sematic meaning of the cite XHtml element is sometimes referenced as
 * blockquote attribution, and sometimes as inline quotes. We decide its
 * meaning depending on the parent node type.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlBlockquoteElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        if ( ( $element->parentNode->tagName === 'blockquote' ) ||
             // This is a special filter for the markup generated by the RST to
             // HTML conversion
             ( ( $element->parentNode->tagName === 'div' ) &&
               ( $element->parentNode->hasAttribute( 'class' ) ) &&
               ( strpos( $element->parentNode->getAttribute( 'class' ), 'attribution' ) !== false ) ) )
        {
            // The attribution is required to be the first element in a
            // blockquote element, so we move this to the front.
            $cloned = $element->parentNode->cloneNode( true );
            $element->parentNode->parentNode->insertBefore( $cloned, $element->parentNode->parentNode->firstChild->nextSibling );

            // Assume this is an attribution.
            $cloned->setProperty( 'type', 'attribution' );
            $element->parentNode->parentNode->removeChild( $element->parentNode );
        }
        elseif ( !$this->isInline( $element ) )
        {
            $element->setProperty( 'type', 'blockquote' );
        }
        else
        {
            $element->setProperty( 'type', 'quote' );
        }
    }

    /**
     * Check if filter handles the current element
     *
     * Returns a boolean value, indicating whether this filter can handle
     * the current element.
     *
     * @param DOMElement $element
     * @return void
     */
    public function handles( DOMElement $element )
    {
        return ( $element->tagName === 'cite' );
    }
}

?>
