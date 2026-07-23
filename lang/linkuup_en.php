<?php
declare(strict_types=1);
namespace GDO\LinkUUp\lang;
return [

	'lup_welcome' => 'Welcome!',
	'lup_staff' => 'Staff Area',
	'err_ws_auth' => 'The connection failed and you cannot login.',

	'keywords' => ' LinkUUp, local chat app, location based chat, establishments with a chatroom, free city chat',

	'module_LinkUUp' => 'LinkUUp',
	'lup_category' => 'Category',
	'lup_course_visible' => 'Show visited places',

	'room_name' => 'Room',
	'room_show_distance' => 'Display distance?',
	'room_view' => 'View radius',
	'room_radius' => 'Chat radius',
	'tt_radius_in_km' => 'Radius in kilometers',
	'lup_owner' => 'Owner',

	'err_room' => 'This room is unknown.',
	'err_room_not_near' => 'This room is not in chat distance.',
	'err_not_in_room' => 'You did not join this chatroom.',
	'err_user_not_near' => 'This user is not near you.',
	'err_perm_view_lup_room' => 'View a room',

	'perm_lup_owner' => 'Owner',
	'perm_lup_worker' => 'Employee',

	'cfg_lup_app_url' => 'URL to LUP/APP',
	'cfg_lup_guest_query' => 'Allow guests to pm/query?',
	'cfg_lup_only_one_chat' => 'Only one chatroom at once?',
	'cfg_lup_ticket_engine' => 'Enable room ticket engine?',
	'cfg_lup_profile_likes_guests' => 'Allow guests to like someone?',
	'cfg_lup_num_top_comments' => 'Num top comments',
	'cfg_lup_course_visible' => 'Who may see your location visits?',

	'cfg_lup_icq' => 'Your ICQ number',
	'cfg_lup_eyecolor' => 'Your eye color',
	'cfg_lup_sexo' => 'Your sexual orientation',
	'cfg_lup_height' => 'Your height in meters',
	'cfg_lup_interest' => 'You are interested in',
	'cfg_lup_whatsapp' => 'Your WhatsApp number',
	'cfg_lup_aboutme' => 'About you',
	'cfg_lup_has_pet' => 'Do you have a pet?',
	'cfg_lup_drinks' => 'Do you drink alcohol?',
	'cfg_lup_smokes' => 'Do you smoke?',
	'cfg_lup_sporty' => 'Do you do sports?',
	'cfg_lup_origin' => 'Where are you from?',
	'cfg_lup_state' => 'Wich state do you live?',
	'cfg_lup_city' => 'Which city do you live?',
	'cfg_lup_graph_width' => 'Graph width in pixels',
	'cfg_lup_graph_height' => 'Graph height in pixels',

	'link_edit_room' => 'Edit room',
	'link_edit_room_workers' => 'Assign employees',

	'mt_linkuup_addcoworker' => 'Add employee',
	'err_lup_validate_exactly_one' => 'Please fill out exactly one of these fields.',
	'lup_add_coworker_by_name' => 'Add a coworker via username.',
	'lup_add_coworker_by_email' => 'Add a coworker via invitation mail.',

	'lup_room_workers' => '%s coworkers in %s %s',
	'lup_room_workers_invited' => '%s coworkers not registered yet',

	'your_lup_stats' => 'Statistics',
	'your_lup_rooms' => 'Rooms',
	'your_lup_coworkers' => 'Employees',

	'mt_linkuup_editroom' => 'Edit room ´%s´',

	'link_edit_room_comments' => 'Edit comments',

	'graph_usercount' => 'User count',
	'graph_messagecount' => 'Message count',

	# 6.10.6
	'lup_room' => 'Room',
	'lup_icq_visible' => 'ICQ Number visibility',
	'lup_category ' => 'Category',
	'list_linkuup_categorylist' => '%s Categories',

	# 6.11.0
	'box_content_lup_main' => 'LinkUUp Staff Area',
	'link_rooms' => 'Rooms',
	'link_add_room' => 'Add Room',
	'link_cats' => 'Categories',
	'link_add_cat' => 'Add Category',

	'mt_linkuup_welcome' => 'Welcome!',

	'lup_eyecolor_visible' => 'Who may see your eye color?',
	'lup_sexo_visible' => 'Who may see your sexual orientation?',
	'lup_height_visible' => 'Who may see your body height?',
	'lup_interest_visible' => 'Who may see your interests?',
	'lup_whatsapp_visible' => 'Who may see your WhatsApp number?',
	'lup_has_pet_visible' => 'Who may know of your pets?',
	'lup_drinks_visible' => 'Who may know if you drink alcolhol?',
	'lup_smokes_visible' => 'Who may know if you smoke?',
	'lup_sporty_visible' => 'Who may know if you do sports?',
	'lup_origin_visible' => 'Who may know your origin country?',
	'lup_state_visible' => 'Who may know the state you live in?',
	'lup_city_visible' => 'Who may know the city you live in?',
	'lup_aboutme_visible' => 'Who may know more about you?',

	'enum_sexi_related' => 'in a friendship',
	'enum_sexi_married' => 'married',
	'enum_sexi_open_relation' => 'in an open relationship',
	'enum_sexi_casual' => 'casual sex',
	'enum_sexi_no_thx' => 'not interested',
	'enum_sexi_searching' => 'actively looking',

	'enum_lup_sporty' => 'sporty',
	'enum_lup_unsporty' => 'unsporty',

	'enum_lup_smokes_yes' => 'yes',
	'enum_lup_smokes_no' => 'no',
	'enum_lup_smokes_no_way' => 'no way!',
	'enum_lup_smokes_no_care' => 'i don\'t care',

	'enum_lup_drink_yes' => 'yes',
	'enum_lup_drink_sometimes' => 'sometimes',
	'enum_lup_drink_never' => 'never',

	# Eye color
	'enum_amber' => 'braun',
	'enum_green' => 'grün',
	'enum_green_brown' => 'grün/braun',
	'enum_gray' => 'grau',
	'enum_blue' => 'blau',
	'enum_light_brown' => 'hellbraun',
	'enum_light_blue' => 'hellblau',
	'enum_blue_green' => 'blau/grün',

	# Sex
	'enum_hetero' => 'Hetero',
	'enum_homo' => 'Homo',
	'enum_bisexual' => 'Bi',
	'enum_asexual' => 'Asexuell',

	'mt_linkuup_categorylist' => 'Categories',
	'mt_linkuup_rooms' => 'Rooms',
	'list_linkuup_rooms' => '%s Rooms',

	'mt_linkuup_roomcomments' => 'Room Comments',
	'list_linkuup_roomcomments' => '%s Room Comments',

	'mt_linkuup_main' => 'Staffarea',

	# 7.0.1
	'mt_linkuup_statistics' => 'Statistics',
	'mt_linkuup_admin' => 'Admin Section',
	'mt_linkuup_editmenu' => 'Edit Menu',
	'mt_linkuup_roomicon' => 'Icon',
	'mt_linkuup_roomimage' => 'Image',
	'mt_linkuup_graphusercount' => 'Usercount Graph',
	'mt_linkuup_graphmessagecount' => 'Messagecount Graph',
	'mt_linkuup_categoryicon' => 'Category Icon',
	'mt_linkuup_coworkers' => 'Coworkers',

	'person_height' => 'Height',
	'lup_status' => 'Currently Doing',
	'lup_sexual_orientation' => 'Sexual Orientation',
	'lup_icq' => 'ICQ Number',
	'lup_eyecolor' => 'Eye Color',
	'lup_interest' => 'Interested',
	'lup_has_pet' => 'Has a Pet',
	'lup_drinks' => 'Drinks Alcohol',
	'lup_smokes' => 'Smokes Cigarettes',
	'lup_sporty' => 'Does Sports',
	'lup_origin' => 'Origin of Country',
	'lup_state' => 'Living State',
	'lup_city' => 'Living City',

];
