CREATE TABLE mugo_calendar_event (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `attribute_id` int not null,
  `version` int not null,
  `start` int null,
  `end` int null,
  `type` int not null,
  `reference` varchar( 255 ) null,
  `recurrence_end` int null,
  `data` longtext null
)ENGINE=InnoDB;

CREATE INDEX start_i ON mugo_calendar_event( start );
CREATE INDEX end_i ON mugo_calendar_event( end );
CREATE INDEX attribute_id_i ON mugo_calendar_event( attribute_id );
CREATE INDEX version_i ON mugo_calendar_event( version );
CREATE INDEX reference_i ON mugo_calendar_event( reference );
CREATE INDEX recurrence_end_i ON mugo_calendar_event( recurrence_end );
CREATE INDEX type_i ON mugo_calendar_event( type );
