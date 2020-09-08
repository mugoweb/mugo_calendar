<?php

class MugoCalendarFunctions
{
	/**
	 * Fetches events (MugoCalendarEvent). There are 2 different:
	 * 1) Given Range with start and end
	 * 2) Given start with limit
	 *
	 * @param DateTime $start
	 * @param DateTime $end
	 * @param int $parentNodeId
	 * @param $subtree
	 * @param $filters
	 * @param int $limit
	 * @return MugoCalendarEvent[]
	 */
	public static function fetchEvents(
		DateTime $start,
		DateTime $end = null,
		$parentNodeId = 1,
		$subtree = true,
		$filters = null,
		$limit = null
	)
	{
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
		else
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
	 * @param MugoCalendarEvent[] $events
	 * @param DateTime $startDay
	 * @param DateTime $endDay
	 * @param null $limit
	 * @param $withExceptions
	 * @return MugoCalendarEvent[]
	 */
	public static function resolveRecurringEvent(
		$events,
		DateTime $startDay = null,
		DateTime $endDay = null,
		$limit = null,
		$withExceptions = true
	)
	{
		// Force start of the day
		$rangeStart = null;
		$rangeEnd = null;
		if( $startDay )
		{
			$rangeStart = clone $startDay;
			$rangeStart->modify( 'Today midnight' );
		}
		if( $endDay )
		{
			$rangeEnd = clone $endDay;
			$rangeEnd->modify( 'Today midnight' );
		}

		$resolvedEvents = self::resolveAllRecurringEvents(
			$events,
			$rangeStart,
			$rangeEnd,
			$limit
		);

		if( $withExceptions )
		{
			$resolvedEvents = self::excludeExceptions( $resolvedEvents );
		}

		// Following if statement probably won't ever get executed
		if( is_null( $resolvedEvents ) )
		{
			$resolvedEvents = array();
			eZDebug::writeWarning( '"NULL" value for $resolvedEvents - fix me.', __CLASS__ );
		}

		return $resolvedEvents;
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

	protected function getEventNodesFetchParameters(
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
	 * @param MugoCalendarEvent[] $events
	 * @param DateTime $start
	 * @param DateTime $end
	 * @param integer $limit
	 * @return MugoCalendarEvent[]
	 */
	private static function resolveAllRecurringEvents(
		$events,
		$start = null,
		$end = null,
		$limit = null
	)
	{
		$return = array();

		if( !empty( $events ) )
		{
			$range = self::getRecurringRange( $start, $end, $events );

			/* @var $loopDay DateTime */
			$loopDay = clone $range[ 'start' ];
			$iteration = 1;
			while( $loopDay <= $range[ 'end' ] )
			{
				foreach( $events as $event )
				{
					if( $event->occursOnDate( $loopDay ) )
					{
						// resolve recurrence
                        // TODO: make the distinction in cloneLoopDayEvent if necessary
						if( $event->type == MugoCalendarPersistentObject::TYPE_RECURRING )
						{
							$newEvent = self::cloneLoopDayEvent( $event, $loopDay );
						}
						else
						{
							$newEvent = clone $event;
							$newEvent->id = self::buildEventId(
								$event->start->getTimestamp(),
								$event->getObjectAttribute()->ID
							);
						}

						$return[ $newEvent->id ] = $newEvent;

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
	 * @param MugoCalendarEvent $event
	 * @param DateTime $loopDay
	 * @return MugoCalendarEvent
	 */
	private static function cloneLoopDayEvent(
		MugoCalendarEvent $event,
		DateTime $loopDay
	)
	{
		$newEvent = clone $event;

		/*
		 * using start date (full day time) to calculate
		 * start/end time for recurrence instance
		 */
		$startDay = clone $event->getStartDateTime();
		$startDay->modify( 'midnight' );

		// diff is the difference of eventStartDay and loopDay
		$diff =
			(int) $loopDay->diff( $startDay )->format( '%a' );

		$newEvent->start->modify( '+'. $diff .' days');
		$newEvent->end->modify( '+'. $diff .' days' );

		// update id - later we match exceptions using that ID
		$newEvent->id = self::buildEventId(
            $newEvent->start->getTimestamp(),
			$event->getObjectAttribute()->ID
		);

		return $newEvent;
	}


	/**
	 * @param DateTime $start
	 * @param DateTime $end
	 * @param MugoCalendarEvent[] $events
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
	 * @param array $events Events for a given time range for the given $recurringEvent
	 * @return MugoCalendarEvent[]
	 */
	private static function excludeExceptions( $events )
	{
		$ids = array_keys( $events );

		if( $ids )
		{
			$exceptionNodes = eZFunctionHandler::execute( 'content', 'tree', array(
				// parent_node_id limit is a required parameter in ezp
				'parent_node_id' => 2,
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
                        /** @var MugoCalendarEvent $exception */
						$exception = $exceptions[0];
						$exception->node = $exceptionNode;

						if( $exception->start )
						{
						    // Replace entry with exception
							$events[ $exception->instance ] = $exception;
						}
						else
						{
							// filter out 'skip' exceptions
							unset( $events[ $exception->instance ] );
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
	 * @param int $startTime
	 * @param int $attributeId
	 * @return string
	 */
	private static function buildEventId( $startTime, $attributeId )
	{
		return $startTime . '-' . $attributeId;
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
