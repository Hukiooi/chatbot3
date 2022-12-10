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
    stop(1);
    search(1);
    return 0;
});

$bot->cmd('/stop', function() {
    stop();
    return 0;
});

$bot->cmd('/help', function() {
    $self['parse_mode'] = 'markdown';
    $self['disable_web_page_preview'] = true;
    Bot::sendMessage("_This bot is for anonymous chatting with strangers in Telegram. Bot can send text, stickers, voice. Do not use this bot for criminals!_", $self);
    Bot::sendMessage("_Source code: (https://github.com/Hukiooi/chatbot3)_", $self);
    
    return 0;
});

$bot->cmd('/setting', function() {
    setting();
    return 0;
});

function is_blocked(){
    $message = Bot::message();
    $id = $message['from']['id'];
    $db = new SQLite3("users.db");
    $blocked = $db->querySingle("select blocked from users where id = {$id}");
    if($blocked > 0){
        return true;
    } else {
        return false;
    }
}

function blockedMessage(){
    echo "Blocked or banned.\n";
    return 0;
}

function setting(){
    if(is_blocked()){
        blockedMessage();
    } else {
        $message = Bot::message();
        $id = $message['from']['id'];
        $db = new SQLite3("users.db");
        $checkid = $db->querySingle("select id from users where id = {$id}");
        
        if(empty($checkid) or $checkid < 1){
            $db->query("insert into users values ({$id}, 0, 0, 0)");
        }
        $self['parse_mode'] = 'markdown';
        $self['disable_web_page_preview'] = true;
        
        Bot::sendMessage("_No need set your gender 😉_", $self);
    }
    return 0;
}

function stop($params = 0){
    if(is_blocked()){
        blockedMessage();
    } else {
        $message = Bot::message();  
        $db = new SQLite3("users.db");
        
        $id = $message['from']['id'];
        $companion = $db->querySingle("select companion from users where id = {$id}");
        
        if($companion > 0){
            $data['chat_id'] = $companion;
            $db->query("update users set status = 0, companion = 0 where id = {$id}");
            $db->query("update users set status = 0, companion = 0  where id = {$companion}");
            
            if ($params > 0){
                $self['parse_mode'] = 'markdown';
                Bot::sendMessage("_You stopped the dialog. Searching for a new partner..._", $self);
            } else {
                Bot::sendMessage("You stopped the dialog 🙄 \nType /search to find a new partner");
            }
            Bot::sendMessage("Your partner has stopped the dialog 😞 \nType /search to find a new partner", $data);
        }
        if($params < 1 and $companion < 1){
            $db->query("update users set status = 0, companion = 0 where id = {$id}");
            Bot::sendMessage("You have no partner 🤔 \nType /search to find a new partner");
        }
    }
    return 0;
}

function search($params = 0){
    if(is_blocked()){
        blockedMessage();
    } else {
        $message = Bot::message();
        $db = new SQLite3("users.db");
        
        $id = $message['from']['id'];
        $check_companion = $db->querySingle("select companion from users where status = 2 and id = {$id} limit 1");
        if($check_companion < 1){
            $db->query("update users set status = 1 where id = {$id}");
            $results = $db->querySingle("select id from users where status = 1 and id != {$id} limit 1");
            $companion = $results;
            
            if($companion > 0){        
                $data['chat_id'] = $companion;
                $db->query("update users set status = 2, companion = {$companion} where id = {$id}");
                $db->query("update users set status = 2, companion = {$id} where id = {$companion}");
                
                Bot::sendMessage("Partner found 🐵 \n/next — find a new partner \n/stop — stop this dialog");
                Bot::sendMessage("Partner found 🐵 \n/next — find a new partner \n/stop — stop this dialog", $data);
            }
            if($params < 1 and $companion < 1){
                $self['parse_mode'] = 'markdown';
                Bot::sendMessage("Looking for a partner...", $self);
            }
        } else {
            Bot::sendMessage("You are in the dialog right now 🤔 \n/next — find a new partner \n/stop — stop this dialog");
        }
    }
    return 0;
}

$bot->cmd('*', function($text){
    if(is_blocked()){
        blockedMessage();
    } else {
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
    }
    return 0;
});

$bot->on('*', function($message){
    if(is_blocked()){
        blockedMessage();
    } else {
        $message = Bot::message();
        $id = $message['from']['id'];  
        $db = new SQLite3("users.db");
        $companion = $db->querySingle("select companion from users where id = {$id} and status = 2 limit 1");
        $data['chat_id'] = $companion;
        
        if ($companion > 0){
            if(@$message['sticker']){
                @$sticker = $message['sticker']['file_id'];
                Bot::sendSticker($sticker, $data);
            }
            if(@$message['voice']){
                $voice = $message['voice']['file_id'];
                Bot::sendVoice($voice, $data);
            }
            
        }
    }
    return 0;
});

$bot->run();
