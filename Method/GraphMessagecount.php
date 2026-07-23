<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;

use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Plot\LinePlot;
use Amenadiel\JpGraph\Themes\UniversalTheme;
use GDO\Core\Application;
use GDO\Core\GDO_ArgError;
use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_Response;
use GDO\Date\Time;
use GDO\JPGraph\MethodGraph;
use GDO\LinkUUp\LUP_MessageSent;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;

/**
 * Render a messagecount graph.
 *
 * @author gizmore
 * @version 7.0.3
 */
final class GraphMessagecount extends MethodGraph
{

	##################
	### Parameters ###
	##################
	public function defaultWidth(): int { return Module_LinkUUp::instance()->cfgGraphWidth(); }

	public function defaultHeight(): int { return Module_LinkUUp::instance()->cfgGraphHeight(); }

	public function gdoParameters(): array
	{
		$params = [
			GDT_Object::make('room')->notNull()->table(LUP_Room::table()),
		];
		return array_merge($params, parent::gdoParameters());
	}

	public function hrefImage(): string
	{
		return parent::hrefImage() . "&room={$this->getRoom()->getID()}";
	}

	/**
	 * @throws GDO_ArgError
	 */
	public function getRoom(): LUP_Room
	{
		return $this->gdoParameterValue('room');
	}

	###############
	### Execute ###
	###############

	/**
	 * @throws GDO_ArgError
	 */
	public function hasPermission(GDO_User $user, string &$error, array &$args): bool
	{
		if (!($room = $this->getRoom()))
		{
			$error = 'err_permission_required';
			return false;
		}
		if (!$room->canEdit($user))
		{
			$error = 'err_not_allowed';
			$args = [t('err_perm_view_lup_room')];
			return false;
		}
		return true;
	}

	##############
	### Render ###
	##############
	public function renderGraph(Graph $graph, $ts, $te): GDT
	{
		# params
		$room = $this->getRoom();
		$start = $this->getStart();
		$end = $this->getEnd();

		# init
		$datax = [];
		$datay1 = [];
		$t = $ts;
		$xend = $te + Time::ONE_DAY;
		while ($t <= $xend)
		{
			$day = date('Y-m-d', (int)round($t));
			$datax[$day] = $day;
			$datay1[$day] = 0;
			$t += Time::ONE_DAY;
		}

		# query data
		$table = LUP_MessageSent::table();
		$query = $table->select('SUM(lms_count) messages_sent');
		$query->select('DATE(lms_date) messages_date');
		$query->where("lms_room={$room->getID()}");
		$query->where("lms_date BETWEEN '$start' AND '$end'");
		$query->group('messages_date');
		$query->order('messages_date');
		$result = $query->exec();

		# gather data
		while ($row = $result->fetchRow())
		{
			[$count, $date] = $row;
			$datay1[$date] = $count;
		}

		// Setup the graph
		$graph = new Graph($this->getWidth(), $this->getHeight());
		$graph->SetScale('textint');

		$theme_class = new UniversalTheme();

		$graph->SetTheme($theme_class);
		$graph->img->SetAntiAliasing(false);
		$graph->title->Set($this->getMethodTitle());
		$graph->SetBox(false);

		$graph->SetMargin(40, 20, 36, 63);

		$graph->img->SetAntiAliasing();

		$graph->yaxis->HideZeroLabel();
		$graph->yaxis->HideLine(false);
		$graph->yaxis->HideTicks(false, false);

		$graph->xgrid->Show();
		$graph->xgrid->SetLineStyle('solid');
		$graph->xaxis->SetTickLabels($this->filterXAxisDaily($datax));
		$graph->xaxis->SetLabelAngle(45);
		$graph->xgrid->SetColor('#E3E3E3');

		// Create the first line
		$p1 = new LinePlot(array_values($datay1));
		$graph->Add($p1);
		$p1->SetColor('#6495ED');
		$p1->SetLegend(t('graph_messagecount'));
		$graph->legend->SetFrameWeight(1);
		if (!Application::$INSTANCE->isUnitTests())
		{
			$graph->Stroke();
		}
		Application::exit();
		return GDT_Response::make();
	}


}
