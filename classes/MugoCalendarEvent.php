<?php

class MugoCalendarEvent
{
    /** @var string */
    public $id;

    /** @var DateTime */
    public $start;

    /** @var DateTime */
    public $end;

    public $isAllDay;

    /* @var eZContentObject DEPRECATED: use $objectAttribute instead */
    public $contentobject;

    /* @var eZContentObjectAttribute */
    protected $objectAttribute;

    /* @var eZContentObjectTreeNode */
    public $node;

    /* @var MugoRecurrence */
    public $recurrence;

    public $instance;

    public $type;

    /* @var mixed Any data you'd like to store in context of an event */
    public $data;

    /** @var string See http://userguide.icu-project.org/formatparse/datetime */
    private static $dayFormat = 'EEEE, d MMMM y';
    private static $timeFormat = 'hh:mmaaa';

    /**
     * MugoCalendarEvent constructor.
     * @param array $eventData
     */
    function __construct( $eventData )
    {
        if( is_array( $eventData ) )
        {
            foreach( $eventData as $key => $value )
            {
                $this->$key = $value;
            }
        }
        else
        {
            $this->id = $eventData->attribute( 'id' );
            $this->type = $eventData->attribute( 'type' );
            $this->data = $eventData->attribute( 'data' );

            if( $eventData->attribute( 'start' ) !== null )
            {
                $this->start = MugoCalendarFunctions::strToDateTime( $eventData->attribute( 'start' ) );
            }
            if( $eventData->attribute( 'end' ) !== null )
            {
                $this->end = MugoCalendarFunctions::strToDateTime( $eventData->attribute( 'end' ) );
            }
            if( isset( $eventData->contentobject ) )
            {
                $this->contentobject = $eventData->contentobject;
            }
            if( isset( $eventData->objectAttribute ) )
            {
                $this->objectAttribute = $eventData->objectAttribute;
            }

            switch( $eventData->attribute( 'type' ) )
            {
                case MugoCalendarPersistentObject::TYPE_RECURRING:
                {
                    $recurrence = json_decode( $eventData->attribute( 'reference' ) );

                    // adding range end
                    $recurrence->end = '';
                    if( $eventData->attribute( 'recurrence_end' ) )
                    {
                        $recurrence->end = $eventData->attribute( 'recurrence_end' );
                    }

                    $this->recurrence = new MugoRecurrence(
                        $recurrence,
                        $this
                    );
                }
                break;

                case MugoCalendarPersistentObject::TYPE_EXCEPTION:
                {
                    $reference = $eventData->attribute( 'reference' );

                    if( $reference )
                    {
                        $this->instance = $reference;
                    }
                }
                break;
            }
        }

        if( $this->start !== null && $this->end !== null )
        {
            //allDay event: start/end hours/minutes are 0
            $this->isAllDay = ( !intval( $this->start->format( 'Gi' ) ) && !intval( $this->end->format( 'Gi' ) ) );
        }
    }

    /**
     * @param DateTime $testDate
     * @return bool
     */
    public function occursOnDate( DateTime $testDate )
    {
        if( $this->recurrence )
        {
            return $this->recurrence->occursOnDate( $testDate );
        }
        else
        {
            $testEndDate = clone $testDate;
            $testEndDate->modify( '+1 day' );

            /*
             * We need to handle 2 cases:
             *   1. Opening hours special rules
             *   2. Normal all-day events
             * They are both considered "all-day events"
             * However, normal all-day events currently store the end date as the same as the start day; opening hours special rules store the end date as midnight of the next day
             * Therefore, we have to do a special check for normal all-day events as to whether the start date matches the end date
             * TODO: standardize the logic where the end date is consistenly stored across those 2 cases
            */
            $eventEndDate = clone $this->getEndDateTime();
            $eventStartDate = clone $this->getStartDateTime();

            if( $eventStartDate == $eventEndDate )
            {
                $eventEndDate->modify( '+1 day' );
            }

            return $this->getStartDateTime() < $testEndDate && $eventEndDate > $testDate;
        }
    }

    public function attributes()
    {
        return array(
            'start',
            'end',
            'id',
            'object',
            'attribute',
            'all_day_event',
            'recurrence',
            'type',
            'parent_id',
            'instance',
            'data',
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
                return $this->getContentObject();
            }
            break;

            case 'attribute':
            {
                return $this->objectAttribute;
            }
            break;

            case 'all_day_event':
            {
                return $this->isAllDay;
            }
            break;

            case 'recurrence':
            {
                return $this->recurrence;
            }
            break;

            case 'type':
            {
                $map = array(
                    0 => 'undefined',
                    1 => 'single',
                    2 => 'recurring',
                    3 => 'exception',
                );

                return $map[ $this->type ];
            }
            break;

            case 'instance':
            {
                return $this->instance;
            }
            break;

            case 'id':
            {
                return $this->id;
            }
            break;

            case 'data':
            {
                return $this->data;
            }
            break;
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

    /**
     * @return DateTime
     */
    public function getStartDateTime()
    {
        return $this->start;
    }

    /**
     * @return DateTime
     */
    public function getEndDateTime()
    {
        return $this->end;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return eZContentObject
     */
    public function getContentObject()
    {
        if( $this->contentobject )
        {
            return $this->contentobject;
        }
        else
        {
            return $this->objectAttribute->attribute( 'object' );
        }
    }

    /**
     * @return eZContentObjectAttribute
     */
    public function getObjectAttribute()
    {
        return $this->objectAttribute;
    }

    /**
     * @return array
     */
    public function toFullCalendarEvent()
    {
        //TODO: unsure about that - could be a timezone issue
        $endDateTime = clone $this->end;
        if( $this->isAllDay )
        {
            $endDateTime->modify( '+1 day' );
        }

        return array(
            'id' => $this->id,
            'title' => $this->getContentObject()->attribute( 'name' ),
            'start' => $this->start->format( 'c' ),
            'end' => $endDateTime->format( 'c' ),
            'allDay' => $this->isAllDay,
            'url' => $this->getUrl(),
        );
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        $url = '';
        if( $this->node )
        {
            $url = $this->node->attribute( 'url_alias' );
        }
        elseif( $this->getContentObject() )
        {
            $url = $this->getContentObject()->attribute( 'main_node' )->attribute( 'url_alias' );
        }
        if( $url )
        {
            eZURI::transformURI( $url );
        }

        return $url;
    }

    /**
     * NOT COMPLETE - do not use - fix then change to public
     */
    private function toMugoCalendarPersistentObject()
    {
        $return = new MugoCalendarPersistentObject();
        $return->id = $this->id;
        //$return->attribute_id = $this->id;
        //$return->version = $this->id;
        $return->start = $this->start->getTimestamp();
        $return->end = $this->end->getTimestamp();
        if( $this->recurrence )
        {
        }
    }

    public function __toString()
    {
        if( $this->recurrence )
        {
            return $this->recurrence->__toString();
        }
        else
        {
            $return = '';

            $formatter = new IntlDateFormatter( Locale::getDefault(), IntlDateFormatter::SHORT, IntlDateFormatter::SHORT );

            if( $this->start->diff( $this->end )->days > 0 )
            {
                $formatter->setPattern( self::$dayFormat );
                $return .= $formatter->format( $this->start );

                if( !$this->isAllDay )
                {
                    $formatter->setPattern( self::$timeFormat );
                    $return .= ' '. $formatter->format( $this->start );
                }

                $return .= ' ';
                $return .= ezpI18n::tr( 'mugo_calendar_date_description','to' );
                $return .= ' ';

                $formatter->setPattern( self::$dayFormat );
                $return .= $formatter->format( $this->end );

                if( !$this->isAllDay )
                {
                    $formatter->setPattern( self::$timeFormat );
                    $return .= ' '. $formatter->format( $this->end );
                }
            }
            else
            {
                $formatter->setPattern( self::$dayFormat );
                $return .= $formatter->format( $this->start );

                $return .= ' ';

                if( $this->isAllDay )
                {
                    $return .= ezpI18n::tr( 'mugo_calendar_date_description','all day' );
                }
                else
                {
                    $formatter->setPattern( self::$timeFormat );
                    $return .= $formatter->format( $this->start );
                    $return .= '-';
                    $return .= $formatter->format( $this->end );
                }
            }

            return $return;
        }
    }

    /**
     *
     */
    public function __clone()
    {
        $this->id = $this->id;

        if( is_object( $this->start ) )
        {
            $this->start = clone $this->start;
        }
        if( is_object( $this->end ) )
        {
            $this->end = clone $this->end;
        }
    }
}
