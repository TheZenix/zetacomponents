<?php
/**
 * File containing the ezcWorkflowExecutionVisualizerPlugin class.
 *
 * @package Workflow
 * @version //autogen//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Execution plugin that visualizes the execution.
 *
 * @package Workflow
 * @version //autogen//
 */
class ezcWorkflowExecutionVisualizerPlugin extends ezcWorkflowExecutionPlugin
{
    /**
     * The directory to which the DOT files are written.
     *
     * @var string
     */
    protected $directory;

    /**
     * Filename counter.
     *
     * @var integer
     */
    protected $fileCounter = 0;

    /**
     * Whether or not to include workflow variables.
     *
     * @var integer
     */
    protected $includeVariables = true;

    /**
     * Constructor.
     *
     * @param  string $directory The directory the DOT files are written to
     * @param  bool   $includeVariables Whether or not to include workflow variables
     * @throws ezcBaseFileNotFoundException when the directory does not exist
     * @throws ezcBaseFilePermissionException when the directory is not writable
     */
    public function __construct( $directory, $includeVariables = true )
    {
        if ( !is_dir( $directory ) )
        {
            throw new ezcBaseFileNotFoundException( $directory );
        }

        if ( !is_writable( $directory ) )
        {
            throw new ezcBaseFilePermissionException( $directory, ezcBaseFileException::WRITE );
        }

        $this->directory        = $directory;
        $this->includeVariables = $includeVariables;
    }

    /**
     * Called after a node has been activated.
     *
     * @param ezcWorkflowExecution $execution
     * @param ezcWorkflowNode      $node
     */
    public function afterNodeActivated( ezcWorkflowExecution $execution, ezcWorkflowNode $node )
    {
        $this->visualize( $execution );
    }

    /**
     * Called after a node has been executed.
     *
     * @param ezcWorkflowExecution $execution
     * @param ezcWorkflowNode      $node
     */
    public function afterNodeExecuted( ezcWorkflowExecution $execution, ezcWorkflowNode $node )
    {
        $this->visualize( $execution );
    }

    /**
     * Visualizes the current state of the workflow execution.
     *
     * @param ezcWorkflowExecution $execution
     */
    protected function visualize( ezcWorkflowExecution $execution )
    {
        $activatedNodes = array();

        foreach ( $execution->getActivatedNodes() as $node )
        {
            $activatedNodes[] = $node->getId();
        }

        if ( $this->includeVariables )
        {
            $variables = $execution->getVariables();
        }
        else
        {
            $variables = array();
        }

        $visitor = new ezcWorkflowVisitorVisualization( $activatedNodes, $variables );
        $execution->workflow->accept( $visitor );

        file_put_contents(
          sprintf(
            '%s%s%s_%03d_%03d.dot',

            $this->directory,
            DIRECTORY_SEPARATOR,
            $execution->workflow->name,
            $execution->getId(),
            ++$this->fileCounter
          ),
          $visitor->__toString()
        );
    }
}
?>