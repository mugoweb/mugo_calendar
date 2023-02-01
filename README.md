#Mugo Calendar Extension for eZ Publish

Supporting recurring events and exceptions.

## Installation
* Add extension to the ezp extension directory
* Import the DB schema changes from sql/mysql/schema.sql into your DB 
* Enable the extension in the settings
* Clear cache
* Regenerate autoload map
* Add permission to allow user groups to access the 'mugo_calendar' module

## Configuration
The extension provides a new datatype "Mugo Recurring Event". Add this datatype
as an attribute to one of your content classes. It will allow you to work
with calendar events in context of this content class.

## Feature ideas
* Allow user to specify how many instances he wants for a recurring event
* Yearly recurrence