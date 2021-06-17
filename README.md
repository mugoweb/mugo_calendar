#Mugo Calendar Extension for eZ Publish

Supporting recurring events and exceptions.

## Installation
* Add extension to the ezp extension directory
* Import the DB schema changes from sql/mysql/schema.sql into your DB 
* Enable the extension in the settings
* Clear cache
* Regenerate autoload map
* Add permission to allow user groups to access the 'mugo_calendar' module

## Customizations
There is an ezpackage containing an example content class 'event'. It is located under the 'doc' directory.
Import this class or create a new content class. Make sure that you *always* use both datatypes 'Mugo Calendar Event' and 'Mugo Recurring Event'
in a content class.

## Feature ideas
* Allow user to specify how many instances he wants for a recurring event
* Yearly recurrence