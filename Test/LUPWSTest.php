<?php

namespace GDO\LinkUUp\Test;

use GDO\Util\WS;
use GDO\Websocket\Test\WebSocketTestCase;

/**
 * Websocket tests.
 */
class LUPWSTest extends WebSocketTestCase
{

	public function testProfile(): void
	{
		$payload = WS::fromHex("");
		$this->ws($payload);
	}

}
