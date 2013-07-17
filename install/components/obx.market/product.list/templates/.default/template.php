<?php
/*******************************************
 ** @product OBX:Market Bitrix Module     **
 ** @authors                              **
 **         Maksim S. Makarov aka pr0n1x  **
 **         Morozov P. Artem aka tashiro  **
 ** @license Affero GPLv3                 **
 ** @mailto rootfavell@gmail.com          **
 ** @mailto tashiro@yandex.ru             **
 ** @copyright 2013 DevTop                **
 *******************************************/

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<div class="catalog-list">
	<? foreach ($arResult["ITEMS"] as &$arItem): ?>
		<div class="item">
			<div class="info">
				<div class="weight">
					<?= $arItem["WEIGHT"] ?>
					<?= $arItem["KKAL"] ?>
				</div>
				<img src="<?= $arItem["PREVIEW_PICTURE"]["SRC"] ?>" alt="<?= $arItem["NAME"] ?>"/>

				<div class="price">
					<?= $arItem["PRICE"]["TOTAL_VALUE_FORMATTED"] ?>
				</div>
			</div>

			<div class="name">
				<?= $arItem["NAME"] ?>
			</div>

			<a href="<?= $arItem["BUY_URL"] ?>">
				$ <?=GetMessage('__BUY')?> $
			</a>
			<br>
			<a href="<?= $arItem["DEL_URL"] ?>">
				- <?=GetMessage('__DELETE')?> -
			</a>
			<br>
			<a href="<?= $arItem["ADD_URL"] ?>">
				+ <?=GetMessage('__ADD')?> +
			</a>
			<?if ($arParams["USE_QUANTITY_VARIABLE"] == "Y"):?>
				<label for="quant-<?=$arItem["ID"]?>"><?=GetMessage('__QUANTITY')?>:</label>
				<input type="text" name="<?=$arParams["QUANTITY_VARIABLE"]?>" id="quant-<?=$arItem["ID"]?>"/>
			<?endif;?>
		</div>
	<? endforeach; ?>
</div>