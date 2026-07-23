<?php

use GDO\JPGraph\GDT_GraphDateselect;
use GDO\LinkUUp\GDT_RoomGraph;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Method\GraphMessagecount;
use GDO\LinkUUp\Method\GraphUsercount;
use GDO\QRCode\GDT_QRCode;
use GDO\UI\GDT_Link;

/**
 * @var LUP_Room[] $rooms
 */
foreach ($rooms as $room) :

	$inputs = [
		'room' => $room->getID(),
	];

	?>
    <div class="lup-room-statistics">
        <div class="statistics-room col-xs-12 col-sm-3">
            <img src="<?php
			echo $room->href_image('icon'); ?>" title="Room image" alt="Room image"/><br/>
            <h2><?php
				echo $room->gdoDisplay('room_name'); ?></h2>
            <div><?=GDT_QRCode::make()->qrcodeSize(128)->var($room->url_chat())->render()?></div>
            <div><?=GDT_Link::make()->href($room->url_chat())->render()?></div>
        </div>
        <div class="col-xs-12 col-sm-9 grapics">
			<?=GDT_GraphDateselect::make('date')->initial('7days')->addClass('lup-graph-select')->withToday(false)->withYesterday(false)->render()?>
            <input type="date" name="start" disabled="disabled"/>
            <input type="date" name="end" disabled="disabled"/>
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
