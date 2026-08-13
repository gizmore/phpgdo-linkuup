<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;

use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Plot\LinePlot;
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
		$graphParams = parent::gdoParameters();
		foreach ($graphParams as $param)
		{
			if ($param->getName() === 'date')
			{
				$param->initial('this_year');
			}
		}
		return array_merge($params, $graphParams);
	}

	public function hrefImage(): string
	{
		return parent::hrefImage() . "&room={$this->getRoom()->getID()}&skin=dark3";
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
		// Graph creates UniversalTheme by default. Clear it after the scale is
		// known; otherwise it repaints the entire margin white while stroking.
		$graph->ClearTheme();

		$graph->SetColor('#171a29');
		$graph->SetMarginColor('#171a29');
		// JPGraph fills the margins only while stroking a frame. Keep that
		// technical frame, but make it indistinguishable from the dark canvas.
		$graph->SetFrame(true, '#171a29', 1);
		$graph->img->SetAntiAliasing(false);
		$graph->title->Set('Chat-Aktivität');
		$graph->title->SetColor('#f4f2ff');
		$graph->SetBox(false);

		// Keep the two useful axes without bringing back the old white grid.
		$graph->SetMargin(56, 20, 36, 67);

		$graph->img->SetAntiAliasing();

		$graph->xaxis->SetColor('#68779b', '#e5eaff');
		$graph->yaxis->SetColor('#68779b', '#e5eaff');
		$graph->xaxis->HideTicks();
		$graph->yaxis->HideTicks();
		$graph->xaxis->SetLabelMargin(8);
		$graph->yaxis->SetLabelMargin(8);

		$graph->xgrid->Show(false);
		$graph->ygrid->Show(false);
		$graph->xaxis->SetTickLabels($this->calendarLabels($datax));
		$graph->xaxis->SetLabelAngle(45);

		// Create the first line
		$p1 = new LinePlot(array_values($datay1));
		$graph->Add($p1);
		$p1->SetColor('#a993ff');
		$p1->SetWeight(3);
		$p1->SetFillColor('#35295b');
		$p1->SetLegend(t('graph_messagecount'));
		$graph->legend->SetColor('#f1efff', '#20243a');
		$graph->legend->SetFillColor('#20243a');
		$graph->legend->SetFrameWeight(0);
		if (!Application::$INSTANCE->isUnitTests())
		{
			$graph->Stroke();
		}
		Application::exit();
		return GDT_Response::make();
	}

	/** Keep six useful calendar points instead of a wall of dates. */
	private function calendarLabels(array &$datax): array
	{
		$days = array_keys($datax);
		$labels = array_fill(0, count($days), '');
		$last = count($labels) - 1;
		$step = max(1, (int)ceil($last / 5));
		foreach ($days as $i => $day)
		{
			if ($i === 0 || $i === $last || $i % $step === 0)
			{
				$labels[$i] = date('d.m.', strtotime($day));
			}
		}
		return $labels;
	}


}
