<?php
require_once 'AltoRouter.php';
$router = new AltoRouter();

$router->addRoutes(array(

    array('GET|POST', '/', 'titleList.php', 'Title-list'),
    array('GET|POST', '/titles/[i:title_id]', 'list.php', 'Post-list'),
    array('GET|POST', '/titles/[i:title_id]/popular', 'popularPosts.php', 'Popular-posts'),
    array('GET|POST', '/titles/[i:title_id]/controversial', 'controversialPosts.php', 'Controversial-posts'),
    array('GET|POST', '/posts/[i:id]', 'posts.php', 'Post-view'),
    array('GET|POST', '/replies/[i:id]', 'replies.php', 'Reply-view'),
    array('GET|POST', '/login', 'login.php', 'Login'),
    array('GET|POST', '/signup', 'signup.php', 'Signup'),
    array('GET|POST', '/logout', 'logout.php', 'Logout'),
    array('GET|POST', '/settings/profile', 'settings.php', 'Settings'),
    array('GET|POST', '/activity', 'activity.php', 'Activity-feed'),
    array('GET|POST', '/settings/account', 'cedarSettings.php', 'Closedverse-settings'),
    array('GET|POST', '/admin_panel', 'admin/admin.php', 'Admin'),
    array('GET|POST', '/admin_panel/[*:action]', 'admin/admin.php', 'Admin-option'),
    array('GET|POST', '/titles/new', 'create_community.php', 'Create-community'),
    array('GET|POST', '/titles/[i:title_id]/edit', 'edit_community.php', 'Edit-community'),
    array('POST', '/messages/[i:id]/rm', 'deleteMessage.php', 'Delete-Message'),
    array('GET|POST', '/messages/[*:action]', 'messages.php', 'Messages'),
    array('GET', '/users/[*:action]/posts', 'users.php', 'Users'),
    array('GET', '/users/[*:action]/replies', 'userReplies.php', 'User-replies'),
    array('GET', '/users/[*:action]/yeahs', 'userYeahs.php', 'User-yeahs'),
    array('GET', '/users/[*:action]/nahs', 'userNahs.php', 'User-nahs'),
    array('GET', '/users/[*:action]/friends', 'userFriends.php', 'Friends'),
    array('GET', '/users/[*:action]/following', 'userFollowing.php', 'Following'),
    array('GET', '/users/[*:action]/followers', 'userFollowers.php', 'Followers'),
    array('GET', '/communities/favorites', 'favorites.php', 'Your-Favorites'),
    array('GET', '/users/[*:action]/favorites', 'favorites.php', 'Favorites'),
    array('GET', '/titles/search', 'searchTitles.php', 'Search-titles'),
    array('GET', '/communities/official', 'allOfficialTitles.php', 'All-Official-Titles'),
    array('GET', '/communities/user', 'allUserTitles.php', 'All-User-Titles'),
    array('GET', '/messages', 'conversations.php', 'Conversations'),
    array('GET', '/notifications', 'notifs.php', 'Notifs'),
    array('GET', '/friend_requests', 'friendRequests.php', 'Freind-Requests'),
    array('GET', '/check_update.json', 'check_update.php', 'Check-update'),
    array('GET', '/users', 'searchUsers.php', 'Search-users'),
    array('GET', '/admin_messages', 'adminMessages.php', 'Admin-messages'),
    array('GET', '/rules', 'rules.php', 'Rules'),
    array('GET', '/blocked', 'blocked.php', 'Blocked-Users'),
    array('GET', '/type_search', 'typeSearch.php', 'Type-Search'),
    array('GET', '/emoji_search', 'searchEmoji.php', 'Search-Emoji'),
    array('POST', '/yeah', 'yeah.php', 'Yeah'),
    array('POST', '/posts/[i:id]/delete', 'deletePost.php', 'Delete-Post'),
    array('POST', '/replies/[i:id]/delete', 'deleteReply.php', 'Delete-Reply'),
    array('POST', '/posts/[i:id]/replies', 'postReply.php', 'Comment'),
    array('POST', '/posts/[i:id]/edit', 'editPost.php', 'Edit-Post'),
    array('POST', '/replies/[i:id]/edit', 'editReply.php', 'Edit-Reply'),
    array('POST', '/posts/[i:id]/favorite', 'favoritePost.php', 'Favorite'),
    array('POST', '/settings/profile_post.unset.json', 'favoritePost.php', 'Unfavorite'),
    array('POST', '/notifications/clear', 'clearNotifs.php', 'Clear-notifs'),
    array('POST', '/users/[*:action]/friend_new', 'friends/sendFriendRequest.php', 'Send-Friend-Request'),
    array('POST', '/users/[*:action]/friend_cancel', 'friends/cancelFriendRequest.php', 'Cancel-Friend-Request'),
    array('POST', '/users/[*:action]/friend_reject', 'friends/rejectFriendRequest.php', 'Reject-Friend-Request'),
    array('POST', '/users/[*:action]/friend_accept', 'friends/becomeFriends.php', 'Become-Friends'),
    array('POST', '/users/[*:action]/friend_delete', 'friends/deleteFriend.php', 'Delete-Friend'),
    array('POST', '/users/[*:action]/block', 'block.php', 'Block-User'),
    array('POST', '/users/[*:action]/unblock', 'unblock.php', 'Unblock-User'),

    // Put other arrays here
    array('GET|POST', '/settings/theme', 'theme_settings.php', 'Theme-settings')
));

// Match the current request
$match = $router->match(urldecode($_SERVER['REQUEST_URI']));
if ($match) {
    foreach ($match['params'] as &$param) {
        ${key($match['params'])} = $param;
    }
    require_once $match['target'];
} else {
    http_response_code(404);
    exit('Page not found');
}
