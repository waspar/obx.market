-- Валюты
create table if not exists obx_currency (
	CURRENCY char(3) not null,
	SORT int(11) not null default 100,
	COURSE decimal(18,2) not null default '1',
	RATE decimal(18,2) not null default '1',
	IS_DEFAULT char(1) not null default 'N',

	primary key(CURRENCY)
);
-- Языковый форматы валют
create table if not exists obx_currency_format (
	ID int(11) not null auto_increment,
	CURRENCY char(3) not null,
	LANGUAGE_ID char(2) not null,
	NAME varchar(255) not null,
	FORMAT varchar(50) not null default '#',
	THOUSANDS_SEP varchar(5) not null default '',
	DEC_POINT char(1) NULL,
	DEC_PRECISION tinyint(2) not null default 2,
	primary key(ID),
	unique udx_obx_currency_format(CURRENCY, LANGUAGE_ID)
);
-- Цены
create table if not exists obx_price (
	ID int(11) not null auto_increment,
	CODE varchar(16) not null,
	CURRENCY char(3) not null,
	NAME varchar(255) not null,
	SORT int(11) not null default 100,
	primary key(ID),
	unique udx_obx_price(CODE)
);
-- Таблица доступа к ценам групп пользвоателей
create table if not exists obx_price_group(
	PRICE_ID int(11) not null,
	GROUP_ID int(11) not null,
	primary key (PRICE_ID,GROUP_ID)
);

-- ИБ котрые содержат товары
create table if not exists obx_ecom_iblock (
	IBLOCK_ID int(11) not null,
	PRICE_VERSION tinyint(2) not null default '1',
	VAT_ID int(11) NULL default '0',

	primary key(IBLOCK_ID)
);

-- Хранение цен в свойствах ИБ PRICE_VERSION=1
create table if not exists obx_price_ibp (
	-- ID int(11) not null auto_increment,
	IBLOCK_ID int(11) not null,
	IBLOCK_PROP_ID int(11) not null,
	PRICE_ID int(11) not null,

	-- primary key(ID),
	unique obx_price_ibpr(IBLOCK_ID, PRICE_ID),
	unique obx_price_ibprp(PRICE_ID, IBLOCK_PROP_ID),
	-- ниже почти невозможная ситуация (одно св-во не может быть в 2х иб сразу), тем не менее стоит обработать на уровне БД
	unique obx_price_ibpp(IBLOCK_ID, IBLOCK_PROP_ID)
);


-- НДС
create table if not exists obx_vat (
	ID int(11) not null auto_increment,
	NAME varchar(50) not null,
	SORT int(11) not null default 100,
	VAT_RATE decimal(18,2) not null default '0.00',

	primary key(ID)
);

-- Цены товаров
create table if not exists obx_price_ibe (
	ID int(11) not null auto_increment,
	ELEMENT_ID int(11) not null,
	PRICE_ID int(11) not null,
	PRICE_VALUE decimal(18,5) not null default '0',
	VAT_INCLUDE char(1) null default 'N',
	VAT_ID int(11) NULL default '0',

	primary key(ID),
	unique udx_obx_ecom_ibe_price(ELEMENT_ID, PRICE_ID)
);
-- Другие данные товаров, типа веса и пр.
create table if not exists obx_ecom_ibe (
	ID int(11) not null auto_increment,
	ELEMENT_ID int(11) not null,
	WEIGHT int(11) not null default '0',
	QUANTITY int(11) not null default '0',
	DECR_ON_ORDER char(1) not null default 'N',

	primary key(ID),
	unique udx_obx_ecom_ibe(ELEMENT_ID)
);

-- Таблица заказов
create table if not exists obx_orders (
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp on update current_timestamp not null default current_timestamp,
	DATE_CREATED timestamp not null,
	USER_ID int(11) not null,
	MODIFIED_BY int(11) not null default 0,
	MANAGER_ID int(11) NULL,
	STATUS_ID int(11) not null default 1,
	CURRENCY char(3) not null,
	DELIVERY_ID int(11) null,
	DELIVERY_COST decimal(18,2) not null default 0,
	PAY_ID int(11) null,
	PAY_TAX_VALUE decimal(18,2) not null default 0,
	DISCOUNT_ID int(11) null,
	DISCOUNT_VALUE decimal(18,2) not null default 0,
	primary key(ID),
	key USER_ID (USER_ID)
);

-- Таблица корзин пользователей
create table if not exists obx_user_basket (
	ID int(11) not null auto_increment,
	DATE_CREATE timestamp not null,
	DATE_UPDATE timestamp not null,
	USER_ID int(11) NULL,
	VISITOR_ID int(11) NULL,
	primary key(ID)
);

-- Таблица товаров в заказе
create table if not exists obx_basket_items (
	ID int(11) not null auto_increment,
	ORDER_ID int(11) NULL,
	VISITOR_ID int(18) NULL,
	PRODUCT_ID int(11) NULL,
	PRODUCT_NAME varchar(255) not null,
	QUANTITY int(11) not null default 1,
	WEIGHT decimal(18,2) not null default 0,
	PRICE_ID int(11) not null,
	PRICE_VALUE decimal(18,2) not null default 0,
	DISCOUNT_VALUE decimal(18,2) not null default 0,
	VAT_ID int(11) null,
	VAT_VALUE decimal(18,2) not null default 0,
	unique udx_obx_basket_items(VISITOR_ID, ORDER_ID, PRODUCT_ID),
	primary key(ID)
);
-- Таблица статусов заказов
create table if not exists obx_order_status (
	ID int(11) not null auto_increment,
	CODE varchar(16) not null,
	NAME varchar(255) not null,
	DESCRIPTION text NULL,
	CHANGE_HANDLER_CLASS varchar(255) NULL,
	CHANGE_HANDLER_METHOD varchar(255) NULL,
	COLOR char(6) NULL,
	SORT int(11) not null default '100',
	ACTIVE char(1) not null default 'Y',
	PERMISSION SMALLINT not null default 3,
	-- ALLOW_CHANGE_STATUS				1 - обратить внимание сюда. Это для финального статуса заказа
	-- ALLOW_CHANGE_ITEMS 				2
	-- ALLOW_CHANGE_DELIVERY_ID		4
	-- ALLOW_CHANGE_PAY_ID				8
	-- ALLOW_CHANGE_VAT						16
	-- ALLOW_CHANGE_DISCOUNT			32
	-- ALLOW_CHANGE_ALL						63
	IS_SYS char(1) not null default 'N',
	-- Системный стутус, нельзя удалить и сменить CODE
	primary key (ID),
	unique udx_obx_order_status(CODE)
);

-- Таблица свойств заказов
create table if not exists obx_order_property (
	ID int(11) not null auto_increment,
	CODE varchar(16) not null,
	NAME varchar(255) not null,
	DESCRIPTION text NULL,
	-- S - String - <input type="text">
	-- T - Text - <textarea>
	-- N - Numeric - <input type="text"> - проверяется на is_numeric
	-- L - List - список <select>
	-- C - Checkbox
	PROPERTY_TYPE char(1) not null default 'S',
	SORT int(11) not null default '100',
	ACTIVE char(1) not null default 'Y',
	ACCESS char(1) not null default 'W',
	-- W - свойство редактируется пользователем на этапе оформления заказа
	-- R - свойство выводится, но редактируется только менеджером
	-- S - системное свойство. не выводится пользователю
	IS_SYS char(1) not null default 'N',
	-- Системное свойство, нельзя удалить и сменить CODE
	primary key (ID),
	unique udx_obx_order_property(CODE)
);

-- Таблица значений списочных свойств заказов
create table if not exists obx_order_property_enum (
	ID int(11) not null auto_increment,
	PROPERTY_ID int(11) not null,
	CODE varchar(16) not null,
	VALUE text not null,
	SORT int(11) not null default '100',
	IS_DEFAULT char(1) NULL,
	primary key(ID),
	unique udx_obx_order_property_enum(CODE, PROPERTY_ID)
);
-- Таблица значений свойств заказов
create table if not exists obx_order_property_values (
	ID int(11) not null auto_increment,
	ORDER_ID int(11) not null,
	PROPERTY_ID int(11) not null,
	VALUE_S varchar(255) NULL,
	VALUE_T text NULL,
	VALUE_N decimal(18,4) NULL,
	VALUE_L int(11) NULL,
	VALUE_C char(1) NULL,
	primary key(ID),
	unique udx_obx_order_property_values(ORDER_ID, PROPERTY_ID)
);
-- Таблица комментраиев заказа
create table if not exists obx_order_comments (
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp on update current_timestamp not null default current_timestamp,
	DATE_CREATED timestamp not null,
	ORDER_ID int(11) not null,
	USER_ID int(11) not null,
	REPLY_ID int(11) NULL,
	MESSAGE text not null,
	primary key (ID)
);

