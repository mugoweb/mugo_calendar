<?php

class MugoCalendarRecurringEventDefinition extends MugoCalendarEventDefinition
{
	/** @var DateTime */
	public $rangeEnd;

	/** @var DateTime */
	public $rangeStart;

	/** @var string Weekly|Monthly|Yearly */
	public $recurrenceType;

	/** @var int */
	public $interval;

	public $day;

	public $weeklyWeekDay;

	public $monthlyType;

	public $monthlyWeekDay;

	/** @var array */
	protected $settings;

	protected $weekDays = array();

	/**
	 *  @var array Number to Ordinal number
	 */
	private $numberToOrdinalMap = array();

	public function __construct( MugoCalendarPersistentObject $eventPersistentObject )
	{
		parent::__construct( $eventPersistentObject );

		$this->init();

		$recurrenceData = json_decode( $eventPersistentObject->attribute( 'reference' ) );

		$this->recurrenceType = $recurrenceData->type;
		$this->interval = $recurrenceData->interval;
		$this->day = $recurrenceData->day;
		$this->weeklyWeekDay = $recurrenceData->weeklyWeekDay;
		$this->monthlyType = $recurrenceData->monthlyType;
		$this->monthlyWeekDay = $recurrenceData->monthlyWeekDay;

		// TODO: solve via function
		$this->rangeStart = $this->start;

		if( $eventPersistentObject->attribute( 'recurrence_end' ) )
		{
			$this->rangeEnd = MugoCalendarFunctions::strToDateTime(
				$eventPersistentObject->attribute( 'recurrence_end' )
			);
		}
	}

	/**
	 * @param DateTime $testDate
	 * @return bool
	 */
	public function occursOnDate( DateTime $testDate ) : bool
	{
		$match = false;

		// Not using the translated version
		$weekDays = array(
			'Sunday',
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday',
		);

		if( $this->occursInRange( $testDate ) )
		{
			$modifyString = '';

			switch( $this->recurrenceType )
			{
				case 'Weekly':
					{
						$modifyString = $weekDays[ $this->weeklyWeekDay ] . ' this week';
					}
					break;

				case 'Monthly':
					{
						switch( $this->monthlyType )
						{
							case 'day':
								{
									// The idea is that $date day minus entry day needs to be zero to match
									$modifyString = '+' . ( (int) $testDate->format( 'd' ) - $this->day ) . ' day this month';
								}
								break;

							default:
								{
									$modifyString = $this->monthlyType . ' ' . $weekDays[ $this->monthlyWeekDay ] . ' of this Month';
								}
								break;
						}
					}
					break;

				case 'Yearly':
					{
						$modifyString = $this->start->format( 'jS F' );
					}
					break;
			}

			if( $modifyString )
			{
				$matchDate = clone $testDate;
				$matchDate->modify( $modifyString );

				$match = $testDate == $matchDate;

				if(
					$match &&
					isset( $this->interval ) &&
					$this->interval > 1
				)
				{
					switch( $this->recurrenceType )
					{
						case 'Monthly':
							{
								$tStart = $this->getRangeStart();
								$tStart->modify( 'First day of this month' );
								$tThis = clone $testDate;
								$tThis->modify( 'First day of this month' );

								$interval = $tStart->diff( $tThis )->m + ( $tStart->diff( $tThis )->y * 12 );

								$match = $interval % ( $this->interval ) == 0;
							}
							break;

						case 'Weekly':
							{
								$tStart = $this->getFirstDayOfWeekDay( clone $this->getRangeStart() );
								$tThis =  $this->getFirstDayOfWeekDay( clone $testDate );

								$interval = $tStart->diff( $tThis )->format( '%a' );
								$match = $interval % ( $this->interval * 7 ) == 0;
							}
							break;
					}
				}
			}
		}

		return $match;
	}

	/**
	 * @return DateTime|null
	 */
	public function getRangeStart() :? DateTime
	{
		if( $this->start )
		{
			$startDate = clone $this->start;
			$startDate->modify( 'midnight' );

			return $startDate;
		}

		return null;
	}

	/**
	 * @return DateTime|null
	 */
	public function getRangeEnd() :? DateTime
	{
		return $this->rangeEnd;
	}

	/**
	 * @return string
	 */
	public function getRecurrenceType(): string
	{
		return $this->recurrenceType;
	}

	/**
	 * @param string $dateFormat
	 * @return string
	 */
	public function describe( string $dateFormat = '%Y-%m-%d' ) : string
	{
		$return = ezpI18n::tr( 'mugo_calendar_date_description', 'every' ) . ' ';

		if( $this->recurrenceType == 'Weekly' )
		{
			$return .= $this->interval > 1 ?
				$this->interval . ' '. ezpI18n::tr( 'mugo_calendar_date_description', 'weeks' ) .' ' :
				' '. ezpI18n::tr( 'mugo_calendar_date_description', 'week' ) .' ';

			$return .=
				ezpI18n::tr( 'mugo_calendar_date_description', 'on' ) .
				' ' .
				$this->weekDays[ $this->weeklyWeekDay ] .
				' ';
		}
		elseif( $this->recurrenceType == 'Monthly' )
		{
			$return .= $this->interval > 1 ?
				$this->interval . ' '. ezpI18n::tr( 'mugo_calendar_date_description', 'months' ) .' ' :
				' '. ezpI18n::tr( 'mugo_calendar_date_description', 'month' ) .' ';

			if( $this->monthlyType == 'day' )
			{
				$return .=
					ezpI18n::tr( 'mugo_calendar_date_description', 'on the' ) .
					' ' .
					$this->numberToOrdinalMap[ $this->day ] .
					' ' .
					ezpI18n::tr( 'mugo_calendar_date_description', 'day' ) .
					' ';
			}
			else
			{
				$return .=
					ezpI18n::tr( 'mugo_calendar_date_description', 'on the' ) .
					' ' .
					//TODO: do not store ordinal string but an interval in the DB
					$this->numberToOrdinalMap[ $this->ordinalToNumberMap[ $this->monthlyType ] ].
					' ' .
					$this->weekDays[ $this->monthlyWeekDay ].
					' ';
			}
		}

		if( $this->getRangeEnd() )
		{
			$return .=
				ezpI18n::tr( 'mugo_calendar_date_description', 'ends on' ).
				' ' .
				eZLocale::instance()->formatDateType( $dateFormat, $this->getRangeEnd()->getTimestamp() );
		}

		return $return;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->describe();
	}

	public function getType(): ?int
	{
		return MugoCalendarPersistentObject::TYPE_RECURRING;
	}

	/**
	 * @param DateTime $dateTime
	 * @return DateTime
	 */
	protected function getFirstDayOfWeekDay( DateTime $dateTime ) : DateTime
	{
		$localDateTime = clone $dateTime;
		$weekDay = $this->settings[ 'WeekOffset' ] ? 'Monday' : 'Sunday';

		return $localDateTime->modify( $weekDay . ' this week' );
	}

	protected function occursInRange( DateTime $testDate ) : bool
	{
		if( $testDate >= $this->getRangeStart() )
		{
			if( is_null( $this->getRangeEnd() ) || $testDate <= $this->getRangeEnd() )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Making the constructor more readable
	 */
	private function init()
	{
		$this->weekDays = array(
			ezpI18n::tr( 'mugo_calendar', 'Sunday' ),
			ezpI18n::tr( 'mugo_calendar', 'Monday' ),
			ezpI18n::tr( 'mugo_calendar', 'Tuesday' ),
			ezpI18n::tr( 'mugo_calendar', 'Wednesday' ),
			ezpI18n::tr( 'mugo_calendar', 'Thursday' ),
			ezpI18n::tr( 'mugo_calendar', 'Friday' ),
			ezpI18n::tr( 'mugo_calendar', 'Saturday' ),
		);

		$this->numberToOrdinalMap =
			[
				1 => ezpI18n::tr( 'mugo_calendar', 'first' ),
				2 => ezpI18n::tr( 'mugo_calendar', 'second' ),
				3 => ezpI18n::tr( 'mugo_calendar', 'third' ),
				4 => ezpI18n::tr( 'mugo_calendar', 'fourth' ),
				5 => ezpI18n::tr( 'mugo_calendar', 'fifth' ),
				6 => ezpI18n::tr( 'mugo_calendar', 'sixth' ),
				7 => ezpI18n::tr( 'mugo_calendar', 'seventh' ),
				8 => ezpI18n::tr( 'mugo_calendar', 'eighth' ),
				9 => ezpI18n::tr( 'mugo_calendar', 'ninth' ),
				10 => ezpI18n::tr( 'mugo_calendar', 'tenth' ),
				11 => ezpI18n::tr( 'mugo_calendar', 'eleventh' ),
				12 => ezpI18n::tr( 'mugo_calendar', 'twelfth' ),
				13 => ezpI18n::tr( 'mugo_calendar', 'thirteenth' ),
				14 => ezpI18n::tr( 'mugo_calendar', 'fourteenth' ),
				15 => ezpI18n::tr( 'mugo_calendar', 'fifteenth' ),
				16 => ezpI18n::tr( 'mugo_calendar', 'sixteenth' ),
				17 => ezpI18n::tr( 'mugo_calendar', 'seventeenth' ),
				18 => ezpI18n::tr( 'mugo_calendar', 'eighteenth' ),
				19 => ezpI18n::tr( 'mugo_calendar', 'nineteenth' ),
				20 => ezpI18n::tr( 'mugo_calendar', 'twentieth' ),
				21 => ezpI18n::tr( 'mugo_calendar', 'twenty-first' ),
				22 => ezpI18n::tr( 'mugo_calendar', 'twenty-second' ),
				23 => ezpI18n::tr( 'mugo_calendar', 'twenty-third' ),
				24 => ezpI18n::tr( 'mugo_calendar', 'twenty-fourth' ),
				25 => ezpI18n::tr( 'mugo_calendar', 'twenty-fifth' ),
				26 => ezpI18n::tr( 'mugo_calendar', 'twenty-sixth' ),
				27 => ezpI18n::tr( 'mugo_calendar', 'twenty-seventh' ),
				28 => ezpI18n::tr( 'mugo_calendar', 'twenty-eighth' ),
				29 => ezpI18n::tr( 'mugo_calendar', 'twenty-ninth' ),
				30 => ezpI18n::tr( 'mugo_calendar', 'thirtieth' ),
				31 => ezpI18n::tr( 'mugo_calendar', 'thirty-first' ),
			];

		$ini = eZINI::instance( 'mugo_calendar.ini' );
		$this->settings = $ini->group( 'Calendar' );
	}

	public function attributes(): array
	{
		$properties = parent::attributes();

		$properties[] = 'recurrence_type';

		return $properties;
	}

	public function attribute( $attr, $noFunction = false )
	{
		switch( $attr )
		{
			case 'recurrence_type':
				{
					return $this->getRecurrenceType();
				}
				break;

			default:
				{
					return parent::attribute( $attr, $noFunction );
				}
		}
	}

	// DB values should have been an integer value
	private $ordinalToNumberMap = array(
		'first' => 1,
		'second' => 2,
		'third' => 3,
		'fourth' => 4,
		'fifth' => 5,
		'sixth' => 6
	);
}