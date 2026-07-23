<?php
namespace GDO\LinkUUp;

use GDO\Core\GDT_Enum;

/**
 *
 * @author gizmore
 *
 */
final class GDT_RelationInterest extends GDT_Enum
{

	protected function __construct()
	{
		parent::__construct();
		$this->enumValues('sexi_related', 'sexi_married', 'sexi_open_relation', 'sexi_casual', 'sexi_no_thx', 'sexi_searching');
		$this->emptyLabel('not_specified');
	}

}
