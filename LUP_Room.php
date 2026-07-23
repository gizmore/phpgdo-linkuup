<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Address\GDO_Address;
use GDO\Address\GDT_Address;
use GDO\Address\GDT_Phone;
use GDO\Comments\CommentedObject;
use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_CreatedBy;
use GDO\Core\GDT_DeletedAt;
use GDO\Core\GDT_DeletedBy;
use GDO\Core\GDT_EditedAt;
use GDO\Core\GDT_EditedBy;
use GDO\Core\GDT_Float;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_ObjectSelect;
use GDO\Core\GDT_String;
use GDO\Core\GDT_Template;
use GDO\File\GDO_File;
use GDO\File\GDT_ImageFile;
use GDO\Maps\GDT_Position;
use GDO\Maps\Position;
use GDO\Net\GDT_Url;
use GDO\OpenTimes\GDT_OpenHour;
use GDO\OpenTimes\GDT_OpenHours;
use GDO\UI\GDT_Color;
use GDO\User\GDO_User;
use GDO\User\GDT_User;
use GDO\Votes\GDT_VoteCount;
use GDO\Votes\GDT_VoteRating;
use GDO\Votes\WithVotes;

/**
 * A chatroom.
 *
 * @version 7.0.3
 * @since 6.9.0
 * @author gizmore
 */
final class LUP_Room extends GDO
{

	final public const MAX_ROOM_NAME_LEN = 64;

	################
	### Comments ###
	################
	use CommentedObject;

	public static function queryRoom(string $roomId): ?self
	{
		return self::getById($roomId);
	}

	public static function getEditableRooms(GDO_User $user, $enabledRooms = false)
	{
		$select = GDT_RoomSelect::make()->editableRooms()->enabledRooms($enabledRooms);
		return $select->queryRooms();
	}

	public static function queryRooms(float $lat = null, float $lng = null, int $limit = null, int $from = 0)
	{
		# Involved tables
		$rooms = self::table();

		# Build select
		$query = $rooms->select();

		# Enabled condition
		$query->where('room_enabled=1');

		# Distance conditions
		if (is_float($lat) && is_float($lng))
		{
			$distanceWhere = Position::getDistanceQuery($lat, $lng, 'room_pos_lat', 'room_pos_lng');
			$query->where($distanceWhere . ' <= room_view');
		}

		# Limit
		if ($limit !== null)
		{
			$query->limit($limit, $from);
		}

		return $query->exec();
	}

	#############
	### Votes ###
	#############
	use WithVotes;

	public function gdoCommentTable() { return LUP_RoomComments::table(); }

	###########
	### GDO ###
	###########

	public function gdoCommentsEnabled() { return true; }

	public function gdoCanComment(GDO_User $user) { return true; }

	##############
	### Static ###
	##############

	/**
	 * @return LUP_RoomVote
	 */
	public function gdoVoteTable() { return LUP_RoomVote::table(); }

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('room_id'),
			GDT_User::make('room_owner')->label('lup_owner')->cascadeNull()->withCompletion(),
			GDT_Checkbox::make('room_enabled')->notNull()->initial('1')->label('enabled'),
			GDT_String::make('room_name')->notNull()->max(self::MAX_ROOM_NAME_LEN),
			GDT_String::make('room_info')->max(512)->label('description'),
			GDT_Color::make('room_color')->notNull(),
			GDT_ObjectSelect::make('room_category')->table(LUP_Category::table())->notNull()->label('category'),
			GDT_Position::make('room_pos')->notNull()->initialCurrent(),
			GDT_Float::make('room_view')->min(0.010)->max(42000.0)->initial('1.500')->step(0.001)->notNull()->tooltip('tt_radius_in_km'),  // Visibility radius
			GDT_Float::make('room_radius')->min(0.001)->max(42000.0)->initial('0.150')->step(0.001)->notNull()->tooltip('tt_radius_in_km'), // Chat radius
			GDT_Url::make('room_www')->allowAll()->reachable(),
			GDT_Phone::make('room_phone'),
			GDT_OpenHour::make('room_open')->hoursColumn('room_hours'),
			GDT_OpenHours::make('room_hours'),
			GDT_Address::make('room_address')->cascadeRestrict(),
			GDT_ImageFile::make('room_icon')->label('icon')->scaledVersion('icon', 64, 64),
			GDT_ImageFile::make('room_image')->label('image')->scaledVersion('icon', 64, 64)->scaledVersion('large', 800, 600),
			GDT_VoteCount::make('room_votes'),
			GDT_VoteRating::make('room_rating'),
			GDT_Checkbox::make('room_show_distance')->initial('1')->notNull(),
			GDT_EditedAt::make('room_edited'),
			GDT_EditedBy::make('room_editor'),
			GDT_DeletedAt::make('room_deleted'),
			GDT_DeletedBy::make('room_deletor'),
			GDT_CreatedAt::make('room_created'),
			GDT_CreatedBy::make('room_creator'),
		];
	}

	public function isDisabled(): bool { return !$this->gdoValue('room_enabled'); }

	###############
	### Getters ###
	###############

	public function getColor(): string { return $this->gdoVar('room_color'); }

	public function getInfo(): ?string { return $this->gdoVar('room_info'); }

	public function getPhone(): ?string { return $this->gdoVar('room_phone'); }

	public function getWww(): ?string { return $this->gdoVar('room_www'); }

	public function getOwner(): GDO_User { return $this->gdoValue('room_owner'); }

	public function getCreator(): GDO_User { return $this->gdoValue('room_creator'); }

	public function getCreatorID(): string { return $this->gdoVar('room_creator'); }

	public function getView() { return (float)$this->gdoVar('room_view'); }

	public function isInChatRange($lat, $lng) { return $this->isNearLatLng($lat, $lng, $this->getRadius()); }

	public function isNearLatLng($lat, $lng, $dist = 20) { return Position::distanceCalculation($this->getLat(), $this->getLng(), $lat, $lng) <= $dist; }

	public function getLat(): float { return (float) $this->gdoVar('room_pos_lat'); }

	public function getLng(): float { return (float) $this->gdoVar('room_pos_lng'); }

	public function getRadius(): float { return (float) $this->gdoVar('room_radius'); }

	public function getLastTicket() { return LUP_Ticket::getLastTicket($this); }

	public function getCurrentTicket() { return LUP_Ticket::getCurrentTicket($this); }

	public function getCategoryInt() { return $this->gdoColumn('room_category')->value($this->getCategory())->enumIndex(); }

	public function getCategory() { return $this->gdoVar('room_category'); }

	public function getIcon(): ?GDO_File { return $this->gdoValue('room_icon'); }

	public function getImage(): ?GDO_File { return $this->gdoValue('room_image'); }

	public function getCoworkers()
	{
		return LUP_RoomWorker::table()->getCoworkers($this);
	}

    public function href_edit()
    {
        return href('LinkUUp', 'EditRoom', '&room=' . $this->getID());
    }

    public function href_qrcode()
    {
        return href('LinkUUp', 'QRForRoom', '&room_id=' . $this->getID());
    }

	public function getID(): ?string { return $this->gdoVar('room_id'); }

	public function getName(): ?string { return $this->gdoVar('room_name'); }

	public function renderCard(): string { return GDT_Template::php('LinkUUp', 'card/room.php', ['room' => $this]); }

	public function renderList(): string { return GDT_Template::php('LinkUUp', 'list/room.php', ['room' => $this]); }

	############
	### HREF ###
	############

	public function renderOption(): string { return GDT_Template::php('LinkUUp', 'choice/room.php', ['room' => $this]); }

	public function href_coworkers()
	{
		return href('LinkUUp', 'AddCoworker', '&room=' . $this->getID());
	}

	public function href_comments()
	{
		return href('LinkUUp', 'RoomComments', '&room=' . $this->getID());
	}

	public function href_image($variant = '')
	{
		return href('LinkUUp', 'RoomImage', "&id={$this->getID()}&variant={$variant}");
	}

	public function url_chat()
	{
		$id = $this->getID();
		return Module_LinkUUp::instance()->cfgAppUrl() . "#!/location/$id/chat";
	}

	##############
	### Render ###
	##############

	public function displayAddress() { return $this->getAddressOrBlank()->renderHTML(); }

	public function getAddressOrBlank(): GDO_Address
	{
		$address = $this->getAddress();
		return $address ?: GDO_Address::blank();
	}

	public function getAddress(): ?GDO_Address { return $this->gdoValue('room_address'); }

	public function isOwner(GDO_User $user): bool
	{
		if ($user->isStaff())
		{
			return true;
		}

		if ($this->getOwnerID() === $this->getID())
		{
			return true;
		}

		return false;
	}

	###################
	### Permissions ###
	###################

	public function getOwnerID(): string { return $this->gdoVar('room_owner'); }

	public function canEdit(GDO_User $user): bool
	{
		if ($user->isStaff())
		{
			return true;
		}

		if (LUP_RoomWorker::isWorker($user))
		{
			return true;
		}

		if ($this->getOwnerID() === $user->getID())
		{
			return true;
		}

		return false;
	}

}
