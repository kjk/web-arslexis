-- mysql -h localhost -u koviorg -p
-- pwd: Dingo123
-- use koviorg_arslexis;

-- table with passwords that people can use to download
-- noah_pro2.zip
create table dl_pwds (
  pwd varchar(50) NOT NULL UNIQUE,
  when_added timestamp(12), 
  last_used timestamp(12),
  status char(1) DEFAULT 'n' NOT NULL, -- can be n-normal, d-disabled, r-rogue i.e. not added by me
  dl_count int DEFAULT '0' NOT NULL
);

-- table that stores paypal transaction info
-- for each transaction we log every variable
-- from HTTP POST request. We'll worry about
-- analyzing it later.

create table paypal_trans (
  id int DEFAULT '0' NOT NULL auto_increment,
  trans_date timestamp(12),
  PRIMARY KEY (id)
);

create table paypal_trans_info (
  id int NOT NULL,
  varname varchar(250),
  value varchar(250)
);

create table paypal_dls (
  count int DEFAULT '0' NOT NULL, -- how many times download has been performed
  login varchar(250) NOT NULL, -- login (e-mail address) used to download this software
  pwd   varchar(250) NOT NULL, -- password
  product_id varchar(250) NOT NULL, -- which product it's about
  disabled_p char(1) DEFAULT 'n' NOT NULL -- to make it possible to disable downloads
                                          -- without removing the row in the table
);
