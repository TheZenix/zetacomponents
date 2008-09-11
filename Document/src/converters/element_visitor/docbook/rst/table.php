<?php

/**
 * File containing the ezcDocumentDocbookElementVisitorConverter class
 *
 * @package Document
 * @version //autogen//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Visit tables
 *
 * The RST table rendering algorithm tries losely to fit a table in the
 * provided document dimensions. This may not always work for over long words,
 * or if the table cells contain literal blocks which can not be wrapped.
 *
 * For this the algorithm estiamtes the available width per column, equally
 * distributes this available width over all columns (which might be far from
 * optimal), and extends the total table width if some cell content exceeds the
 * column width.
 *
 * The initial table cell estiation happens inside the function
 * estimateColumnWidths() which you might want to extend to fit your needs
 * better.
 * 
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToRstTableHandler extends ezcDocumentDocbookToRstBaseHandler
{
    protected function estimateColumnWidths( ezcDocumentDocbookElementVisitorConverter $converter, DOMElement $table )
    {
        // Get some row from the table
        $row = $table->getElementsByTagName( 'row' )->item( 0 );
        $columns = 0;
        foreach ( $row->childNodes as $child )
        {
            if ( ( $child->nodeType === XML_ELEMENT_NODE ) &&
                 ( $child->tagName === 'entry' ) )
            {
                ++$columns;
            }
        }

        $columnWidth = floor( ( $converter->options->wordWrap - ( 2 * ( $columns - 1 ) ) ) / $columns );
        return array_fill( 0, $columns, $columnWidth );
    }

    protected function getMaxLineLength( array $lines )
    {
        $maxLength = 0;
        foreach ( $lines as $line )
        {
            $maxLength = max( $maxLength, strlen( $line ) );
        }

        return $maxLength;
    }

    /**
     * Handle a node
     *
     * Handle / transform a given node, and return the result of the
     * conversion.
     * 
     * @param ezcDocumentDocbookElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root 
     * @return mixed
     */
    public function handle( ezcDocumentDocbookElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $columns  = $this->estimateColumnWidths( $converter, $node );
        $rows     = $node->getElementsByTagName( 'row' );
        $table    = array();
        $rowLines = array();
        $rowNr    = 0;
        $oldWidth = $converter->options->wordWrap;

        // Create contents from tables cells recursively and calculate their
        // content width, extending the column width, if necessary.
        foreach ( $rows as $row )
        {
            $cellNr           = 0;
            $rowLines[$rowNr] = 1;
            foreach ( $row->childNodes as $cell )
            {
                if ( ( $cell->nodeType === XML_ELEMENT_NODE ) &&
                     ( $cell->tagName === 'entry' ) )
                {
                    ezcDocumentDocbookToRstConverter::$wordWrap = $columns[$cellNr];
                    $table[$rowNr][$cellNr] = $cellContent = explode( "\n", trim( $converter->visitChildren( $cell, '' ) ) );
                    $rowLines[$rowNr] = max( $rowLines[$rowNr], count( $cellContent ) );
                    $columns[$cellNr] = max( $columns[$cellNr], $this->getMaxLineLength( $cellContent ) );
                    ++$cellNr;
                }
            }
            ++$rowNr;
        }
        ezcDocumentDocbookToRstConverter::$wordWrap = $converter->options->wordWrap;

        // Build table row seperator
        $separator = '';
        foreach ( $columns as $width )
        {
            $separator .= str_repeat( '-', $width ) . '  ';
        }
        $separator = rtrim( $separator ) . "\n";

        // Check if table has a header. RST does only support the foirst row as
        // a header row, so we will only check for this, and render all
        // subsequent header lines as plain contents.
        $hasHeader = (bool) $node->getElementsByTagName( 'thead' )->length;

        // Draw table
        $cellCount = count( $columns );
        $root .= ( $hasHeader ? str_replace( '-', '=', $separator ) : $separator );
        foreach ( $table as $rowNr => $row )
        {
            for ( $line = 0; $line < $rowLines[$rowNr]; ++$line )
            {
                for ( $cellNr = 0; $cellNr < $cellCount; ++$cellNr )
                {
                    $last         = ( $cellNr >= ( $cellCount - 1 ) );
                    $width        = $columns[$cellNr] + ( $last ? 0 : 2 );
                    $lineContent  = isset( $table[$rowNr][$cellNr][$line] ) ? $table[$rowNr][$cellNr][$line] : '';
                    $root        .= $last ? $lineContent . "\n" : str_pad( rtrim( $lineContent ), $width, ' ' );
                }
            }

            $root .= ( $hasHeader && ( $rowNr === 0 ) ? str_replace( '-', '=', $separator ) : $separator );
        }

        $root .= "\n";

        return $root;
    }
}

?>