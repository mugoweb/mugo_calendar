<?php

$result = [];

$mugoCalendarPersistentObject = new MugoCalendarPersistentObject();
$mugoCalendarPersistentObject->setAttribute( 'id', 0 );
$mugoCalendarPersistentObject->setAttribute( 'end', $_REQUEST[ 'end' ] );
$mugoCalendarPersistentObject->setAttribute( 'start', $_REQUEST[ 'start' ] );
$mugoCalendarPersistentObject->setAttribute( 'type', MugoCalendarPersistentObject::TYPE_RECURRING );
$mugoCalendarPersistentObject->setAttribute( 'recurrence_end',  $_REQUEST[ 'rangeEnd' ] );
$mugoCalendarPersistentObject->setAttribute( 'reference', json_encode( $_REQUEST ) );

$mugoCalendarEvent = new MugoCalendarEvent( $mugoCalendarPersistentObject );

$events = MugoCalendarFunctions::resolveRecurringEvent(
    [ $mugoCalendarEvent ],
    null,
    null,
    1,
    false
);

$result = array_values( $events );

header( 'Content-Type: application/json' );
echo json_encode( $result );

eZExecution::cleanExit();
