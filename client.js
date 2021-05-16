var pollServer = function() {
    $.get('http://ecs.fullerton.edu/~cs431s41/chat/chat.php', function(result) {
        
        if(!result.success) {
            console.log("Error polling server for new messages!");
            return;
        }
        
        $.each(result.messages, function(idx) {
            
            var chatBubble;
	const root = document.documentElement;
            
            if(this.sent_by == 'self') {
                chatBubble = $('<div class="row bubble-sent pull-right">' + 
                               this.message + 
                               '</div><div class="clearfix"></div>');
            } else {
                chatBubble = $('<div class="row bubble-recv">' + 
                               this.message + 
                               '</div><div class="clearfix"></div>');
            }
            
            $('#chatPanel').append(chatBubble);
        });
        
        setTimeout(pollServer, 5000);
    });
}

$(document).on('ready', function() {
    pollServer();
    
    $('button').click(function() {
        $(this).toggleClass('active');
    });
});

$('#sendMessageBtn').on('click', function(event) {
    event.preventDefault();
    
    var message = $('#chatMessage').val();
    var chat_username = $('#chat_username').val();
    var color = $('#color').val();
    
    $.post('chat.php', {
        'message' : message,
        'chat_username' : chat_username,
        'color' : color
    }, function(result) {
        
        $('#sendMessageBtn').toggleClass('active');
          
        if(!result.success) {
            alert("There was an error sending your message");
        } else {
            console.log("Message sent!");
            $('#chatMessage').val('');
        }
    });
    
});
