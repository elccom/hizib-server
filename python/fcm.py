# -*- coding: utf-8 -*-
import argparse
import firebase_admin

from lib import *
from firebase_admin import credentials
from firebase_admin import messaging

def send(**kwargs) :
    path = "/home/hizib/fcm_auth.json"
    cred = credentials.Certificate(path)
    app = firebase_admin.initialize_app(cred)

    title = kwargs['title']
    body = kwargs['body']

    if 'data' in kwargs :
        data = kwargs['data']
    else :
        data = {}

    if 'click_action' in kwargs :
        click_action = kwargs['click_action']
    else :
        click_action = ''

    token = kwargs['token']

    message = messaging.Message(
        notification=messaging.Notification(
            title=title,
            body=body
        ),
        data=data,
        token=token
    )

    response = messaging.send(message)

    return response

def sends(**kwargs) :
    path = "/home/hizib/fcm_auth.json"
    cred = credentials.Certificate(path)
    app = firebase_admin.initialize_app(cred)

    title = kwargs['title']
    body = kwargs['body']

    if 'data' in kwargs :
        data = kwargs['data']
    else :
        data = {}

    if 'click_action' in kwargs :
        click_action = kwargs['click_action']
    else :
        click_action = ''

    tokens = kwargs['tokens']

    message = messaging.MulticastMessage(
        notification=messaging.Notification(
            title=title,
            body=body
        ),
        data=data,
        tokens=tokens
    )

    response = messaging.send(message)

    return response

def main():
    cmds = ['send','sends']
    parser = argparse.ArgumentParser(prog='PROG')
    parser.add_argument('cmd', choices=cmds, help='bar help')
    parser.add_argument('-token', required=True, help='Fcm Token')
    parser.add_argument('-title', required=True, help='Fcm Title')
    parser.add_argument('-body', required=True, help='Fcm Body')
    parser.add_argument('-image', required=False, help='Fcm Image')
    parser.add_argument('-data', required=False, help='Fcm data')
    args = parser.parse_args()

    kwargs = dict()
    kwargs['title'] = args.title
    kwargs['body'] = args.body

    if hasattr(args, 'image'):
        kwargs['image'] = args.image

    if hasattr(args, 'data') :
        kwargs['data'] = jsondecode(args.data)

    results = dict()

    if args.cmd == "send" :
        kwargs['token'] = args.token

        try :
            send(**kwargs)
            results['result'] = True
        except Exception as e :
            results['result'] = False
            results['message'] = getattr(e, 'message', str(e))

        print(jsonencode(results))
    elif args.cmd == "sends" :
        kwargs['tokens'] = args.token.split(',')

        try :
            sends(**kwargs)
            results['result'] = True
        except Exception as e :
            results['result'] = False
            results['message'] = getattr(e, 'message', str(e))

        print(jsonencode(results))

if __name__ == '__main__':
    main()


