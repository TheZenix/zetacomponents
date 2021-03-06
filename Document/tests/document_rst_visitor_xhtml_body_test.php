<?php
/**
 * ezcDocumentRstParserTests
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
 * @subpackage Tests
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

require_once 'helper/rst_dummy_directives.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentRstXhtmlBodyVisitorTests extends ezcTestCase
{
    protected static $testDocuments = null;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public static function getTestDocuments()
    {
        if ( self::$testDocuments === null )
        {
            // Get a list of all test files from the respektive folder
            $testFiles = glob( dirname( __FILE__ ) . '/files/rst/xhtml_body/s_*.txt' );

            // Create array with the test file and the expected result file
            foreach ( $testFiles as $file )
            {
                self::$testDocuments[] = array(
                    $file,
                    substr( $file, 0, -3 ) . 'html'
                );
            }
        }

        return self::$testDocuments;
        return array_slice( self::$testDocuments, 3, 1 );
    }

    /**
     * @dataProvider getTestDocuments
     */
    public function testParseRstFile( $from, $to )
    {
        if ( !is_file( $to ) )
        {
            $this->markTestSkipped( "Comparision file '$to' not yet defined." );
        }

        $document = new ezcDocumentRst();
        $document->options->errorReporting = E_PARSE | E_ERROR | E_WARNING;
        $document->options->xhtmlVisitor   = 'ezcDocumentRstXhtmlBodyVisitor';

        $document->registerDirective( 'my_custom_directive', 'ezcDocumentTestDummyXhtmlDirective' );
        $document->registerDirective( 'user', 'ezcDocumentTestDummyXhtmlDirective' );
        $document->registerDirective( 'book', 'ezcDocumentTestDummyXhtmlDirective' );
        $document->registerDirective( 'function', 'ezcDocumentTestDummyXhtmlDirective' );
        $document->registerDirective( 'replace', 'ezcDocumentTestDummyXhtmlDirective' );

        $document->loadFile( $from );

        $html = $document->getAsXhtml();
        $xml = $html->save();

        // Store test file, to have something to compare on failure
        $tempDir = $this->createTempDir( 'html_body_' ) . '/';
        file_put_contents( $tempDir . basename( $to ), $xml );

        $this->assertEquals(
            file_get_contents( $to ),
            $xml,
            'Document not visited as expected.'
        );

        // Remove tempdir, when nothing failed.
        $this->removeTempDir();
    }

    public function testDocumentHeaderLevel()
    {
        $from = dirname( __FILE__ ) . '/files/rst/xhtml_body/s_002_titles.txt';
        $to   = dirname( __FILE__ ) . '/files/rst/xhtml_body/s_002_titles_header_level.html';

        $document = new ezcDocumentRst();
        $document->options->errorReporting = E_PARSE | E_ERROR | E_WARNING;
        $document->options->xhtmlVisitor   = 'ezcDocumentRstXhtmlBodyVisitor';
        $document->options->xhtmlVisitorOptions->headerLevel = 3;

        $document->registerDirective( 'my_custom_directive', 'ezcDocumentTestDummyXhtmlDirective' );
        $document->registerDirective( 'user', 'ezcDocumentTestDummyXhtmlDirective' );
        $document->registerDirective( 'book', 'ezcDocumentTestDummyXhtmlDirective' );
        $document->registerDirective( 'function', 'ezcDocumentTestDummyXhtmlDirective' );
        $document->registerDirective( 'replace', 'ezcDocumentTestDummyXhtmlDirective' );

        $document->loadFile( $from );

        $html = $document->getAsXhtml();
        $html->options->xmlHeader = true;
        $xml = $html->save();

        // Store test file, to have something to compare on failure
        $tempDir = $this->createTempDir( 'html_' ) . '/';
        file_put_contents( $tempDir . basename( $to ), $xml );

        $this->assertEquals(
            file_get_contents( $to ),
            $xml,
            'Document not visited as expected.'
        );

        // Remove tempdir, when nothing failed.
        $this->removeTempDir();
    }
}

?>
