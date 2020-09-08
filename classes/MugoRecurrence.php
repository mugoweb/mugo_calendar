<?php

/**
 * Class MugoRecurrence
 */
class MugoRecurrence
{
	/** @var DateTime */
	public $rangeEnd;

        /** @var DateTime */
	public $rangeStart;

	public $type;

	public $interval;

	public $day;

	public $weeklyWeekDay;

	public $monthlyType;

	public $monthlyWeekDay;

	/** @var  MugoCalendarEvent */
	protected $event;

	/** @var array */
	protected $settings;

	protected $weekDays = array();

	/**
	 *  @var array Number to Ordinal number
	 */
	private $numberToOrdinalMap = array();

	/**
	 * MugoRecurrence constructor.
	 * @param stdClass $jsonObj
	 * @param MugoCalendarEvent $event
	 */
	public function __construct( $jsonObj, $event )
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

		$this->type = $jsonObj->type;
		$this->interval = $jsonObj->interval;
		$this->day = $jsonObj->day;
		$this->weeklyWeekDay = $jsonObj->weeklyWeekDay;
		$this->monthlyType = $jsonObj->monthlyType;
		$this->monthlyWeekDay = $jsonObj->monthlyWeekDay;
        $this->rangeStart = $event->start;

		if( $jsonObj->end )
		{
			$this->rangeEnd = MugoCalendarFunctions::strToDateTime( $jsonObj->end );
		}

		$this->event = $event;
	}

	/**
	 * Called before occursOnDate
	 *
	 * @return $this
	 */
	public function init()
	{
		$ini = eZINI::instance( 'mugo_calendar.ini' );
		$this->settings = $ini->group( 'Calendar' );

		return $this;
	}

	/**
	 * @param DateTime $date
	 * @return bool
	 */
	public function occursOnDate( DateTime $date )
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

		if( $this->occursInRange( $date ) )
		{
			$modifyString = '';

			switch( $this->type )
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
                            $modifyString = '+' . ( (int) $date->format( 'd' ) - $this->day ) . ' day this month';
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
                    $modifyString = $this->event->start->format( 'jS F' );
                }
                break;
			}

			if( $modifyString )
			{
				$testDate = clone $date; /* @var $testDate DateTime */
				$testDate->modify( $modifyString );

				$match = $date == $testDate;

				if(
					$match &&
					isset( $this->interval ) &&
					$this->interval > 1
				)
				{
					switch( $this->type )
					{
						case 'Monthly':
						{
							$tStart = $this->getRangeStart();
							$tStart->modify( 'First day of this month' );
							$tThis = clone $date;
							$tThis->modify( 'First day of this month' );

							$interval = $tStart->diff( $tThis )->m + ( $tStart->diff( $tThis )->y * 12 );

							$match = $interval % ( $this->interval ) == 0;
						}
						break;

						case 'Weekly':
						{
							$tStart = $this->getFirstDayOfWeekDay( clone $this->getRangeStart() );
							$tThis =  $this->getFirstDayOfWeekDay( clone $date );

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

	protected function occursInRange( DateTime $date )
	{
		if( $date >= $this->getRangeStart() )
		{
			if( is_null( $this->getRangeEnd() ) || $date <= $this->getRangeEnd() )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param DateTime $dateTime
	 * @return DateTime
	 */
	public function getRelativeTime( DateTime $dateTime )
	{
		$start = new DateTime( $dateTime->format( 'Y-m-d' ) );

		return $start->diff( $dateTime )->format( '+%h hours %i minutes' );
	}

	/**
	 * @param MugoCalendarEvent $event
	 * @return $this
	 */
	public function setEvent( MugoCalendarEvent $event )
	{
		$this->event = $event;
		return $this;
	}
	
	/**
	 * @param DateTime $end
	 * @return $this
	 */
	public function setRangeEnd( DateTime $end )
	{
		$this->rangeEnd = $end;
		return $this;
	}

	/**
	 * @return DateTime
	 */
	public function getRangeStart()
	{
		$return = null;

		if( $this->event )
		{
			if( $this->event->start )
			{
				$startDate = clone $this->event->start;
				$startDate->modify( 'midnight' );

				$return = $startDate;
			}
		}

		return $return;
	}

	/**
	 * @return DateTime
	 */
	public function getRangeEnd()
	{
		return $this->rangeEnd;
	}

    /**
     * @param string $dateFormat
     * @return string
     */
	public function describe( $dateFormat = '%Y-%m-%d' )
    {
        $return = ezpI18n::tr( 'mugo_calendar_date_description', 'every' ) . ' ';

        if( $this->type == 'Weekly' )
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
        elseif( $this->type == 'Monthly' )
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
	 * @param DateTime $dateTime
	 * @return DateTime
	 */
	protected function getFirstDayOfWeekDay( DateTime $dateTime )
	{
		$localDateTime = clone $dateTime;
		$weekDay = $this->settings[ 'WeekOffset' ] ? 'Monday' : 'Sunday';

		return $localDateTime->modify( $weekDay . ' this week' );
	}

	//
	// Make it usable in eztemplates
	//
	public function attributes()
	{
		return array(
			'description',
			'range_start',
			'range_end',
            'type',
            'monthly_type',
            'monthly_week_day',
		);
	}

	public function attribute( $attr, $noFunction = false )
	{
		switch( $attr )
		{
			case 'description':
			{
				return $this->describe();
			}
			break;

			case 'range_start':
			{
				$start = $this->getRangeStart();
				if( $start )
				{
					return $start->getTimestamp();
				}

				return null;
			}
			break;

			case 'range_end':
			{
				$end = $this->getRangeEnd();
				if( $end )
				{
					return $end->getTimestamp();
				}

				return null;
			}
			break;

            case 'type':
            {
                return $this->type;
            }
            break;

            case 'monthly_type':
            {
                return $this->monthlyType;
            }
            break;

            case 'monthly_week_day':
            {
                return $this->monthlyWeekDay;
            }
            break;
        }
	}

	public function hasAttribute( $attr )
	{
		return in_array( $attr, $this->attributes() );
	}

	// optional
	public function setAttribute( $attr, $value )
	{
		$this->$attr = $value;
	}

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->describe();
    }

    /**
	 * @param string $type
	 * @return MugoRecurrence
	 */
	static function factory( $type )
	{
		$className = 'MugoRecurrence' . $type;

		if( class_exists( $className ) )
		{
			return new $className;
		}
		else
		{
			return new MugoRecurrence();
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
