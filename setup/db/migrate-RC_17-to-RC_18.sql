create table gameslot (
	id		integer AUTO_INCREMENT,
	field 		integer NOT NULL,
	date_played	datetime,
	game_date	date,
	game_start	time,
	game_end	time,
	game_id		integer,
	PRIMARY KEY (id)
);

insert into gameslot (game_id,field,date_played,game_date,game_start) select game_id,field_id,date_played,DATE_FORMAT(date_played,'%Y-%m-%d'),TIME_FORMAT(date_played,'%H:%i') as time from schedule;

alter table schedule drop date_played;
alter table schedule drop field_id;

-- Notes field is unused, so nuke it for now
alter table field drop notes;

-- Now update field/site info
alter table site add num_fields int default 1 after code;
create table site_field_count (
	site_id 	integer,
	num_fields	integer
);
insert into site_field_count (site_id, num_fields) select site_id, MAX(num) from field group by site_id;
update site, site_field_count SET site.num_fields = site_field_count.num_fields 
WHERE site.site_id = site_field_count.site_id;
drop table site_field_count;

-- drop table field_assignment;
-- create table league_gameslot_assoc (
-- 	league		integer NOT NULL,
--	gameslot	integer NOT NULL
-- );
