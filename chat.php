<!DOCTYPE html>
<html>
    <head>
        <title>AJAX Chat</title>
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css">
        <link rel="stylesheet" href="style/stylesheet.css">
    </head>
    <body>
    <h1 style="text-align:center">AJAX Chat</h1>
    <div class="container">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">Let's Chat</h2>
            </div>
            <div class="panel-body" id="chatPanel">
            </div>
            <div class="panel-footer">
                <div class="input-group">
                    <input type="text" class="form-control" id="chatMessage" placeholder="Send a message here..."/>
                    <span class="input-group-btn">
                        <button id="sendMessageBtn" class="btn btn-primary has-spinner" type="button">
                            <span class="spinner"><i class="icon-spin icon-refresh"></i></span>
                            Send
                        </button>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <script src="//code.jquery.com/jquery-2.2.3.min.js"></script>
    <script src="client.js"></script>
    </body>
</html>

<?php
session_start();
ob_start();
header("Content-type: application/json");
date_default_timezone_set('UTC');
//connect to database
$db = mysqli_connect('mariadb', 'cs431s41', 'EeChe9sh', 'cs431s41');
if (mysqli_connect_errno()) {
   echo '<p>Error: Could not connect to database.<br/>
   Please try again later.</p>';
   exit;
}
//helper funtion to replace get_results() if without mysqlnd 
function get_result( $Statement ) {
    $RESULT = array();
    $Statement->store_result();
    for ( $i = 0; $i < $Statement->num_rows; $i++ ) {
        $Metadata = $Statement->result_metadata();
        $PARAMS = array();
        while ( $Field = $Metadata->fetch_field() ) {
            $PARAMS[] = &$RESULT[ $i ][ $Field->name ];
        }
        call_user_func_array( array( $Statement, 'bind_result' ), $PARAMS );
        $Statement->fetch();
    }
    return $RESULT;
}
try { 
    $currentTime = time();
    $session_id = session_id();    
    $lastPoll = isset($_SESSION['last_poll']) ? $_SESSION['last_poll'] : $currentTime;    
    $action = isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') ? 'send' : 'poll';
    switch($action) {
        case 'poll':
           $query = "SELECT * FROM chatlog WHERE date_created >= ".$lastPoll;
           $stmt = $db->prepare($query);
           $stmt->execute();
           $stmt->bind_result($id, $message, $session_id, $date_created, $chat_usrname, $color);
           $result = get_result( $stmt);
           $newChats = [];
           while($chat = array_shift($result)) {
               
               if($session_id == $chat['sent_by']) {
                  $chat['sent_by'] = 'self';
               } else {
                  $chat['sent_by'] = 'other';
               }
             
               $newChats[] = $chat;
            }
           $_SESSION['last_poll'] = $currentTime;

           print json_encode([
                'success' => true,
		'messages' => $newChats
           ]);
           exit;
        case 'send':
            $chat_username = isset($_POST['chat_usrname']) ? $_POST['chat_username'] : ' ';
            $chat_username = strip_tags($chat_username);
            $message = isset($_POST['message']) ? $_POST['message'] : '';            
            $message = strip_tags($message);
            $color = isset($_POST['color']) ? $_POST['color'] : '';
            $query = "INSERT INTO chatlog (message, sent_by, date_created, chat_username, color) VALUES(?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param('ssi', $message, $session_id, $currentTime, $chat_username, $color); 
            $stmt->execute(); 
            print json_encode(['success' => true]);
            exit;
    }
} catch(Exception $e) {
    print json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
