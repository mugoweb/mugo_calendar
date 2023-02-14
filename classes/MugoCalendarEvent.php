<?php

class MugoCalendarEvent
{
	/** @var DateTime */
	protected $start;

	/** @var DateTime */
	protected $end;

	/** @var string */
	protected $id;

	/** @var MugoCalendarEventDefinition */
	protected $mugoCalendarEventDefinition;

	/**
	 * The variable $occurrenceDay only needed for recurring events
	 *
	 * @param MugoCalendarEventDefinition $eventDefinition
	 * @param DateTime|null $occurrenceDay
	 */
	public function __construct(
		MugoCalendarEventDefinition $eventDefinition,
		DateTime $occurrenceDay = null
	)
	{
		$this->mugoCalendarEventDefinition = $eventDefinition;

		$this->start = clone $eventDefinition->getStartDateTime();
		$this->end = clone $eventDefinition->getEndDateTime();

		if( $eventDefinition->getType() == MugoCalendarPersistentObject::TYPE_RECURRING )
		{
			/*
			 * using start date (full day time) to calculate
			 * start/end time for recurrence instance
			 */
			$startDay = clone $eventDefinition->getStartDateTime();
			$startDay->modify( 'midnight' );

			// diff: the difference of eventStartDay and loopDay
			$diff =
				(int) $occurrenceDay->diff( $startDay )->format( '%a' );

			$this->start->modify( '+'. $diff .' days');
			$this->end->modify( '+'. $diff .' days' );
		}

		$this->id =
			$eventDefinition->getId() .
			'-'.
			$this->getStart()->getTimestamp();
	}

	/**
	 * @return DateTime
	 */
	public function getStart(): DateTime
	{
		return $this->start;
	}

	/**
	 * @param DateTime $start
	 */
	public function setStart( DateTime $start ): void
	{
		$this->start = $start;
	}

	/**
	 * @return DateTime
	 */
	public function getEnd(): DateTime
	{
		return $this->end;
	}

	/**
	 * @param DateTime $end
	 */
	public function setEnd( DateTime $end ): void
	{
		$this->end = $end;
	}

	/**
	 * @return mixed
	 */
	public function getMugoCalendarEventDefinition()
	{
		return $this->mugoCalendarEventDefinition;
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId( string $id ): void
	{
		$this->id = $id;
	}

	/**
	 * @param mixed $mugoCalendarEventDefinition
	 */
	public function setMugoCalendarEventDefinition( $mugoCalendarEventDefinition ): void
	{
		$this->mugoCalendarEventDefinition = $mugoCalendarEventDefinition;
	}

	public function isFullDayEvent()
	{
		$return = null;

		if( $this->start !== null && $this->end !== null )
		{
			//allDay event: start/end hours/minutes are 0
			$return = ( !intval( $this->start->format( 'Gi' ) ) && !intval( $this->end->format( 'Gi' ) ) );
		}

		return $return;
	}

	/*
	 *  Access in templates
	 */

	public function attributes() : array
	{
		return array(
			'start',
			'end',
			'id',
			'object',
//TODO: support more attributes
//			'attribute',
//			'all_day_event',
//			'recurrence',
//			'type',
//			'parent_id',
//			'instance',
//			'data',
		);
	}

	/**
	 * @param $attr
	 * @param bool $noFunction
	 * @return bool|eZContentObject|int
	 */
	public function attribute( $attr, $noFunction = false )
	{
		switch( $attr )
		{
			case 'start':
				{
					return is_object( $this->start ) ? $this->start->getTimestamp() : null;
				}
				break;

			case 'end':
				{
					return is_object( $this->end ) ? $this->end->getTimestamp() : null;
				}
				break;

			case 'object':
				{
					return $this->mugoCalendarEventDefinition->getContentObject();
				}
				break;

//			case 'attribute':
//				{
//					return $this->objectAttribute;
//				}
//				break;

//			case 'all_day_event':
//				{
//					return $this->isAllDay;
//				}
//				break;

//			case 'recurrence':
//				{
//					return $this->recurrence;
//				}
//				break;

//			case 'type':
//				{
//					$map = array(
//						0 => 'undefined',
//						1 => 'single',
//						2 => 'recurring',
//						3 => 'exception',
//					);
//
//					return $map[ $this->type ];
//				}
//				break;

//			case 'instance':
//				{
//					return $this->instance;
//				}
//				break;

			case 'id':
				{
					return $this->id;
				}
				break;

//			case 'data':
//				{
//					return $this->data;
//				}
//				break;
		}
	}

	/**
	 * @param $attr
	 * @return bool
	 */
	public function hasAttribute( $attr )
	{
		return in_array( $attr, $this->attributes() );
	}

	// optional
	public function setAttribute( $attr, $value )
	{
		$this->$attr = $value;
	}

}
