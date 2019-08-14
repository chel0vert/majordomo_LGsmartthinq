from selenium import webdriver
from selenium.webdriver.firefox.options import Options
import time
from xvfbwrapper import Xvfb
import argparse
import json
try:
    from urllib.parse import urljoin, urlencode, urlparse, parse_qs
except ImportError:
     from urlparse import urljoin, urlparse, parse_qs
     from urllib import urlencode

parser = argparse.ArgumentParser()
parser.add_argument('--login'  ,       help='user login')
parser.add_argument('--password' ,     help='user password')
parser.add_argument('--url',           help='login url')
args = parser.parse_args()

vdisplay = Xvfb()
vdisplay.start()

options = Options()
options.set_headless()
options.add_argument("--no-sandbox")
options.add_argument("--disable-setuid-sandbox")
driver = webdriver.Firefox(options=options)
driver.get(args.url)
driver.find_element_by_xpath('//input[@id="uid"]').send_keys(args.login)
driver.find_element_by_xpath('//input[@id="upw"]').send_keys(args.password)
driver.find_element_by_xpath('//button[@id="btn_login"]').click()
time.sleep(2)
url = driver.current_url
params = parse_qs(urlparse(url).query)
try:
    access_token = params['access_token'][0]
    refresh_token = params['refresh_token'][0]
except KeyError:
    access_token = None
    refresh_token = None
result = {
    'access_token' : access_token,
    'refresh_token': refresh_token,
}

print (json.dumps(result))
driver.close()
vdisplay.stop()