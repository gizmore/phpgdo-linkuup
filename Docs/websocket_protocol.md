# This document documents the binary protocol of the lup websocket server.

Author: christian@busch-peine.de / +49 176 - 59 59 88 44

## General thoughts

The gdo6 websocket protocol describes itself via it's GDO and GDT classes.
GDO entities export their fields via a a few http request [11] [12]
This way, the binary data is exported and parsed accordingly to the system. Sadly it's not perfect yet. Method parameters are not exported yet, but it's easy to wrap a method to become a websocket method that expects the same parameters as the MethodForm in the right order.
Strings are null terminated.

[11] http://gdo6.gizmore.org/index.php?mo=Core&me=GetEnums&_ajax=1
[12] http://gdo6.gizmore.org/index.php?mo=Core&me=GetTypes&_ajax=1

## Command Helpers

Many commands are just wrappers for GDO/Form/MethodForm. The parameters then have to match those of MethodForm. Those are currently not documented well.

## Login handshake

If a user is not authenticated, it has to call 0x0001 with a single string as parameter; it's cookie value. A cookie value is obtained by GDO/Websocket/Method/GetSecret [21] beforehand.

[21] index.php?mo=Websocket&me=GetSecret

### Client Packet headers

The client may only send binary websocket data.
The first two bytes indicate the command, and if it shall sync with a reply. When synced, the submission can use promises to react to it's response. Unsynced messages can not receive a response for a certain message. This adds 3 byte overhead for the msgid.

Example command 0x9116. Synced message with cmd 0x1116

The parameters for commands vary, and sadly, are not always created by the system.
Thus, I will list them here.
In general each Command needs to be registered with GWS_Commands::register(<16bitcmd>, new <CommandHandlerClass>()); so you can search the codebase for it ( search for GWS_Commands::register ).

### Datatypes

It all boils down to intN, float32, string, enum and dates(int32).

Sometimes, on lists, new items are transmitted until stream ends (terminated by msg length (End of Data))

Some structures are re-used:

- GDT_Pagemenu (@TODO Needs docs)
- fullUserPayload (@TODO Needs docs)
- all GDO can be auto encoded, like LUP_Notification
- MethodForm / GWS_CommandForm auto encode as well

### Responses

On MethodForm responses, a 200 OK means all fine.

### Server commands

This section describes commands that are called by the client to the server. E.g.: change avatar, send a message to a room, etc.
Note that some commands are serving both, server and client commands.
PARAM means input, VALUES means output for synced messages.

0x0101 Continue as guest
 PARAM -none-
VALUES JSON / gdoUserJSON

0x0102 Register
 PARAM see GDO\Register\Method\Form
VALUES JSON / gdoUserJSON

0x0103 Login
 PARAM see GDO\Login\Method\Form
VALUES JSON / gdoUserJSON

0x0104 Logout
 PARAM -none-
VALUES JSON / gdoUserJSON

0x0105 PING(?) There is hook magic there
 PARAM -none-
VALUES -none-

0x0106 Password recovery
 PARAM GDO\Recovery\Method\Form
 
0x0107 Save a setting
 PARAM string module, string key, string var
VALUES -none- 200 OK

0x0108 Get Debug data
 PARAM -none-
VALUES JSON / GDT_PerfBar::data

0x0109 setLanguage
 PARAM string langaugeIso
VALUES -none- 200 OK

0x0111 Facebook login
 PARAM uint32 fbExpire, string fbAccessToken, string fbCookie
VALUES 200 OK

0x0112 Instagram Login
VALUES string accessToken

0x0121 Wrapper for the account form. Change Email etc.
 PARAM Ouch.. this even depends on Module_Account settings. (TODO)

0x0401 Wraps method GDO/Avatar/Method/Set to set a user's avatar.
 PARAM See method parameters
 
0x0402 Wraps method GDO/Avatar/Method/Upload to a user's avatar.
 PARAM See method parameters

0x0602 Accept a friendship
 PARAM int32 userId, int32 friendid 
 
0x0603 List friendships for a user
 PARAM int32 userId
    
0x0604 Send friendship removed for user and friend (2msgs)
 PARAM int32 userId, int32 friendid

0x0901 Show profile for a user.
 PARAM int32 userId
VALUES 

0x1101 Get a list of rooms within lat/lng.
 PARAM float32 lat, float32 lng
VALUES List of LUP_Room + GDO_Address + List of joinedUserIds + 0terminator

0x1102 Get room metadata.
 PARAM uint32 roomId
VALUES LUP_Room, GDO_Address, List of int32 userId

0x1103 Join a LUP_Room
 PARAM int32 roomId, float32 lat, float32 lon, string roomPassword
VALUES List of int32 Room user Ids

0x1104 Part a LUP_Room
 PARAM int32 roomId
 
0x1105 Get multiple user info
 PARAM List of int32 userId
VALUES List of fullUserPayload
 
0x1106 Get user info
 PARAM int32 userId
VALUES fullUserPayload

0x1107 Send a message to a room.
 PARAM int32 roomId, string message
VALUES int32 time, int32 user, int32 room, string message

0x1108 Send a message to a user.
 PARAM int32 userId, string message
VALUES LUP_QueryMessage->payload()

0x1109 Mark a PM as read
 PARAM int32 queryMessageId
VALUES LUP_QueryMessage->payloadStatus()


0x110A Get all latest PM for a user.
 PARAM -none-
VALUES List of LUP_QueryMessage

0x110B Get messages for a private conversation.
 PARAM int32 userId, int32 timestampCut
VALUES List of LUP_QueryMessage

0x110C Delete a PM conversation
 PARAM int32 friendId
VALUES int32 numDeletedMessages

0x1110 Change your status
 PARAM string status
VALUES int32 userId, string status

0x1111 Count a profile view
 PARAM int32 userId
VALUES int32 userId, int32 provileViews

0x1112 Update users geoposition
 PARAM float32 lat, float32 lon
 
0x1120 Vote for a room.
 PARAM int32 roomId, int8 rating
VALUES LUP_Room + GDO_Address
 
0x1121 Get a page of comments for a room.
 PARAM int32 roomId, int16 pageNum
VALUES GDT_Pagemenu, List of int32 commentId, int32 creatorId, string message, int32 timestamp

0x1122 Get the newest comment for a room
 PARAM int32 roomId
VALUES int32 totalCommentCount, int32 commentId, int32 creatorId, string message, int32 timestamp

0x1123 Get own comment and rating for a room and own user.
 PARAM -none-
VALUES int32 roomId, int32 userId, int8 scoreRoomRating, string commentText, int32 numLikes

0x1124 Save a room comment.
 PARAM int32 roomId, GDO\LinkUUp\Method\WriteRoomComment
VALUES -no-reply-sent- @see 0x1141

0x1125 Get user list for a room.
 PARAM int32 roomId
VALUES List of int32 userId

0x1126 Get the top comments for a room.
 PARAM int32 roomId
VALUES List of int32 commentId, int32 creatorId, string message, int32 timestamp

0x1127 Delete a comment
 PARAM int32 commentId
VALUES -none-

0x1130 Like another user
 PARAM int32 userId
VALUES -none- 200 OK

0x1131 Send friend request
 PARAM int32 friendid (See GDO\Friends\Method\Request)

0x1132 Accept friendship 
 PARAM int32 userId, int32 friendid

0x1133 Get list of likers(?) # unsure on this command
 PARAM int32 userId
VALUES int32 userId + List of int32 userId, int32 likeCount(?)

0x1135 Check if you may see a users friendlist.
 PARAM int32 userId
 
0x1134 Remove Friendship
 PARAM int32 friendid

0x1141 Get notifications for a user, younger than timestamp
 PARAM int32 timestamp
VALUES List of LUP_Notification

0x1142 Mark a notifcation as read
 PARAM int32 notfication id
VALUES int32 notfication id

0x1143 Get unread notification count
 PARAM -none-
VALUES int32 notificationCount, int32 PMCount

0x1144 Delete a notification
 PARAM int32 notfication id
VALUES int32 notfication id

0x1151 Request a user's gallery images
 PARAM int32 userId
 
0x1152 Wrapper. Upload a gallery image
 PARAM See GDO\Gallery\Method\Crud.php
VALUES See GDO\Gallery\Method\Crud.php
 
0x1153 Delete one of your GDO_GalleryImages
 PARAM int32 FileId
VALUES 200 OK

0x1160 Get an overview of the visited locations for a user.
 PARAM int32 userId
VALUES List of int32 roomId, int32 visitCount, int32 timestampLastVisit

0x1161 Search a user by name
 PARAM string searchTerm
VALUES List of fullUserPayload

0x1162 Is user course visible for you?
  PARAM int32 userId
VALUES -none- / 200 OK

0x1190 Get help topic marks. What help topics have been shown?
 PARAM -none-
VALUES JSON array of help topics.

0x1191 Mark a help topic read
 PARAM string key
VALUES 200 OK

0x1192 Reset help topic marks
 PARAM -none-
VALUES -none-



### Client Commands

This section describes commands that are sent by the server to the client. E.g.: avatar changed, new message, etc.

0x0401 Broadcasts an avatar change to all clients.
VALUES int32 userId, int32 avatarfile id

0x0402 Broadcasts an avatar change to all clients.
VALUES int32 userId, int32 avatarfile id

0x0601 Send friendship request ()
VALUES int32 userId, int32 friendid

0x0602 Send friendship accepted to user and friend (2msgs)
VALUES int32 userId, int32 friendid

0x0603 List friendships for a user
VALUES LIST of int32 userId

0x0604 Send friendship removed for user and friend (2msgs)
VALUES int32 userId, int32 friendid

0x1104 A user parted a LUP_Room
VALUES int32 timestamp, int32 roomId, int32 userId

0x1106 Refresh user
VALUES fullUserPayload

0x1110 A user changed their status
VALUES int32 userId, string status

0x1131 Send friend request
VALUES GDO LUP_Notification

0x1132 Accept friendship
VALUES Send accepted friendship to all friends of befriended user
       Payload is a GDO_Profile
       
0x1134 Remove Friendship broadcast to all friends of both users.
VALUES LUP_Notification gdo

0x1135 Check if you may see a users friendlist.
VALUES -none- but success means you may.

0x1141 New notfication
VALUES LUP_Notification

0x1151 Request a user's gallery images.
VALUES List of GDO_GalleryImage

##### 
