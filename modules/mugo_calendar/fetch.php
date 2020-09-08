<?php

$parentNodeId = (int) $_REQUEST[ 'parent_node_id' ] ? (int) $_REQUEST[ 'parent_node_id' ] : 1;
$startDate = $_REQUEST[ 'start' ];
$endDate = $_REQUEST[ 'end' ];

$result = eZFunctionHandler::execute( 'mugo_calendar', 'events', array(
	'start' => $startDate,
	'end' => $endDate,
	'parent_node_id' => $parentNodeId,
));

$fcEvents = array();

if( !empty( $result ) )
{
	foreach( $result as $event )
	{
		$fcEvents[] = $event->toFullCalendarEvent();
	}
}

header( 'Content-Type: application/json' );
echo json_encode( $fcEvents );

eZExecution::cleanExit();