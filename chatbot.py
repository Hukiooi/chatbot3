import telebot
from telebot import types
from database import *
import os

#token from @BotFather
bot = telebot.TeleBot("")

class User:  
    def __init__(self, user_id):
        self.user_id = user_id
        self.name = None
        self.age = None
        self.sex = None
        self.change = None

#complete
@bot.message_handler(commands=['start'])
def start(message):
    if check_user(user_id=message.from_user.id)[0]:
        me = bot.get_me()
        return
    else:
        bot.send_message(message.from_user.id, "🥀Zzzzz...")
        register(message)

#complete
def register(message):
    try:
        user = User(message.from_user.id)
        user.name = message.from_user.id
        user.age = 18
        user.sex = "Female"
        user.change = "Male and Female"
        reg_db(user_id=user.user_id,name=user.name,old=user.age,gender=user.sex,change=user.change)
    except:
        pass
    start(message)

#complete
@bot.message_handler(commands=['help'])
def help(message):
    pass

#complete
@bot.message_handler(commands=['setting'])
def setting(message):
    pass

#complete
@bot.message_handler(commands=['next'])
def next(message):
    pass

@bot.message_handler(commands=['stop'])
def stop(message):
    pass

#complete
@bot.message_handler(commands=['search'])
def search(message):
    is_open = check_open(first_id = message.from_user.id)
    if is_open[0][0]:
        #bot.register_next_step_handler(message, chat)
        bot.register_next_step_handler(message, chat)
    else:
        bot.send_message(message.from_user.id, "Looking for a partner...")
        select = select_free()
        success = False
        if not select:
            add_user(first_id = message.from_user.id)
        else:
            for sel in select:
                if check_status(first_id = message.from_user.id, second_id = sel[0] ) or message.from_user.id == sel[0]:
                    #print(message.from_user.id, "joined")
                    continue
                else:
                    #print(sel[0])
                    #print(message.from_user.id)
                    add_second_user(first_id = sel[0], second_id = message.from_user.id)
                    user_info = get_info(user_id = sel[0])
                    bot.send_message(message.from_user.id, "Partner found. You are in dialog now.")
                    user_info = get_info(user_id = message.from_user.id)
                    bot.send_message(sel[0], "Partner found. You are in dialog now.")
                    success = True
                    break
        if not success:
            time.sleep(2)
            bot.register_next_step_handler(message, search)
        else:
            bot.register_next_step_handler(message, chat)

#complete
def chat(message):
    if message.text == "/stop" or message.text == "/next":
        companion = check_companion(first_id = message.from_user.id)
        if message.text == "/next":
            bot.send_message(message.from_user.id, "You stopped the dialog. Searching for a new partner...")
        else:
            bot.send_message(message.from_user.id, "You stopped the dialog 🙄 Type /search to find a new partner")
        bot.send_message(companion, "Your partner has stopped the dialog 😔 Type /search to find a new partner")
        close_chat(first_id = message.from_user.id)
        if message.text == "/next":
            search(message)
        else:
            start(message)
        return
        
    elif not check_open(first_id=message.from_user.id)[0][0]:
        start(message)
        return
    
    companion = check_companion(first_id = message.from_user.id)
    if message.sticker:
        bot.send_sticker(companion, message.sticker.file_id)
    elif message.photo:
        file_id = None
        for item in message.photo:
            file_id = item.file_id
        bot.send_photo(companion, file_id, caption = message.caption)
    elif message.video:
        bot.send_video(companion, message.video.file_id, caption = message.caption)
    elif message.audio :
        bot.send.audio(companion, message.audio.file_id, caption = message.caption)
    elif message.voice:
        bot.send.voice(companion, message.voice.file_id)
    elif message.animation:
        bot.send_animation(companion, message.animation.file_id)
    elif message.text:
        if message.text != "/start" and message.text != "/stop" :
            if message.reply_to_message is None:
                bot.send_message(companion, message.text)
            elif message.from_user.id != message.reply_to_message.message_id:
                bot.send_message(companion, message.text, message.reply_to_message.message_id - 1)
            else:
                bot.send_message(message.chat.id, "Rejected")
        if message.text == "/search":
            bot.send_message(message.from_user.id, "You are in dialog right now 🤔")
    bot.register_next_step_handler(message, chat)


if __name__ == "__main__":
    os.system('clear')
    bot.infinity_polling()
