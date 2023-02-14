<?php

class MugoCalendarFunctions
{
    /**
     * Fetches events (MugoCalendarEvent). There are 2 different:
     * 1) Given Range with start and end
     * 2) Given start with limit
     *
     * @param DateTime $start
     * @param DateTime|null $end
     * @param int $parentNodeId
     * @param bool $subtree
     * @param array|null $filters
     * @param int|null $limit
     *
     * @return MugoCalendarEventDefinition[]
     */
    public static function fetchEvents(
        DateTime $start,
        DateTime $end = null,
        int $parentNodeId = 1,
        bool $subtree = true,
        array $filters = null,
        int $limit = null
    )
    {
    	// Currently hardcoded to 'true'
        $chronologicalSort = true;

        if( $start !== null && $end !== null )
        {
            // force beginning of day
            $startDay = $start->modify( 'Today 00:00' );
            $endDay = $end->modify( 'Tomorrow 00:00' );

            $return = self::fetchEventsByRange(
                $startDay,
                $endDay,
                $parentNodeId,
                $subtree,
                $filters
            );
        }
        elseif( $start !== null && $limit > 0 )
        {
            // force beginning of day
            $startDay = $start->modify( 'midnight' );

            $return = self::fetchEventsByLimit(
                $startDay,
                $parentNodeId,
                $subtree,
                $filters,
                $limit
            );
        }
        else
		{
			// unsupported parameter combination
		}

        if( $chronologicalSort )
        {
            uksort($return, function($keyA, $keyB)
            {
                list($dateA, $objectIdA) = explode('-', $keyA);
                list($dateB, $objectIdB) = explode('-', $keyB);

                return (int) $dateA - (int) $dateB;
            });
        }

        return $return;
    }

    /**
     * @param MugoCalendarEventDefinition[] $eventDefinitions
     * @param DateTime $startDay
     * @param DateTime $endDay
     * @param int|null $limit
     * @param $withExceptions
     * @param int|null $fetchLimit allowing "overfetching"
     * @return MugoCalendarEventDefinition[]
     */
    public static function resolveRecurringEvent(
		$eventDefinitions,
		DateTime $startDay = null,
		DateTime $endDay = null,
		int $limit = null,
		bool $withExceptions = true,
		int $fetchLimit = null
    )
    {
        $fetchLimit = $fetchLimit ?: $limit;

        // Force start of the day
        $rangeStart = null;
        $rangeEnd = null;
        if( $startDay )
        {
        	// Not sure why a clone is needed
            $rangeStart = clone $startDay;
            $rangeStart->modify( 'Today midnight' );
        }
        if( $endDay )
        {
            $rangeEnd = clone $endDay;
            $rangeEnd->modify( 'Today midnight' );
        }

        $events = self::resolveAllRecurringEvents(
            $eventDefinitions,
            $rangeStart,
            $rangeEnd,
            $fetchLimit
        );

        if( $withExceptions )
        {
            $beforeCount = count( $events );
            $events = self::excludeExceptions( $events );

            if(
                $limit !== null &&
                $beforeCount == $fetchLimit && // there is potentially more occurrences
                count( $events ) < $limit // lost due to the skip exceptions
            )
            {
                return self::resolveRecurringEvent(
                    $eventDefinitions,
                    $startDay,
                    $endDay,
                    $limit,
                    true,
                    ($fetchLimit * 2)
                );
            }
        }

        // after "overfetching" due to exceptions we want to force the limit
        if( $limit !== null && count( $events ) > $limit )
        {
            $events = array_splice( $events, 0, $limit );
        }

        return $events;
    }

    /**
     * Normalize the PHP behaviour if creating new DateTime objects:
     * a provided timestamp like @1490101970 or 1490101970 will also
     * get the current timezone and not UTC
     *
     * @param string $string
     * @return DateTime|null
     */
    public static function strToDateTime( $string )
    {
        $return = null;

        if( $string !== null )
        {
            $string = trim( $string );

            //Detect pure timestamps
            if( strlen( (int)$string ) == strlen( $string ) )
            {
                $string = '@' . $string;
            }

            try
            {
                $return = new DateTime( $string );

                if( substr( $string, 0, 1 ) == '@' )
                {
                    $return->setTimezone( new DateTimeZone( date_default_timezone_get() ) );
                }
            }
            catch( Exception $e )
            {
                // report
                $return = new DateTime();
            }
        }

        return $return;
    }

    /**
     * @param eZContentObject $context
     * @return bool
     */
    public static function isRecurrenceException( eZContentObject $context )
    {
        $settings = eZINI::instance( 'mugo_calendar.ini' );

        $exceptionClasses = $settings->variable( 'Calendar', 'EventExceptionClasses' );

        if( !empty( $exceptionClasses ) )
        {
            return in_array(
                $context->attribute( 'class_identifier' ),
                $exceptionClasses
            );
        }
        else
        {
            $parentNode = self::getNode( $context );
            return $parentNode->attribute( 'class_identifier' ) == $context->attribute( 'class_identifier' );
        }
    }

    protected static function getEventNodesFetchParameters(
        $start,
        $end,
        $parentNodeId,
        $filters
    )
    {
        $fetchParameters = array(
            'parent_node_id' => $parentNodeId,
            'extended_attribute_filter' => array(
                'id' => 'MugoCalendarAttributeRange',
                'params' => array(
                    'start' => $start->getTimestamp(),
                    'end'   => $end->getTimestamp(),
                )
            )
        );

        if( !empty( $filters ) )
        {
            if( !empty( $filters[ 'extended_attribute_filter' ] ) )
            {
                if( class_exists( 'ExtendedAttributeMultiFilter' ) )
                {
                    $fetchParameters[ 'extended_attribute_filter' ] = array(
                        'id' => 'ExtendedAttributeMultiFilter',
                        'params' => array(
                            $fetchParameters[ 'extended_attribute_filter' ],
                            $filters[ 'extended_attribute_filter' ],
                        )
                    );
                }
                else
                {
                    // Do not support a given extended_attribute_filter
                }

                unset( $filters[ 'extended_attribute_filter' ] );
            }

            $fetchParameters = array_merge_recursive( $fetchParameters, $filters );
        }

        return $fetchParameters;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param $parentNodeId
     * @param $subtree
     * @param $filters
     * @return array
     */
    private static function fetchEventsByRange(
        DateTime $start,
        DateTime $end,
        $parentNodeId,
        $subtree,
        $filters
    )
    {
        $returnValues = [];

        $method = isset( $subtree ) && $subtree == false ? 'list' : 'tree';

        $eventNodes = eZFunctionHandler::execute(
            'content',
            $method,
            self::getEventNodesFetchParameters(
                $start,
                $end,
                $parentNodeId,
                $filters
            )
        );

        if( !empty( $eventNodes ) )
        {
            // get all MugoCalendarEvents
            $events = [];
            foreach( $eventNodes as $recurringEventNode )
            {
                $nodeEvents = self::findAttrByType( $recurringEventNode, 'mugorecurringevent' )
                    ->attribute( 'content' );

                // Add reference to node
                foreach( $nodeEvents as $event )
                {
                    $event->node = $recurringEventNode;
                }

                $events = array_merge( $events, $nodeEvents );
            }

            $returnValues = self::resolveRecurringEvent(
                $events,
                $start,
                $end
            );
        }

        return $returnValues;
    }

    /**
     * @param DateTime $start
     * @param $parentNodeId
     * @param $subtree
     * @param $filters
     * @param $limit
     * @return array
     */
    private static function fetchEventsByLimit(
        $start,
        $parentNodeId,
        $subtree,
        $filters,
        $limit
    )
    {
        $events = array();

        $i = 0;
        $rangeStart = clone $start;
        $rangeStart->modify( "-1 day" );
        $rangeEnd = clone $start;

        while( count( $events ) <= $limit && $i < 1000 )
        {
            $events = array_merge(
                $events,
                self::fetchEventsByRange(
                    $rangeStart->modify( '+'. 1 .' day' ),
                    $rangeEnd->modify( '+'. 1 .' day' ),
                    $parentNodeId,
                    $subtree,
                    $filters
                )
            );

            $i++;
        }

        return array_slice( $events, 0, $limit, true );
    }

    /**
     * @param MugoCalendarEventDefinition[] $eventDefinitions
     * @param DateTime $start
     * @param DateTime $end
     * @param int|null $limit
     * @return MugoCalendarEvent[]
     */
    private static function resolveAllRecurringEvents(
		array $eventDefinitions,
		DateTime $start = null,
		DateTime $end = null,
		int $limit = null
    ) : array
    {
        $return = array();

        if( !empty( $eventDefinitions ) )
        {
            $range = self::getRecurringRange( $start, $end, $eventDefinitions );

            /* @var $loopDay DateTime */
            $loopDay = clone $range[ 'start' ];
            $iteration = 1;
            while( $loopDay <= $range[ 'end' ] )
            {
                foreach( $eventDefinitions as $eventDefinition )
                {
                    if( $eventDefinition->occursOnDate( $loopDay ) )
                    {
                    	$newEvent = new MugoCalendarEvent(
							$eventDefinition,
							$loopDay
						);

                    	// array format used in order to filter out exceptions
                        $return[ $newEvent->getId() ] = $newEvent;

                        if( $limit && count( $return ) >= $limit )
                        {
                            break 2;
                        }
                    }
                }

                $iteration++;
                $loopDay->modify( '+1 day' );
            }
        }

        return $return;
    }

    /**
	 * Optimize the range, fetch will be more efficient
	 *
     * @param DateTime $start
     * @param DateTime $end
     * @param MugoCalendarEventDefinition[] $events
     * @return array
     */
    private static function getRecurringRange(
        $start = null,
        $end = null,
        $events = []
    )
    {
        // I hope nobody is running that code in the year of 4082
        $return = array(
            'start' => is_null( $start ) ? self::strToDateTime( '@-66666666666' )->modify( 'midnight' ) : $start,
            'end' => is_null( $end ) ? self::strToDateTime( '@66666666666' )->modify( 'midnight' ) : $end,
        );

        if( !empty( $events ) )
        {
            // Do the extra effort of limiting the range
            if( ( (int) $return[ 'start' ]->diff( $return[ 'end' ] )->format( '%a' ) ) > 100 )
            {
                $minEventStart = $return[ 'end' ];
                $maxEventEnd = $return[ 'start' ];

                foreach( $events as $event )
                {
                    if( $event->type == MugoCalendarPersistentObject::TYPE_RECURRING )
                    {
                        $eventStart = clone $event->recurrence->getRangeStart();

                        if( $event->recurrence->getRangeEnd() )
                        {
                            $eventEnd = clone $event->recurrence->getRangeEnd();
                        }
                        else
                        {
                            $eventEnd = $return[ 'end' ];
                        }
                    }
                    else
                    {
                        $eventStart = clone $event->getStartDateTime();
                        $eventEnd = clone $event->getEndDateTime();
                    }

                    if( $eventStart < $minEventStart )
                    {
                        $minEventStart = $eventStart;
                    }
                    if( $eventEnd > $maxEventEnd )
                    {
                        $maxEventEnd = $eventEnd;
                    }
                }

                $minEventStart = $minEventStart->modify( 'midnight' );
                $maxEventEnd = $maxEventEnd->modify( 'midnight' );

                $return =
                    [
                        'start' => max( $minEventStart, $return[ 'start' ] ),
                        'end' => min( $maxEventEnd, $return[ 'end' ] ),
                    ];
            }
        }

        return $return;
    }

    /**
     * Removes/Replaces events from the given $events array. "skip" exceptions
     * remove an entry in the array. Other exceptions override an entry in the
     * array
     *
     * @param MugoCalendarEvent[] $events Events for a given time range for the given $recurringEvent
     * @return MugoCalendarEvent[]
     */
    private static function excludeExceptions( array $events ) : array
    {
        $ids = array_keys( $events );

        if( $ids )
        {
            $exceptionNodes = eZFunctionHandler::execute( 'content', 'tree', array(
                // parent_node_id limit is a required parameter in ezp
                'parent_node_id' => 1,
                'extended_attribute_filter' => array(
                    'id' => 'MugoCalendarAttributeByIds',
                    'params' => array(
                        'ids' => $ids,
                    )
                )
            ));

            if( !empty( $exceptionNodes ) )
            {
                foreach( $exceptionNodes as $exceptionNode )
                {
                    $exceptionAttribute = self::findAttrByType( $exceptionNode, 'mugorecurringevent' );

                    if( $exceptionAttribute->attribute( 'has_content' ) )
                    {
                        $exceptions = $exceptionAttribute->attribute( 'content' );

                        // exceptions always have a single event
                        /** @var MugoCalendarEventDefinition $exceptionDefinition */
                        $exceptionDefinition = $exceptions[0];
                        $exceptionDefinition->node = $exceptionNode;

						//TODO: this will break for skip exceptions
						$exception = new MugoCalendarEvent( $exceptionDefinition );

                        if( $exception->getStart() )
                        {
                            // Replace entry with exception
							if( isset( $events[ $exception->getId() ] ) )
							{
								$events[ $exception->getId() ] = $exception;
							}
							else
							{
								// Exception is out of sync - report it
							}
                        }
                        else
                        {
                            // filter out 'skip' exceptions
                            unset( $events[ $exception->getId() ] );
                        }
                    }
                }
            }
        }

        return $events;
    }

    /**
     * @param $eZObj
     * @param $type
     * @return bool
     */
    private static function findAttrByType( $eZObj, $type )
    {
        $dataMap = $eZObj->attribute( 'data_map' );

        foreach( $dataMap as $eZAttr )
        {
            if( $eZAttr->attribute( 'data_type_string' ) == $type )
            {
                return $eZAttr;
            }
        }

        return false;
    }

    /**
     * @param eZContentObject $eZObj
     * @return eZContentObjectTreeNode
     */
    private static function getNode( $eZObj )
    {
        $return = $eZObj->attribute('current')->attribute('parent_nodes')[0];

        if( !$return )
        {
            $return = eZContentObjectTreeNode::fetch(
                $eZObj->attribute( 'current' )->attribute( 'main_parent_node_id' )
            );
        }

        return $return;
    }
}
