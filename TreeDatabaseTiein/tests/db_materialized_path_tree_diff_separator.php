<?php
/**
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package TreeDatabaseTiein
 * @subpackage Tests
 */

require_once 'Tree/tests/tree.php';
require_once 'db_tree.php';
require_once 'db_materialized_path_tree.php';

/**
 * @package TreeDatabaseTiein
 * @subpackage Tests
 */
class ezcTreeDbMaterializedPathTestWithDifferentSeparator extends ezcTreeDbMaterializedPathTest
{
    private $tempDir;

    public function insertData()
    {
        // insert test data
        $data = array(
            // child -> parent
            1 => array( 'null', '@1' ),
            2 => array(      1, '@1@2' ),
            3 => array(      1, '@1@3' ),
            4 => array(      1, '@1@4' ),
            6 => array(      4, '@1@4@6' ),
            7 => array(      6, '@1@4@6@7' ),
            8 => array(      6, '@1@4@6@8' ),
            5 => array(      1, '@1@5' ),
            9 => array(      5, '@1@5@9' ),
        );
        foreach( $data as $childId => $details )
        {
            list( $parentId, $path ) = $details;
            $this->dbh->exec( "INSERT INTO materialized_path(id, parent_id, path) VALUES( $childId, $parentId, '$path' )" );
        }

        // add data
        for ( $i = 1; $i <= 8; $i++ )
        {
            $this->dbh->exec( "INSERT INTO data(id, data) values ( $i, 'Node $i' )" );
        }
    }

    protected function setUpEmptyTestTree( $dataTable = 'data', $dataField = 'data', $indexTableSuffix = '' )
    {
        $store = new ezcTreeDbExternalTableDataStore( $this->dbh, $dataTable, 'id', $dataField );
        $tree = ezcTreeDbMaterializedPath::create(
            $this->dbh,
            'materialized_path' . $indexTableSuffix,
            $store,
            '@'
        );
        $this->emptyTables();
        return $tree;
    }

    protected function setUpTestTree( $dataTable = 'data', $dataField = 'data', $indexTableSuffix = '' )
    {
        $store = new ezcTreeDbExternalTableDataStore( $this->dbh, $dataTable, 'id', $dataField );
        $tree = new ezcTreeDbMaterializedPath(
            $this->dbh,
            'materialized_path' . $indexTableSuffix,
            $store,
            '@'
        );
        return $tree;
    }

    public function testWithWrongSeparationChar()
    {
        $store = new ezcTreeDbExternalTableDataStore( $this->dbh, 'data', 'id', 'data' );
        $tree = new ezcTreeDbMaterializedPath(
            $this->dbh,
            'materialized_path',
            $store,
            '$'
        );

        $nodeList = $tree->fetchNodeById( 4 )->fetchSubtree();
        self::assertSame( 1, $nodeList->size );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcTreeDbMaterializedPathTestWithDifferentSeparator" );
    }
}

?>
