<?php
require_once "PHPTelebot.php";
require_once "token.php";

$bot = new PHPTelebot($apiToken);

$bot->cmd('/start', function() {
    setting();
    return 0;
});

$bot->cmd('/search', function() {
    search();
    return 0;
});

$bot->cmd('/next', function() {
    stop();
    search();
    return 0;
});

$bot->cmd('/stop', function() {
    stop();
    return 0;
});

$bot->cmd('/help', function() {
    return 0;
});

$bot->cmd('/setting', function() {
    setting();
    return 0;
});

function setting(){
    $message = Bot::message();
    $id = $message['from']['id'];
    $db = new SQLite3("users.db");
    @$db->query("insert into users values ({$id}, 0, 0)");
    Bot::sendMessage("Set your gender");
    return 0;
}

function stop(){
    $message = Bot::message();  
    $db = new SQLite3("users.db");
    
    $id = $message['from']['id'];
    $companion = $db->querySingle("select companion from users where id = {$id}");
    
    if($companion > 0){
        $data['chat_id'] = $companion;
        Bot::sendMessage("You stopped the dialog 🙄 \nType /search to find a new partner");
        Bot::sendMessage("Your partner has stopped the dialog 😞 \nType /search to find a new partner", $data);
    } else {
        Bot::sendMessage("You have no partner 🤔 \nType /search to find a new partner");
    }
    
    $db->query("update users set status = 0 where id = {$id}");
    $db->query("update users set status = 0 where id = {$companion}");
    $db->query("update users set companion = 0 where id = {$id}");
    $db->query("update users set companion = 0 where id = {$companion}");
    
    return 0;
}

function search(){
    $message = Bot::message();
    $db = new SQLite3("users.db");
    
    Bot::sendMessage("Looking for a partner...");
    
    $id = $message['from']['id'];
    $db->query("update users set status = 1 where id = {$id}");
    
    $results = $db->querySingle("select id from users where status = 1 and id != {$id} limit 1");
    $companion = $results;
    
    if($companion > 0){
        $data['chat_id'] = $companion;
        $db->query("update users set status = 2 where id = {$id}");
        $db->query("update users set status = 2 where id = {$companion}");
        $db->query("update users set companion = {$companion} where id = {$id}");
        $db->query("update users set companion = {$id} where id = {$companion}");
        Bot::sendMessage("Partner found 🐵 \n/next — find a new partner \n/stop — stop this dialog");
        Bot::sendMessage("Partner found 🐵 \n/next — find a new partner \n/stop — stop this dialog", $data);
    }
    return 0;
}

$bot->cmd('*', function($text){
    $message = Bot::message();
    $id = $message['from']['id'];  
    $db = new SQLite3("users.db");
    $companion = $db->querySingle("select companion from users where id = {$id} and status = 2 limit 1");
    $data['chat_id'] = $companion;
    
    if ($companion > 0){       
        if (is_string($text)){
            Bot::sendMessage($text, $data);
        }
    }
    return 0;
});

$bot->run();
