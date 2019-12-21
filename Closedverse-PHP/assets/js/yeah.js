/* I'll give a bit of documentation on the functions here.

popup(title, text):
	it does a popup box that looks like miiverses with an ok button to close it 

	also fuck you


*/


var atBottom = false;
var offset = 1;
var loading = 0;
var textcomplete = 0;
var editor = 0;
$.pjax.defaults.timeout = 5000;

// call this function whenever loading new posts
function reloadYeahTooltip() {
	window.tip.destroyAll();
	window.emojiTip.destroyAll();

	window.tip = tippy('.empathy', {
		animation: 'shift-away',
		arrow: true,
		dynamicTitle: true
	});
	window.emojiTip = tippy('.emoji', {
		delay: 100,
		animation: 'fade',
		arrow: true,
		dynamicTitle: true
	});
}

function popup(title, text, buttons) {
	$('body').append('<div class="dialog active-dialog modal-window-open mask">\
		<div class="dialog-inner">\
			<div class="window">\
				<h1 class="window-title">' + title + '</h1>\
				<div class="window-body">\
					<p class="window-body-content">' + text + '</p>\
					<div class="form-buttons">' + buttons + '</div>\
				</div>\
			</div>\
		</div></div>');
	bindEvents();
}

function getNotifs() {
	$.getJSON('/check_update.json', function(data) {
		if (data.notifs.unread_count > 0) {
			favicon.badge(data.notifs.unread_count);
			$('#global-menu-news .badge').show().text(data.notifs.unread_count);
		} else {
			$('#global-menu-news .badge').hide().text(data.notifs.unread_count);
		}

		if (data.messages.unread_count > 0) {
			favicon.badge(data.messages.unread_count);
			$('#global-menu-message .badge').show().text(data.messages.unread_count);
		} else {
			$('#global-menu-message .badge').hide().text(data.messages.unread_count);
		}

		if (data.messages.unread_count == 0 && data.notifs.unread_count == 0) {
			favicon.reset();
		}

		if (data.messages.unread_count > 0 && data.notifs.unread_count > 0) {
			favicon.badge(data.messages.unread_count + data.notifs.unread_count);
		}
	});
}

setInterval(function(){ 
    	getNotifs();
    }, 30000);

var favicon = new Favico({
    animation:'none'
});

function bindEvents() {
	if (textcomplete != 0) {
			delete textcomplete;
			delete editor;
		}

	if ($('.mention').length) {
        editor = new Textcomplete.editors.Textarea(document.getElementsByClassName('mention')[0]);
        textcomplete = new Textcomplete(editor);

        textcomplete.register([{
            // Emoji strategy
            match: /(^|\s)@(\w+)$/,
            search: function (term, callback) {
                $.getJSON("/type_search", { q: term }, function (data) {
                    // `data` is an array
                    callback(data);
                });
            },
            template: function (name) {
                return '<span class="search-icon-container'+ name.online +'"><img class="search-icon" src="' + name.user_face + '"></span><span class="search-user-item-info"><span class="search-nickname">' + name.nickname + '</span> <span class="search-username">@' + name.user_name + '</span></span>';
            },
            replace: function (value) {
                return '$1@' + value.user_name + ' ';
            }
        }, {
            // Emoji strategy
            match: /(^|\s):(\w+)$/,
            search: function (term, callback) {
                $.getJSON("/emoji_search", { q: term }, function (data) {
                    // `data` is an array
                    callback(data);
                });
            },
            template: function (name) {
                return '<span class="search-icon-container emoji"><img class="search-icon" src="' + name.emoji_url + '"></span><span class="search-user-item-info emoji"><span class="search-nickname">:' + name.emoji_name + ':</span></span>';
            },
            replace: function (value) {
                return '$1:' + value.emoji_name + ': ';
            }
        }]);
    }

	$(".trigger").off().on('click', (function(){
		var href = $(this).attr('data-href');
		
		//if you click on a post it takes you to 'data-href' an attribute defined in list.php for each post
		
		$.pjax({url: href, container: '#main-body'});
    }));

    // prevents accidentally opening a post when trying to view the Yeah to Nah ratio on mobile
    $('.empathy').off().on('click', function(e) {
    	e.stopImmediatePropagation();
    });

    $('.my-menu-dark-toggle').off().on('click', function(e) {
    	e.preventDefault();

    	var v = document.cookie.match('(^|;) ?dark-mode=([^;]*)(;|$)');
    	isDark = v ? v[2] : null;

    	if (isDark == null) {
    		var d = new Date();
    		d.setTime(d.getTime() + (365*24*60*60*1000));
    		var expires = "expires="+ d.toUTCString();
    		document.cookie = "dark-mode=1;" + expires + ";path=/";

    		$('link[href="/assets/css/style.css"]').after('<link rel="stylesheet" type="text/css" href="/assets/css/dark.css">');
    		$('.empty-icon').children().attr('src', '/assets/img/dark-empty.png');
    	} else {
    		document.cookie = 'dark-mode=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/';

    		$('link[href="/assets/css/dark.css"]').remove();
    		$('.empty-icon').children().attr('src', '/assets/img/empty.png');
    	}
    });


    // prevents opening a post when clicking a link
    $('.post-link').off().on('click', function(e) {
    	e.stopImmediatePropagation();
    });

    $('.mention-link').off().on('click', function(e) {
    	e.stopImmediatePropagation();
    })

    $('.textarea-text').off().on('click', function() {
    	$('#post-form').removeClass('folded');
    });

	$('.nah').off().on('click', function(event) {
		event.stopPropagation();
		var postId = $(this).attr('id');
		var nahType = $(this).attr('data-track-label');

		//changes the nah button to disabled so no one nahs twice while its posting the first nah just like on miiverse holy shish
		$('#' + postId + '.nah').attr('disabled', '');

		$.post('/nah.php', {postId:postId, nahType:nahType}, function(data) {
			if(data == 'success'){

				$('#' + postId + '.nah').addClass('nah-added');
				$('#' + postId + '.nah').find('.nah-button-text').text('Un-nah.');
				$('#'+postId).closest('div').find('.yeah-count').text(Number($('#'+postId).closest('div').find('.yeah-count').text()) - 1);

				$('#'+postId).closest('div').find('[nahs]').attr('nahs', Number($('#'+postId).closest('div').find('[nahs]').attr('nahs')) + 1);


				if ($('#' + postId + '.yeah').hasClass('yeah-added')) {
					$('#' + postId + '.yeah').removeClass('yeah-added');
					$('#' + postId + '.yeah').find('.yeah-button-text').text('Yeah!');
					$('#' + postId).closest('div').find('.yeah-count').text(Number($('#'+postId).closest('div').find('.yeah-count').text()) - 1);

					$('#'+postId).closest('div').find('[yeahs]').attr('yeahs', Number($('#'+postId).closest('div').find('[yeahs]').attr('yeahs')) - 1);

					if(nahType == 0){ 
						$('.icon-container.visitor').attr('style', 'display: none;');
						if ($('.yeah-count').text() < 1){
							$('#yeah-content').addClass('none');
						}
					} else {
						$('.reply-permalink-post .icon-container.visitor').attr('style', 'display: none;');
						if ($('.reply-permalink-post .yeah-count').text() < 1){
							$('.reply-permalink-post #yeah-content').addClass('none');
						}
					} 
				}

				nahs = Number($('#'+postId).closest('div').find('[nahs]').attr('nahs'));

				yeahs = Number($('#'+postId).closest('div').find('[yeahs]').attr('yeahs'));

				$('#'+postId).closest('div').find('[data-original-title]').attr('title', yeahs + ' ' + (yeahs == 1 ? 'Yeah' : 'Yeahs') + ' / ' + nahs + ' ' + (nahs == 1 ? 'Nah' : 'Nahs'));

				//rebind events here
				bindEvents();
			} else {
				popup('', 'Nah failed.', '<button class="ok-button black-button" type="button" data-event-type="ok">OK</button>');
			}

			$('#' + postId + '.nah').removeAttr("disabled");
		})
	});

	$('.friend-button.cancel').off().on('click', function() {
		name = $(this).attr('data-screen-name');
		popup('Cancel Friend Request', 'Are you sure you really want to cancel your friend request to ' + name + '?', '<button class="ok-button gray-button" type="button" data-event-type="cancel">No</button><button class="cancel-friend black-button" type="button" data-event-type="ok">Yes</button>');
	})

	$('.cancel-friend').off().on('click', function() {
		$('.mask').remove();
		$.post($('.friend-button.cancel').attr('data-action'));
		$.pjax({url: window.location.href, container: '#main-body'});
	})

	$('.reject-button').off().on('click', function() {
		name = $(this).attr('data-screen-name');
		id = $(this).parent().parent().parent().parent().parent().attr('uuid')
		popup('Reject Friend Request', 'Are you sure you want to reject ' + name + '\'s friend request?', '<button class="ok-button gray-button" type="button" data-event-type="cancel">No</button><button class="confirm-reject-button black-button" type="button" id="'+ id +'" data-event-type="ok">Yes</button>');
	});

	$('.become-friends').off().on('click', function() {
		$.post($(this).attr('data-action'));
		$('[uuid="'+id+'"]').remove();
		$('[id="'+id+'"]').remove();
		if ($.trim($('.news-list').html()) == '') {
			$('.news-list').html('<div id="user-page-no-content" class="no-content"><div><p>No friend requests yet.</p></div></div>');
		}
	});

	$('.friend-button.become-friends').off().on('click', function(e) {
		e.preventDefault();
		$.post($(this).attr('data-action')).done(function() {
			$.pjax({url: window.location.href, container: '#main-body'});
		})
	})

	$('.confirm-reject-button').off().on('click', function() {
		id = $(this).attr('id');
		$.post($('.dialog[uuid="'+ id +'"]').attr('data-reject-action'));
		$('[uuid="'+id+'"]').remove();
		$('[id="'+id+'"]').remove();
		$('.active-dialog').remove();

		if ($.trim($('.news-list').html()) == '') {
			$('.news-list').html('<div id="user-page-no-content" class="no-content"><div><p>No friend requests yet.</p></div></div>');
		}
	});

	$('.friend-button.unf.delete').off().on('click', function(e) {
		name = $(this).attr('data-screen-name');
		popup('Unfriend', 'Are you sure you really want to unfriend '+ name +'? Your messages will not be deleted.', '<button class="ok-button gray-button" type="button" data-event-type="cancel">No</button><button class="confirm-unf-button black-button" type="button" data-event-type="ok">Yes</button>');
		bindEvents();
	});

	$('.confirm-unf-button').off().on('click', function(e) {
		e.preventDefault();
		$.post($('.friend-button.unf.delete').attr('data-action')).done(function() {
			$('.active-dialog').remove();
			$.pjax({url: window.location.href, container: '#main-body'});
		})
	})

	$('.nah-added').off().on('click', function(event){
		event.stopPropagation();
		var postId = $(this).attr('id');
		var nahType = $(this).attr('data-track-label');

		//same thing here just for un-nahs lol
		$('#' + postId + '.nah').attr('disabled', '');

		$.post('/unnah.php', {postId:postId, nahType:nahType}, function(data) {
			if (data == 'success') {
				$('#' + postId + '.nah').removeClass('nah-added');
				$('#' + postId + '.nah').find('.nah-button-text').text('Nah...');
				$('#'+postId).closest('div').find('.yeah-count').text(Number($('#'+postId).closest('div').find('.yeah-count').text()) + 1);

				$('#'+postId).closest('div').find('[nahs]').attr('nahs', Number($('#'+postId).closest('div').find('[nahs]').attr('nahs')) - 1);

				nahs = Number($('#'+postId).closest('div').find('[nahs]').attr('nahs'));

				yeahs = Number($('#'+postId).closest('div').find('[yeahs]').attr('yeahs'));

				$('#'+postId).closest('div').find('[data-original-title]').attr('title', yeahs + ' ' + (yeahs == 1 ? 'Yeah' : 'Yeahs') + ' / ' + nahs + ' ' + (nahs == 1 ? 'Nah' : 'Nahs'));

				//rebind events here
				bindEvents();
			} else {
				popup('', 'Un-nah failed.', '<button class="ok-button black-button" type="button" data-event-type="ok">OK</button>');
			}

			$('#' + postId + '.nah').removeAttr("disabled");
		})
	});





	
    $('.yeah').off().on('click', function(event) {
    	event.stopPropagation();

    	var postId = $(this).attr('id');
    	var yeahType = $(this).attr('data-track-label');

    	//changes the yeah button to disabled so no one yeahs twice while its posting the first yeah just like on miiverse holy shish
    	$('#'+postId).attr('disabled', '');

    	$.post('/yeah.php', {postId:postId, yeahType:yeahType}, function(data) {
    		if(data == 'success') {

    			$('#'+postId).addClass('yeah-added');
    			$('#'+postId).find('.yeah-button-text').text('Unyeah');
    			$('#'+postId).closest('div').find('.yeah-count').text(Number($('#'+postId).closest('div').find('.yeah-count').text()) + 1);

    			$('#'+postId).closest('div').find('[yeahs]').attr('yeahs', Number($('#'+postId).closest('div').find('[yeahs]').attr('yeahs')) + 1);



				if (yeahType == 'post') {
					$('#yeah-content').removeClass('none');
					$('.icon-container.visitor').removeAttr("style");
				} else {
					$('.reply-permalink-post #yeah-content').removeClass('none');
					$('.reply-permalink-post .icon-container.visitor').removeAttr("style");
				}

				if ($('#' + postId + '.nah').hasClass('nah-added')) {
					$('#' + postId + '.nah').removeClass('nah-added');
					$('#' + postId + '.nah').find('.nah-button-text').text('Nah...');
					$('#'+postId).closest('div').find('.yeah-count').text(Number($('#'+postId).closest('div').find('.yeah-count').text()) + 1);

					$('#'+postId).closest('div').find('[nahs]').attr('nahs', Number($('#'+postId).closest('div').find('[nahs]').attr('nahs')) - 1);
				}


				nahs = Number($('#'+postId).closest('div').find('[nahs]').attr('nahs'));

				yeahs = Number($('#'+postId).closest('div').find('[yeahs]').attr('yeahs'));

				$('#'+postId).closest('div').find('[data-original-title]').attr('title', yeahs + ' ' + (yeahs == 1 ? 'Yeah' : 'Yeahs') + ' / ' + nahs + ' ' + (nahs == 1 ? 'Nah' : 'Nahs'));

				//rebind events here
				bindEvents();
			} else {
				popup('', 'Yeah failed.', '<button class="ok-button black-button" type="button" data-event-type="ok">OK</button>');
			}

			$('#'+postId).removeAttr("disabled");
		})
    });

    $('.yeah-added').off().on('click', function(event){
    	event.stopPropagation();
    	var postId = $(this).attr('id');
		var yeahType = $(this).attr('data-track-label');
		
		//same thing here just for unyeahs lol
		$('#'+postId).attr('disabled', '');

		$.post('/unyeah.php', {postId:postId, yeahType:yeahType}, function(data){
			if(data=='success'){
				$('#'+postId).removeClass('yeah-added');
				$('#'+postId).find('.yeah-button-text').text('Yeah!');
				$('#'+postId).closest('div').find('.yeah-count').text(Number($('#'+postId).closest('div').find('.yeah-count').text()) - 1);

				$('#'+postId).closest('div').find('[yeahs]').attr('yeahs', Number($('#'+postId).closest('div').find('[yeahs]').attr('yeahs')) - 1);

				if (yeahType == 'post') { 
					$('.icon-container.visitor').attr('style', 'display: none;');
					if ($('.yeah-count').text() < 1){
						$('#yeah-content').addClass('none');
					}
				} else {
					$('.reply-permalink-post .icon-container.visitor').attr('style', 'display: none;');
					if ($('.reply-permalink-post .yeah-count').text() < 1){
						$('.reply-permalink-post #yeah-content').addClass('none');
					}
				} 

				nahs = Number($('#'+postId).closest('div').find('[nahs]').attr('nahs'));

				yeahs = Number($('#'+postId).closest('div').find('[yeahs]').attr('yeahs'));

				$('#'+postId).closest('div').find('[data-original-title]').attr('title', yeahs + ' ' + (yeahs == 1 ? 'Yeah' : 'Yeahs') + ' / ' + nahs + ' ' + (nahs == 1 ? 'Nah' : 'Nahs'));

				//rebind events here
				bindEvents();
			} else {
				alert('unyeah failed');
			}

			$('#'+postId).removeAttr("disabled");
		})
	});

	$('#global-my-menu').find('a').on('click', function() {
		$('#global-my-menu').addClass('none');
	})

	$(".js-open-global-my-menu").off().click(function() {
		$('#global-my-menu').not($("#global-my-menu").toggleClass('none')).addClass('none');
	});

	$('.user-dropdown-button').off().on('click', function() {
		$('#user-dropdown-menu').not($("#user-dropdown-menu").toggleClass('none')).addClass('none');
	})

	$('.textarea').keyup(function() {
		var text_length = $(this).val().length;
		var text_remaining = 2000 - text_length;
		$(this).parent().parent().find('.textarea-feedback').html('<font color="#646464" style="font-size: 13px; padding: 0 3px 0 7px;">'+text_remaining+'</font> Characters Remaining');
	});

	$('.received-request-button').off().on('click', function(e) {
		e.stopImmediatePropagation();

		id = $(this).parent().parent().attr('id');

		$('.dialog[uuid="' + id + '"]').removeClass('none');
	});

	$('.friend-button.create').off().on('click', function() {
		$('.dialog[data-modal-types="post-friend-request"]').removeClass('none');
	})

	$('form#friend_request').off().on('submit', function(e) {
		e.preventDefault();
		$.ajax({
            url : $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (data) {
                $('.dialog[data-modal-types="post-friend-request"]').remove();
                $.pjax({url: window.location.href, container: '#main-body'});
            }
        });
	});

	$('.user-menu-block').off().on('click', function(e) {
		e.preventDefault();
		popup('Block user', 'Are you sure you want to block this user?', '<button class="ok-button gray-button" type="button" data-event-type="cancel">No</button><button data-action="'+ $(this).attr('href') +'" class="block-user-confirm black-button" type="button" data-event-type="ok">Yes</button>');
	});

	$('.block-user-confirm').off().on('click', function(e) {
		$.post($(this).attr('data-action'), function(data) {
			if(data == 'success'){
				$('.active-dialog').remove();
				$.pjax({url: window.location.href, container: '#main-body'});
			} else {
				$('.active-dialog').remove();
				popup('Error', data, '<button class="ok-button black-button" type="button" data-event-type="cancel">OK</button>');
			}
		});
	});

	$('.unblock-button').off().on('click', function(e) {
		e.stopImmediatePropagation();
		unblock_url = $(this).attr('data-action');
		$.post($(this).attr('data-action'), function(data) {
			if (data == 'success') {
				$('.unblock-button[data-action="'+ unblock_url +'"]').parent().parent().remove();
				if ($('#friend-list-content').html() == '') {
					$('#friend-list-content').html('<div id="user-page-no-content" class="no-content"><div><p>No blocked users.</p></div></div>');
				}
			}
		});
	});

	$('.screenshot-container.still-image img').off().on('click', function(e) {
		e.stopImmediatePropagation();
		src = $(this).attr('src');
		$('body').append('<div class="full-image mask"><img class="full-image-content animation" src="'+ src +'"></img></div>');
		bindEvents();
	});

	$('audio').off().on('click', function(e) {
		e.stopImmediatePropagation();
	});

	$('.full-image-content').off().on('webkitAnimationEnd oanimationend msAnimationEnd animationend', function() {
		$(this).removeClass('animation');
	});

	$(document).off().on('click',function (e) {
		if ((!$('.open-global-my-menu').is(e.target) && $('.open-global-my-menu').has(e.target).length === 0) && (!$('#global-my-menu').is(e.target) && $('#global-my-menu').has(e.target).length === 0)){
			$('#global-my-menu').addClass('none');
		}
		if ((!$('.user-dropdown-button').is(e.target) && $('.user-dropdown-button').has(e.target).length === 0) && (!$('#user-dropdown-menu').is(e.target) && $('#global-my-menu').has(e.target).length === 0)){
			$('#user-dropdown-menu').addClass('none');
		}
		if ((!$('.full-image-content').is(e.target) && $('.full-image-content').has(e.target).length === 0) && (!$('#global-my-menu').is(e.target) && $('#global-my-menu').has(e.target).length === 0)) {
			$('.full-image-content').addClass('hide');
			$('.full-image-content.hide').on('webkitAnimationEnd oanimationend msAnimationEnd animationend', function() {
				$('.full-image').remove();
			});
		}
	});

	$('.js-open-truncated-text-button').off().on('click', function(){
		$(this).addClass('none');
		$('.js-truncated-text').addClass('none');
		$('.js-full-text').removeClass('none');
		bindEvents();
	})

	$('.favorite-button').off().on('click', function(){
		var titleId = $(this).attr('data-title-id');
		if ($('.favorite-button').hasClass('checked')){

			$.post('/favorite.php', {titleId:titleId, favType: "removeFav"}, function(data) {
				if(data == 'success'){
					$('.favorite-button').removeClass('checked');
				}
			})
		} else {
			$.post('/favorite.php', {titleId:titleId, favType: "addFav"}, function(data) {
				if(data == 'success'){
					$('.favorite-button').addClass('checked');
				}
			})
		}
	});

	$('input[name="face-type"]').click(function() {
		if ($('input[name="face-type"][value="2"]').is(':checked')) {
			$('.nnid-face').removeClass('none');
			$('.custom-face').addClass('none');
		} else {
			$('.custom-face').removeClass('none');
			$('.nnid-face').addClass('none');
		}
	});

	$('.feeling-button').click(function() {
		$('.feeling-button').removeClass('checked');
		$(this).addClass('checked');
	})

	$('.follow-button').off().on('click', function(event) {
		event.stopPropagation();
		var userId = $(this).attr('data-user-id');
		$.post('/follow.php', {userId:userId, followType: "follow"}, function(data) {
			if(data == 'success'){
				$('.user-sidebar').find('[data-user-id="' + userId + '"]').addClass('unfollow-button').removeClass('follow-button');
				$('.list').find('[data-user-id="' + userId + '"]').addClass('none').next('.follow-done-button').removeClass('none').removeAttr("disabled");
				bindEvents();
			}
		})
	});

	$('.unfollow-button').off().on('click', function(){
		var userId = $(this).attr('data-user-id');
		$.post('/follow.php', {userId:userId, followType: "unfollow"}, function(data) {
			if(data == 'success'){
				$('.unfollow-button').addClass('follow-button').removeClass('unfollow-button');
				bindEvents();
			}
		})
	});

	$('#profile-post').off().on('click', function(){
		$.post('/settings/profile_post.unset.json');
		$(this).remove();
	});

	$('.olv-modal-close-button').off().on('click', function(){
		$('.mask').addClass('none');
	});

	$('#post-form').find('textarea[name="text_data"]').on('input', function(){
		if ($(this).val() == ""){
			//$('#post-form').find('.post-button').addClass('disabled').attr('disabled', '');
		} else {
			$('#post-form').find('.post-button').removeClass('disabled').removeAttr('disabled');
		}
	});

	$('#edit-form').find('textarea[name="body"]').on('input', function() {
		if ($(this).val().trim() == ""){
			$('#edit-form').find('.post-button').addClass('disabled').attr('disabled', '');
		} else {
			$('#edit-form').find('.post-button').removeClass('disabled').removeAttr('disabled');
		}
	});

	$('#edit-form').off().on('submit', function(e) {
		e.preventDefault();
		$(this).find('.post-button').addClass('disabled').attr('disabled', '');
		var formData = new FormData(this);
		$.ajax({url: $(this).attr('action'), type: 'POST', data: formData, success:function(data) {
			if (data == 'success') {
				$.pjax({url: window.location.href, container: '#main-body'});
			}
		}, contentType: false, processData: false});
	});

	$('#post-form').off().on('submit', function(e){

		e.preventDefault();
		$(this).find('.post-button').addClass('disabled').attr('disabled', '');
		if ($(this).find('.file-button').val() || $(this).find('input[name="pasted-image"]').val()) {
			var formData = new FormData(this);
		} else {
			var formData = new FormData();
			formData.append('csrfToken', $(this).find('input[name="csrfToken"]').val());
			formData.append('title_id', $(this).find('input[name="title_id"]').val());
			formData.append('text_data', $(this).find('textarea[name="text_data"]').val());
			formData.append('feeling_id', $(this).find('input[name="feeling_id"]:checked').val());
		}
		var code;

		$.ajax({url: $(this).attr('action'), type: 'POST', data: formData,

		statusCode: {
			201: function() {
				var code = 201;
			}
		},

		success:function(data) {

			if ($('#post-form').attr('action').substr(-7) == 'replies') {
				$('.reply-list').append(data);
			} else if ($('.post-list').length) {
				$('.post-list').prepend(data);
			} else {
				$('.list.messages').prepend(data);
			}

			if (code !== 201) {
				$('.no-reply-content').remove();
				$('.no-content').remove();
				$('.post').fadeIn();
				$('.feeling-button').removeClass('checked');
				$('.feeling-button-normal').addClass('checked');
				$('#post-form').each(function(){this.reset();});

				//remove the textarea and add it back because paste.js is gay
				$('#post-form').find('.textarea-text').removeClass('pastable').removeClass('pastable-focus');
				textarea = $('#post-form').find('.textarea-text').parent().html();
				$('#post-form').find('.textarea-text').remove();
				$('#post-form').find('.textarea-container').html(textarea);

				$('.file-button-container').replaceWith('<label class="file-button-container"><span class="input-label">File upload <span>PNG, JPG, BMP, GIF, MP3, OGG, and WEBM are allowed. Max file size: 8M.</span></span><input type="file" class="file-button" name="image" accept="image/*,.mp3,.ogg,.webm"></label>');
			}

			$("#post-form").find('.post-button').removeClass('disabled').removeAttr('disabled');
			bindEvents();
		}, contentType: false, processData: false});
	});

	$('.setting-form').off().on('submit', function(e){
		e.preventDefault();
		$('.apply-button').addClass('disabled').prop('disabled', '');
		var formData = new FormData(this);
		$.ajax({url: $(this).attr('action'), type: 'POST', data: formData, success:function(data){
			popup('', data, '<button class="ok-button black-button" type="button" data-event-type="ok">OK</button>');

			$('.apply-button').removeClass('disabled').removeAttr('disabled', '');
		}, contentType: false, processData: false})
	});

	$('.clear-notifs').off().on('click', function() {
		$.post('/notifications/clear');
		$('.news-list').empty().append('<div id="user-page-no-content" class="no-content"><div><p>No notifications.</p></div></div>');
	});

	$('.ok-button').off().on('click', function(){
		$('.active-dialog').remove();
	});

	$('.rm-post-button').off().on('click', function() {
		if ($(this).attr('data-action').substr(0, 6) == '/posts') {
			popup('Delete post', 'Really delete this post?', '<button class="ok-button gray-button" type="button" data-event-type="cancel">Cancel</button><button data-action="'+ $(this).attr('data-action') +'" class="delete-post-button black-button" type="button" data-event-type="ok">OK</button>');
		} else if ($(this).attr('data-action').substr(0, 8) == '/replies') {
			popup('Delete reply', 'Really delete this reply?', '<button class="ok-button gray-button" type="button" data-event-type="cancel">Cancel</button><button data-action="'+ $(this).attr('data-action') +'" class="delete-post-button black-button" type="button" data-event-type="ok">OK</button>');
		} else {
			popup('Delete message', 'Really delete this message?', '<button class="ok-button gray-button" type="button" data-event-type="cancel">Cancel</button><button data-action="'+ $(this).attr('data-action') +'" class="delete-post-button black-button" type="button" data-event-type="ok">OK</button>');
		}
    });

    $('.delete-post-button').off().on('click', function() {
    	deletePostId = $(this).attr('data-action');
    	$.ajax({url: $(this).attr('data-action'), type: 'POST', success:function(data) {
    		$('.active-dialog').remove();
    		if (deletePostId.substr(0, 6) == '/posts' || deletePostId.substr(0, 8) == '/replies') {
    			$.pjax({url: window.location.href, container: '#main-body'});
    		} else {
    			$('.rm-post-button[data-action="'+ deletePostId +'"]').parent().parent().remove();
    		}
    	}});
    });

    $('.profile-post-button').off().on('click', function() {
    	if ($(this).attr('data-action').substr(0, 6) == '/posts') {
    		popup('Favorite post', 'Set this as your favorite post?', '<button class="ok-button gray-button" type="button" data-event-type="cancel">Cancel</button><button data-action="'+ $(this).attr('data-action') +'" class="favorite-post-button black-button" type="button" data-event-type="ok">OK</button>');
    	} else {
    		popup('Unfavorite post', 'Really unfavorite this post?', '<button class="ok-button gray-button" type="button" data-event-type="cancel">Cancel</button><button data-action="'+ $(this).attr('data-action') +'" class="favorite-post-button black-button" type="button" data-event-type="ok">OK</button>');
    	}
    });

    $('.favorite-post-button').off().on('click', function() {
    	favoritePostId = $(this).attr('data-action');
    	$.ajax({url: $(this).attr('data-action'), type: 'POST', success:function(data) {
    		$('.active-dialog').remove();
    		$.pjax({url: window.location.href, container: '#main-body'});
    	}});
    });

    $('.edit-post-button').off().on('click', function() {
    	$('#the-post').toggleClass('none');
    	$('#post-edit').toggleClass('none');
    });

    $('#post-edit').find('.cancel-button').off().on('click', function () {
    	$('#the-post').toggleClass('none');
    	$('#post-edit').toggleClass('none');
    })

    $('.msg-update').off().on('click', function() {
    	$.pjax({url: window.location.href, container: '#main-body'});
    });

	$('.community-top-sidebar .search').on('submit', function(e){
		if ($(this).find('input[type="text"]').val().length < 2){
			e.preventDefault();
		}
	});

	$('.headline .search').on('submit', function(e){
		if ($(this).find('input[type="text"]').val().length < 1){
			e.preventDefault();
		}
	});

	$('.mention').blur(function () {
    	textcomplete.hide(1);
    });

	$('.textarea-text').pastableTextarea();

	$('.textarea-text').off('pasteImage').on('pasteImage', function (ev, data) {
		$('.file-button-container').replaceWith('<label class="file-button-container">\
            <span class="input-label">Image: <span>'+ data.width +' x '+ data.height +'.</span></span>\
            <img class="pasted-image-preview" src="'+ data.dataURL +'">\
            <input name="pasted-image" type="hidden" value="'+ data.dataURL +'">\
        </label>');
	}).on('pasteImageError', function(ev, data){
		alert('Oops: ' + data.message);
	});

	//checks if loadOnScroll is defined. So this code will only run on pages the need it
	if ((typeof loadOnScroll !== 'undefined')) {

		$(window).scroll(function() {
			//checks if you're at the bottom of the page and if you are it loads more posts
			if ($(window).scrollTop() + window.innerHeight >= $('[data-next-page-url]').height()) {
				if (loading == 0 && atBottom == false) {
					$("[data-next-page-url]").append('<div class="post-list-loading"><img src="/assets/img/loading-image-green.gif" alt=""></div>');
					loading = 1;
					$.get($('[data-next-page-url]').attr('data-next-page-url'), function(data) {
						if(data == ''){
							atBottom = true;
							bindEvents();
						}
						$("[data-next-page-url]").append(data);
						offset++;
						$('[data-next-page-url]').attr('data-next-page-url', $('[data-next-page-url]').attr('data-next-page-url').replace(/(offset=).*?(&)/,"offset=" + offset + "&"))
						loading = 0;
						$(".post-list-loading").remove();
						bindEvents();
					})
				}
			}
		});
	}

	$(document).on('pjax:end', function() {
		getNotifs();
		bindEvents();
		reloadYeahTooltip();
		reloadYeahTooltip();
	});

	$(document).pjax('a', '#main-body', replace = true);
}

$(document).ready(function() {
	window.tip = tippy('.empathy', {
		animation: 'shift-away',
		arrow: true,
		dynamicTitle: true
	});
	window.emojiTip = tippy('.emoji', {
		delay: 100,
		animation: 'fade',
		arrow: true,
		dynamicTitle: true
	});
	bindEvents();
    getNotifs();
});