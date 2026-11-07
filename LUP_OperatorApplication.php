<?php
declare(strict_types=1);
namespace GDO\LinkUUp;
use GDO\Core\{GDO,GDT_AutoInc,GDT_Object,GDT_String,GDT_Text,GDT_Enum,GDT_UInt};
use GDO\Date\GDT_Timestamp;
use GDO\User\GDT_User;

/** Private operator requests; no automatic account, subscription or venue assignment. */
final class LUP_OperatorApplication extends GDO
{
    public function gdoEngine(): string { return 'InnoDB'; }
    public function gdoCached(): bool { return false; }
    public function gdoColumns(): array
    {
        return [
            GDT_AutoInc::make('oa_id'),
            GDT_User::make('oa_user')->notNull(),
            GDT_Object::make('oa_room')->table(LUP_Room::table()),
            GDT_String::make('oa_business')->max(160)->notNull(),
            GDT_String::make('oa_kind')->max(100)->notNull(),
            GDT_String::make('oa_address')->max(300)->notNull(),
            GDT_String::make('oa_contact')->max(160)->notNull(),
            GDT_String::make('oa_email')->max(254)->notNull(),
            GDT_String::make('oa_phone')->max(80),
            GDT_Text::make('oa_details')->max(2000)->notNull(),
            GDT_Enum::make('oa_status')->enumValues('pending','approved','rejected','revoked')->notNull()->initial('pending'),
            GDT_UInt::make('oa_version')->notNull()->initial('1'),
            GDT_Timestamp::make('oa_created')->notNull(),
            GDT_Timestamp::make('oa_reviewed'),
            GDT_User::make('oa_reviewer'),
            GDT_Text::make('oa_review_note')->max(2000),
            GDT_Text::make('oa_history')->max(60000)->notNull(),
        ];
    }
}
