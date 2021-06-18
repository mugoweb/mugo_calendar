<?php
/**
 *
 * @package tests
 */

class mugoCalendarTestSuite extends ezpDatabaseTestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->setName( "Mugo Calendar Test Suite" );
        $this->addTestSuite( 'MugoCalendarTestCase' );
    }

    public static function suite()
    {
        return new self();
    }

    public function setUp()
    {
        parent::setUp();

        // make sure extension is enabled and settings are read
        // give a warning if it is already enabled
        if ( !ezpExtensionHelper::load( 'mugo_calendar' ) )
		{
			trigger_error( __METHOD__ . ': extension is already loaded, this hints about missing cleanup in other tests that uses it!', E_USER_WARNING );
		}
    }

    public function tearDown()
    {
        ezpExtensionHelper::unload( 'mugo_calendar' );
        parent::tearDown();
    }
}
