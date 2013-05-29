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
				$ купить $
			</a>
			<br>
			<a href="<?= $arItem["DEL_URL"] ?>">
				- удалить -
			</a>
			<br>
			<a href="<?= $arItem["ADD_URL"] ?>">
				+ добавить +
			</a>
			<?if ($arParams["USE_QUANTITY_VARIABLE"] == "Y"):?>
				<label for="quant-<?=$arItem["ID"]?>">Количество:</label>
				<input type="text" name="<?=$arParams["QUANTITY_VARIABLE"]?>" id="quant-<?=$arItem["ID"]?>"/>
			<?endif;?>
		</div>
	<? endforeach; ?>
</div>