#!/usr/bin/python
#-*- coding: utf-8 -*-
import sys
import os
import json
import re
import chardet
import time
import datetime
import logging
import datetime

#def getDbConfigByJsonFile
def getDbConfigByJsonFile():
    with open('config.json', 'r') as f:
        config = json.load(f)

    return config

# ereg_match
def ereg_match(regExpress, str, isIgnorecase=False):
    if isIgnorecase == True :
        regex = re.compile(regExpress, re.I)
    else:
        regex = re.compile(regExpress)

    mo = regex.search(str)
    if mo == None:
        return False
    else:
        return True

# ereg_replace
def ereg_replace(regExpress, newExpress, str):
    return re.sub(regExpress, newExpress, str)

# check_blank
def check_blank(str):
    if str == None:
        return True
    elif str == 'None':
        return True
    elif str == '0000-00-00':
        return True
    elif str == '00:00:00':
        return True
    elif str == '0000-00-00 00:00:00':
        return True
    elif str == '':
        return True

    return False

# explode
def explode(keyword, str):
    result = str.split(keyword)
    return result

# getReplaceStr
def getReplaceStr(str, condition) :
    if check_blank(condition) :
        return str

    results = explode(",", condition);
    for result in results:
        temp = explode("=", result)
        str = str.replace(temp[0], temp[1])

    return str

# getFullFileName
def getFullFileName(str):
    return os.path.basename(str)

# getFileName
def getFileName(str):
    return os.path.splitext(getFullFileName(str))[0]

# getFileType
def getFileType(str):
    return os.path.splitext(getFullFileName(str))[1]

# getTimestamp
def getTimestamp(str=''):
    if str == '':
        return int(time.time())
    elif str == 'yesterday'    or str == '어제':
        yesterday = datetime.date.today() - datetime.timedelta(1)
        return int(yesterday.strftime("%s"))
    elif str == 'today'    or str == '오늘':
        today = datetime.date.today()
        return int(today.strftime("%s"))

    temp = explode(' ', str)

    if ereg_match('-', temp[0]) :
        d = explode("-", temp[0])
    else:
        tmp = temp[0]
        d = []
        d.append(tmp[:4])
        d.append(tmp[4:6])
        d.append(tmp[6:8])

    if len(temp) == 2:
        t = explode(':', temp[1])
        for i in range(len(t), 3):
            t.append('00')
    else:
        t = ['00', '00', '00']

    dt = datetime.datetime(int(d[0]), int(d[1]), int(d[2]), int(t[0]), int(t[1]), int(t[2]))

    return int(time.mktime(dt.timetuple()))


# jsonencode
def jsonencode(obj):
    #print("Encode Before")
    #print(type(obj))
    #print(obj)

    result = json.dumps(obj, ensure_ascii=False)
    #result = json.dumps(obj, ensure_ascii=False).encode("utf-8").decode("utf-8")

    #print("Encode After")
    #print(type(result))
    #print(result)

    return result

# jsondecode
def jsondecode(s):
    # print("Decode Before")
    # print("%s %s" % (type(s), s))
    # print(str)
    # print(chardet.detect(str))

    if type(s) is bytes:
        s = json.loads(s.decode("utf-8"), encoding="utf-8")
        return s
    elif isinstance(s, str):
        try:
            result = json.loads(s)
            return result
        except:
            if ereg_match('^\{', s) and ereg_match('\}$', s):
                try:
                    result = json.loads(s, encoding="utf-8")
                except Exception as e:
                    #print("[이전] %s" % s)
                    s = s.strip("'<>() ").replace('\'', '\"').replace('\r\n', '<br>')
                    s = s.replace('","', '\',\'').replace('":"', '\':\'')
                    s = s.replace('":{"', '\':{\'').replace('"},"', '\'},\'')
                    s = s.replace('":', '\':').replace(',"', ',\'')
                    s = s.replace('{"', '{\'').replace('"}', '\'}')
                    s = s.replace('"', '\\\"')
                    #print("[중간] %s" % s)
                    s = s.replace('\',\'', '","').replace('\':\'', '":"')
                    s = s.replace('\'{\'', '"{"').replace('\'}\'', '"}"')
                    s = s.replace('\':', '":').replace(',\'', ',"')
                    s = s.replace('{\'', '{"').replace('\'}', '"}')
                    #print("[이후] %s" % s)
                    result = json.loads(s)

                return result
            else:
                return s
    else:
        return s


# specialchars(text):
def specialchars(text):
    return (
        text.replace("&", "&amp;").
        replace('"', "&quot;").
        replace("'", '&#039;').
        replace("\xa0",'&nbsp;')
    )
    

# htmlspecialchars
def htmlspecialchars(text):
    return (
        text.replace("&", "&amp;").
        replace('"', "&quot;").
        replace("'", '&#039;').
        replace("\xa0",'&nbsp;').
        replace("<", "&lt;").
        replace(">", "&gt;")
    )

# htmlspecialchars
def htmlspecialchars_decode(text):
    return (
        text.replace("&amp;", "&").
        replace("&#039;", "'" ).
        replace("&quot;", '"').
        replace("&lt;", "<").
        replace("&gt;", ">")
    )
    
# autolink
def autolink(text, target=''):
    if target == '':
        return re.sub(r'\b((?:https?:\/\/)?(?:www\.)?(?:[^\s.]+\.)+\w{2,4})\b', r'<a href="\1">\1</a>', text)
    else :
        return re.sub(r'\b((?:https?:\/\/)?(?:www\.)?(?:[^\s.]+\.)+\w{2,4})\b', r'<a href="\1" target="%s">\1</a>' % target, text)
    
# time
def datetimeToUnixtime(d='') :
    if d == '' :
        d = now()
        
    temp = d.split(' ')
    tmp = temp[0].split('-')
    year = tmp[0]
    month = tmp[1]
    day = tmp[2]
    tmp = temp[1].split(':')
    hour = tmp[0]
    min = tmp[1]
    sec = tmp[2]
    
    return mktime(int(hour), int(min), int(sec), int(month), int(day), int(year))
# NOW
def now() :
    dt = str(datetime.datetime.now()).split(".")
    
    return dt[0]

# date
def date(format, t=None):
    if t == None :
        t = time.time()

    return datetime.datetime.fromtimestamp(int(t)).strftime(format)

# 오늘
def today():
    return datetime.date.today()

# 오늘
def yesterday():
    return datetime.date.today() - datetime.timedelta(1)

# mktime
def mktime(hour, min, sec, month, day, year):
   return int(datetime.datetime(year, month, day, hour, min, sec).timestamp()) 

def displayDate(date):
    temp = date.split(' ')
    date = temp[0]
    if today().strftime('%Y-%m-%d') == date :
        date = '오늘'
    elif yesterday().strftime('%Y-%m-%d') == date :
        date = '어제'
    else :
        temp = date.split('-')
        year = today().strftime('%Y')
        if year == temp[0] :
            date = temp[1] + '.' + temp[2]
        else :
            date = temp[0] + '.' + temp[1] + '.' + temp[2]
    return date

# displayRegDate
def displayTime(date):
    temp = date.split(' ')
    time = temp[1]
    temp = time.split(':')
    time = temp[0] + ':' + temp[1]       
                
    return time

# date
def calcBeforeTax(price, vat=10):
    vat = price / (100 + vat) * 10
    vat = int(vat);

    return price - vat

# load_model
def load_model(name):
    result = "";
    
    for i in range(len(name)):
        if name[i].isupper():
            if result != "" :
                result = result + "_"
            result = result + name[i].lower()
        else :
            result = result + name[i]
            
    module = 'elcsoft.model.' + result
    
    tmp = __import__(module, fromlist=module)
            
    return tmp

# load_class
def load_model_class(name):
    module = lib.load_model(name)
    obj = getattr(module, className)()
    return obj

class ELCError(Exception):
    def __init__(self, msg):
        logging.error(msg)
        self.msg = msg

    def __str__(self):
        return self.msg
