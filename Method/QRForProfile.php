<?php
namespace GDO\LinkUUp\Method;
use GDO\Core\GDT;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\QRCode\Method\Render;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

class QRForProfile extends \GDO\Core\Method {

    public function gdoParameters(): array
    {
        return [
            GDT_User::make('user_id')->notNull(),
        ];
    }

    public function getUser(): GDO_User {
        return $this->gdoParameterValue('user_id');
    }

    public function execute(): GDT
    {
        return (new Render())->inputs(['data' => $this->getQRCodeContent($this->getUser()), 'size' => '1024'])->execute();
    }

    private function getQRCodeContent(GDO_User $user) {
        $mod = Module_LinkUUp::instance();
        return $mod->cfgAppUrl() . '#!/profile/' . $user->getID();
    }
}
