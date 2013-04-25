SELECT 
	 O.ID as ID
	,O.DATE_CREATED as DATE_CREATED
	,O.ID as USER_ID
	,U.NAME as USER_NAME
	,O.STATUS_ID as STATUS_ID
	,S.CODE as STATUS_CODE
	,S.NAME as STATUS_NAME
	,O.CURRENCY as CURRENCY
	,(
		GROUP_CONCAT(CONCAT("[",I.ID,"]"," ",I.PRODUCT_NAME," - ",I.QUANTITY) SEPARATOR "\n")
	) as ITEMS
	,(
		SUM(I.PRICE_VALUE * I.QUANTITY)
	) as ITEMS_COST
	,(
		SELECT
			concat(
				'[',
				group_concat(
					concat('{ PROPERTY_ID: "', OP.ID, '"'),
					concat(', PROPERTY_TYPE: "', OP.PROPERTY_TYPE, '"'),
					concat(', PROPERTY_NAME: "', OP.NAME, '"'),
					concat(', PROPERTY_CODE: "', OP.CODE, '" }')
				),
				']'
			)
		FROM
			obx_order_property as OP
		LEFT JOIN
			obx_order_property_values as OPV ON (OPV.PROPERTY_ID = OP.ID)
		WHERE
			OPV.ORDER_ID = O.ID
		GROUP BY
			OPV.ORDER_ID
	) as PROPERTIES_JSON
FROM obx_orders as O
LEFT JOIN obx_order_status as S ON (O.STATUS_ID = S.ID)
LEFT JOIN obx_basket_items as I ON (O.ID = I.ORDER_ID)
LEFT JOIN b_user as U ON (O.USER_ID = U.ID)

WHERE O.ID = 1;


