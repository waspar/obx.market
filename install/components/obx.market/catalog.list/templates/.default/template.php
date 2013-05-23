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
					<?= $arItem["PRICE_FORMATTED"] ?>
				</div>
			</div>

			<div class="name">
				<?= $arItem["NAME"] ?>
			</div>

			<a href="<?= $arItem["BUY_LINK"] ?>">
				<?= GetMessage("BUY_LINK") ?>
			</a>
		</div>
	<? endforeach; ?>
</div>