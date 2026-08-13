<?php

use GDO\JPGraph\GDT_GraphDateselect;
use GDO\LinkUUp\GDT_RoomGraph;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Method\GraphMessagecount;
use GDO\LinkUUp\Method\GraphUsercount;
use GDO\UI\GDT_Link;

/**
 * @var LUP_Room[] $rooms
 */
foreach ($rooms as $room) :

	$inputs = [
		'room' => $room->getID(),
	];
	$categoryVisuals = [
		'1' => ['fas fa-globe-europe', 'category-country'], '2' => ['fas fa-city', 'category-city'],
		'3' => ['fas fa-cocktail', 'category-bar'], '4' => ['fas fa-beer', 'category-pub'],
		'5' => ['fas fa-coffee', 'category-cafe'], '6' => ['fas fa-briefcase', 'category-business'],
		'7' => ['fas fa-shopping-bag', 'category-shop'], '8' => ['fas fa-place-of-worship', 'category-religion'],
		'9' => ['fas fa-cut', 'category-salon'], '10' => ['fas fa-map-marked-alt', 'category-town'],
		'11' => ['fas fa-compact-disc', 'category-club'], '12' => ['fas fa-theater-masks', 'category-culture'],
		'13' => ['fas fa-futbol', 'category-sport'], '14' => ['fas fa-utensils', 'category-food'],
		'15' => ['fas fa-tree', 'category-outdoors'], '16' => ['fas fa-school', 'category-education'],
		'17' => ['fas fa-university', 'category-education'], '18' => ['fas fa-hospital', 'category-health'],
		'19' => ['fas fa-hotel', 'category-hotel'],
	];
	$categoryVisual = $categoryVisuals[$room->getCategory()] ?? ['fas fa-map-marker-alt', 'category-default'];

	?>
    <div class="lup-room-statistics">
        <div class="statistics-room col-xs-12 col-sm-3">
			<div class="lup-stat-category-icon <?=$categoryVisual[1]?>" title="Kategorie"><i class="<?=$categoryVisual[0]?>"></i></div>
			<span class="lup-stat-category-name">Kategorie</span>
            <h2><?php
				echo $room->gdoDisplay('room_name'); ?></h2>
			<a class="lup-stat-qrcode" href="<?=$room->href_qrcode()?>" title="QR-Code für <?=$room->gdoDisplay('room_name')?>"><i class="fas fa-qrcode"></i><span>QR</span></a>
            <div><?=GDT_Link::make()->href($room->url_chat())->render()?></div>
        </div>
        <div class="col-xs-12 col-sm-9 grapics">
			<?=GDT_GraphDateselect::make('date')->initial('this_year')->addClass('lup-graph-select')->withToday(false)->withYesterday(false)->render()?>
			<input class="lup-graph-custom-date" type="date" name="start" disabled="disabled" style="display:none"/>
			<input class="lup-graph-custom-date" type="date" name="end" disabled="disabled" style="display:none"/>
            <div class="lup-room-graph-container">
                <div class="statistics-usercount col-xs-12 col-sm-6">
					<?=GDT_RoomGraph::make()->room($room)->graphMethod(GraphUsercount::make()->appliedInputs($inputs))->withoutDateInput()->render()?>
                </div>
                <div class="statistics-messagecount col-xs-12 col-sm-6">
					<?=GDT_RoomGraph::make()->room($room)->graphMethod(GraphMessagecount::make()->appliedInputs($inputs))->withoutDateInput()->render()?>
                </div>
            </div>
        </div>
    </div>
<?php
endforeach; ?>
<script>
    document.addEventListener("DOMContentLoaded", function (event) {

		function changeGraph(cont, select) {
			var date = select.val();
			var start = cont.find("input[name=start]").val();
			var end = cont.find("input[name=end]").val();
			// The range fields are meaningful only for a manual date range.
			// Keeping them hidden otherwise removes the empty white block below
			// the timeframe selector.
			cont.find('.lup-graph-custom-date').toggle(date === 'custom');
            cont.find('form').each(function () {
                cont.find('input').prop('disabled', date !== 'custom');
                var form = $(this);
                form.find('img').each(function () {
                    window.GDO.JPGraph._renderImage($(this), date, start, end);
                });
            });
        }

        jQuery('input[type=date]').change(function () {
            var input = $(this);
            var cont = input.parent();
            var select = cont.find('select');
            changeGraph(cont, select);
        });

        jQuery('select.lup-graph-select').change(function () {
            var select = $(this);
            var cont = select.parent();
            changeGraph(cont, select);
        });
    });
</script>
