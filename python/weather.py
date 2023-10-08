# -*- coding: utf-8 -*-
import rlcompleter
import argparse
import datetime
import requests
import json
from bs4 import BeautifulSoup
from lib import *

def naverWeather(areacode) :
    url = 'https://weather.naver.com/today/%s' % areacode
    res = requests.get(url)

    if res.status_code == 200:
        soup = BeautifulSoup(res.text, 'html.parser')

        # 현재위치
        temp = soup.select_one("#now > div > div.location_info_area > div.location_area > strong")
        location = temp.text.strip()

        # 현재날씨
        temp = soup.select_one("#now > div > div.weather_area > div.weather_now > p > span.weather")
        sky = temp.text.strip()

        # 현재날씨
        temp = soup.select_one("#now > div > div.weather_area > div.weather_now > div > i")
        icon = getIcon(temp.get('data-ico'))

        # 현재기온
        temp = soup.select_one("#now > div > div.weather_area > div.weather_now > div > strong")
        currentTemperature = temp.text.replace('현재 온도', '').replace('°', '').replace("\n", "")

        # 최고기온
        temp = soup.select_one("#weekly > ul > li:nth-child(1) > div > div.cell_temperature > strong > span.highest")
        maxTemperature = temp.text.replace('최고기온', '').replace('°', '')

        # 최저기온
        temp = soup.select_one("#weekly > ul > li:nth-child(1) > div > div.cell_temperature > strong > span.lowest")
        minTemperature = temp.text.replace('최저기온', '').replace('°', '')

        data = [];
        str1 = res.text.split('var hourlyFcastListJson =')
        str2 = str1[1].split(';')
        items = jsondecode(str2[0])
        #items = soup.select("#hourly > div.weather_graph > div > div > div > table > thead > tr > th._cnItemTime")
        #print(items)
        i = 1
        for item in items:
            obj = dict()
            obj['date'] = item['aplYmdt']
            obj['temperature'] = item['tmpr']
            obj['sky'] = item['wetrTxt']
            obj['icon'] = item['wetrCd']
            data.append(obj)

            i = i + 1

        result = dict()
        result['location'] = location
        result['currentTemperature'] = currentTemperature
        result['maxTemperature'] = maxTemperature
        result['minTemperature'] = minTemperature
        result['sky'] = sky
        result['icon'] = icon
        result['lists'] = data
        return result

    return None

# naverAir
def naverAir(areacode):
    url = 'https://weather.naver.com/air/%s' % areacode
    res = requests.get(url)

    result = dict()

    if res.status_code == 200:
        soup = BeautifulSoup(res.text, 'html.parser')

        result = dict()

        temp = soup.select_one(
            "#content > div > div.section_right > div.card.card_dust > div.top_area em > span.grade._cnPm10Grade")
        result['finedust'] = temp.text

        # 최고기온
        temp = soup.select_one(
            "#content > div > div.section_right > div.card.card_dust > div.top_area em > span.grade._cnPm25Grade")
        result['ultrafinedust'] = temp.text

        return result

    return None

# getSky
def getIcon(msg):
    return msg.replace("ico_animation_wt", "")
# areacode
def areacode(keyword) :
    url = 'https://ac.weather.naver.com/ac?q_enc=utf-8&r_format=json&r_enc=utf-8&r_lt=1&st=1&q=%s' % keyword
    res = requests.get(url)

    if res.status_code == 200 :
        return jsondecode(res.text)

def main():
    cmds = ['weather','areacode']
    parser = argparse.ArgumentParser(prog='PROG')
    parser.add_argument('cmd', choices=cmds, help='bar help')
    parser.add_argument('-keyword', help='Naver Weather Keyword')
    parser.add_argument('-areacode', help='Naver Weather area code')
    args = parser.parse_args()

    if args.cmd == "weather" :
        result = naverWeather(args.areacode)
        temp = naverAir(args.areacode)

        result['finedust'] = temp['finedust']
        result['ultrafinedust'] = temp['ultrafinedust']
    else :
        result = areacode(args.keyword)

    rs = jsonencode(result)
    print(rs)

if __name__ == '__main__':
    main()


