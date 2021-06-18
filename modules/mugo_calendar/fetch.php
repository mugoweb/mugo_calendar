<?php

$parentNodeId = (int) $_REQUEST[ 'parent_node_id' ] ?: 1;
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
        $fcEvents[] = toFullCalendarEvent( $event );
    }
}

header( 'Content-Type: application/json' );
echo json_encode( $fcEvents );

eZExecution::cleanExit();

function toFullCalendarEvent( MugoCalendarEvent $event )
{
	$eventDefinition = $event->getMugoCalendarEventDefinition();

	$return = array(
		'id' => $event->getId(),
		'title' => $eventDefinition->getContentObject()->attribute( 'name' ),
		'start' => $event->getStart()->format( 'c' ),
		'end' => $event->getEnd()->format( 'c' ),
		'allDay' => $event->isFullDayEvent(),
		'url' => getUrl( $eventDefinition ),
	);

	return $return;
}

function getUrl( MugoCalendarEventDefinition $eventDefinition )
{
	$url = '';
	if( $eventDefinition->node )
	{
		$url = $eventDefinition->node->attribute( 'url_alias' );
	}
	elseif( $eventDefinition->getContentObject() )
	{
		$url = $eventDefinition->getContentObject()->attribute( 'main_node' )->attribute( 'url_alias' );
	}
	if( $url )
	{
		eZURI::transformURI( $url );
	}

	return $url;
}