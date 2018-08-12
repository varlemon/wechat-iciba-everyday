#!/usr/bin/python3
#coding=utf-8
import json
import requests

class iciba:
    # 初始化
    def __init__(self, wechat_config):
        self.appid = wechat_config['appid']
        self.appsecret = wechat_config['appsecret']
        self.template_id = wechat_config['template_id']
        self.access_token = ''

    # 获取access_token
    def get_access_token(self, appid, appsecret):
        url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s' % (str(appid), str(appsecret))
        r = requests.get(url)
        data = json.loads(r.text)
        access_token = data['access_token']
        self.access_token = access_token
        return self.access_token

    # 获取用户列表
    def get_user_list(self):
        if self.access_token == '':
            self.get_access_token(self.appid, self.appsecret)
        access_token = self.access_token
        url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=%s&next_openid=' % str(access_token)
        r = requests.get(url)
        return json.loads(r.text)

    # 发送消息
    def send_msg(self, openid, template_id, iciba_everyday):
        msg = {
            'touser': openid,
            'template_id': template_id,
            'url': iciba_everyday['fenxiang_img'],
            'data': {
                'content': {
                    'value': iciba_everyday['content'],
                    'color': '#0000CD'
                    },
                'note': {
                    'value': iciba_everyday['note'],
                },
                'translation': {
                    'value': iciba_everyday['translation'],
                }
            }
        }
        json_data = json.dumps(msg)
        if self.access_token == '':
            self.get_access_token(self.appid, self.appsecret)
        access_token = self.access_token
        url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s' % str(access_token)
        r = requests.post(url, json_data)
        return json.loads(r.text)

    # 获取爱词霸每日一句
    def get_iciba_everyday(self):
        url = 'http://open.iciba.com/dsapi/'
        r = requests.get(url)
        return json.loads(r.text)

    # 为设置的用户列表发送消息
    def send_everyday_words(self, openids):
        everyday_words = self.get_iciba_everyday()
        for openid in openids:
            result = self.send_msg(openid, self.template_id, everyday_words)
            if result['errcode'] == 0:
                print (' [INFO] send to %s is success' % openid)
            else:
                print (' [ERROR] send to %s is error' % openid)

    # 执行
    def run(self, openids=[]):
        if openids == []:
            # 如果openids为空，则遍历用户列表
            result = self.get_user_list()
            openids = result['data']['openid']
        # 根据openids对用户进行群发
        self.send_everyday_words(openids)


if __name__ == '__main__':
    # 微信配置
    wechat_config = {
        'appid': 'xxxxx', #(No.1)此处填写你的appid
        'appsecret': 'xxxxx', #(No.2)此处填写你的appsecret
        'template_id': 'xxxxx' #(No.3)此处填写你的模板消息ID
    }
    
    # 用户列表
    openids = [
        'xxxxx', #(No.4)此处填写你的微信号（微信公众平台上你的微信号）
        #'xxxxx', #如果有多个用户也可以
        #'xxxxx',
    ]

    # 执行
    icb = iciba(wechat_config)

    # run()方法可以传入openids列表，也可不传参数
    # 不传参数则对微信公众号的所有用户进行群发
    icb.run()


