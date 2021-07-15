<?php

class MugoCalendarExceptionEventDefinition extends MugoCalendarEventDefinition
{
	/** @var string */
	protected $for;

	public function __construct( $eventPersistentObject )
	{
		parent::__construct( $eventPersistentObject );

		$reference = $eventPersistentObject->attribute( 'reference' );

		if( $reference )
		{
			$this->for = $reference;
		}
	}

	/**
	 * @return int
	 */
	public function getType() :? int
	{
		return MugoCalendarPersistentObject::TYPE_EXCEPTION;
	}

	public function getFor() : string
	{
		return $this->for;
	}

	public function attributes(): array
	{
		$properties = parent::attributes();

		$properties[] = 'instance';
		$properties[] = 'for';

		return $properties;
	}

	public function attribute( $attr, $noFunction = false )
	{
		switch( $attr )
		{
			case 'instance':
			case 'for':
				{
					return $this->for;
				}
				break;

			default:
			{
				return parent::attribute( $attr, $noFunction );
			}
		}
	}
}