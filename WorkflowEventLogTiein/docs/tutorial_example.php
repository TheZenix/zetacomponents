<?php
// Set up the logfile writer.
$writer = new ezcLogUnixFileWriter( '/tmp', 'workflow.log' );

$log = ezcLog::getInstance();
$mapper = $log->getMapper();
$filter = new ezcLogFilter;
$rule = new ezcLogFilterRule( $filter, $writer, true );
$mapper->appendRule( $rule );

// Set up database connection.
$db = ezcDbFactory::create( 'mysql://test@localhost/test' );

// Set up workflow definition storage (database).
$definition = new ezcWorkflowDatabaseDefinitionStorage( $db );

// Load latest version of workflow named "Test".
$workflow = $definition->loadByName( 'Test' );

// Set up database-based workflow executer.
$execution = new ezcWorkflowDatabaseExecution( $db );

// Pass workflow object to workflow executer.
$execution->workflow = $workflow;

// Attach logfile writer as a listener.
$execution->addListener( new ezcWorkflowEventLogListener( $log ) );

// Start workflow execution.
$id = $execution->start();
?>
