<?php
namespace GDO\LinkUUp\Method;
use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\QRCode\Method\Render;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

class QRForRoom extends \GDO\Core\Method {

    public function gdoParameters(): array
    {
        return [
            GDT_Object::make('room_id')->table(LUP_Room::table())->notNull(),
        ];
    }

    public function getRoom(): LUP_Room {
        return $this->gdoParameterValue('room_id');
    }

    public function execute(): GDT
    {
        return (new Render())->inputs(['data' => $this->getQRCodeContent($this->getRoom()), 'size' => '1024'])->execute();
    }

    private function getQRCodeContent(LUP_Room $room) {
        $mod = Module_LinkUUp::instance();
        return $mod->cfgAppUrl() . '#!/location/' . $room->getID();
    }
}
