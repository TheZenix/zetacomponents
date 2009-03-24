<?php
/**
 * File containing the ezcDocumentPdfRenderer class
 *
 * @package Document
 * @version //autogen//
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Abstract renderer base class
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentPdfRenderer extends ezcDocumentPdfRenderer
{
    /**
     * Used driver implementation
     * 
     * @var ezcDocumentPdfDriver
     */
    protected $driver;

    /**
     * Construct renderer from driver to use
     * 
     * @param ezcDocumentPdfDriver $driver 
     * @return void
     */
    public function __construct( ezcDocumentPdfDriver $driver )
    {
        $this->driver = $driver;
    }
}
