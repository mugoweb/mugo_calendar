<?php

$definition = $_REQUEST[ 'definition' ];

$description = '-';
if( is_array( $definition ) )
{
    /** @var MugoRecurrence $recurrence */
    $recurrence = MugoRecurrence::factory( $definition[ 'type' ] );
    $recurrence->setDefinition( $definition );
    $description = $recurrence->__toString();
}

header( 'Content-Type: application/json' );
echo json_encode( $description );

eZExecution::cleanExit();
