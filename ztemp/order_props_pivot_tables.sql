SET @sql = NULL;
SELECT
  GROUP_CONCAT(DISTINCT
    CONCAT(
      'MAX(IF(prop.CODE = ''',
      CODE,
      ''', value.VALUE, NULL)) AS PROPERTY_',
      CODE
    )
  ) INTO @sql
FROM
  obx_order_property;
SET @sql = CONCAT('SELECT o.*,res.* FROM obx_orders as o
	LEFT JOIN (
		SELECT value.ORDER_ID,', @sql, ' FROM obx_order_property_values as value
		LEFT JOIN obx_order_property as prop ON (value.PROPERTY_ID = prop.ID)
		WHERE value.ORDER_ID = 1
	) AS res on (o.ID = res.ORDER_ID)
');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;