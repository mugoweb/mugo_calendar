<?php

class MugoCalendarEventException extends MugoCalendarEvent
{
	public function __construct(
		MugoCalendarEventDefinition $eventDefinition,
		DateTime $occurrenceDay = null )
	{
		$this->mugoCalendarEventDefinition = $eventDefinition;
		if( $eventDefinition->getStartDateTime() )
		{
			$this->start = clone $eventDefinition->getStartDateTime();
		}
		if( $eventDefinition->getEndDateTime() )
		{
			$this->end = clone $eventDefinition->getEndDateTime();
		}

		$this->id = $eventDefinition->instance;
	}

	/**
	 * @return bool
	 */
	public function isSkipException() : bool
	{
		return !isset( $this->start );
	}
}