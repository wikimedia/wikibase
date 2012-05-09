-- MySQL version of the database schema for the WikibaseLib extension.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >


-- Change feed.
CREATE TABLE IF NOT EXISTS /*_*/wb_changes (
  change_id                  INT unsigned        NOT NULL auto_increment PRIMARY KEY, -- Id of change
  change_type                VARCHAR(25)         NOT NULL, -- Type of the change
  change_time                varbinary(14)       NOT NULL, -- Time the change was made
  change_info                BLOB                NOT NULL -- Holds additional info about the change, inc diff and stuff
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_changes_change_type ON /*_*/wb_changes (change_type);
CREATE INDEX /*i*/wb_changes_change_time ON /*_*/wb_changes (change_time);