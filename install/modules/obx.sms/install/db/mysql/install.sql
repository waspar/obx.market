-- messages
create table if not exists obx_sms_log (
	ID int(11) not null auto_increment,
	PROVIDER_ID varchar(16) not null,
	MESSAGE_ID varchar(100) not null default '',
	TEL_NO int(12) not null default 0,
	TEXT varchar(350) null default '',
	TIME_STAMP timestamp not null,
	DATE datetime not null,
	STATUS varchar(255) not null default 'none',
	primary key(ID),
	unique obx_sms_log(PROVIDER_ID,MESSAGE_ID),
	KEY `TEL_NO` (`TEL_NO`)
);