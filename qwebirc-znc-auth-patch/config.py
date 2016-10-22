from qwebirc.config_options import *
IRCSERVER, IRCPORT = "localhost", 4950
SSLPORT = 5050
REALNAME = "StormBNC Web Client"
IDENT = "IDENT_FORM"
WEBIRC_MODE = None

BASE_URL = "https://bnc.stormbit.net/config/"
NETWORK_NAME = "StormBNC"
APP_TITLE = NETWORK_NAME + " Web Client"
NICKNAME_VALIDATE = True

import string
NICKNAME_VALID_FIRST_CHAR = string.letters + "_[]{}`^\\|"
NICKNAME_VALID_SUBSEQUENT_CHARS = NICKNAME_VALID_FIRST_CHAR + string.digits + "-"
NICKNAME_MINIMUM_LENGTH = 2
NICKNAME_MAXIMUM_LENGTH = 15
FEEDBACK_FROM = "feedback@bnc.stormbit.net"
FEEDBACK_TO = "alexm@stormbit.net"
FEEDBACK_SMTP_HOST, FEEDBACK_SMTP_PORT = "127.0.0.1", 25
ADMIN_ENGINE_HOSTS = [""]
FORWARDED_FOR_HEADER="x-forwarded-for"
FORWARDED_FOR_IPS=["127.0.0.1"]
ARGS = "-n -p 3989"
UPDATE_FREQ = 0.5
MAXBUFLEN = 100000
MAXSUBSCRIPTIONS = 3
MAXLINELEN = 600
DNS_TIMEOUT = 5
HTTP_AJAX_REQUEST_TIMEOUT = 30
HTTP_REQUEST_TIMEOUT = 5
STATIC_BASE_URL = ""
DYNAMIC_BASE_URL = ""
CONNECTION_RESOLVER = None
HELP_URL = "https://bnc.stormbit.net/"
HMACKEY = "nohmackeyset"
HMACTEMPORAL = 30
AUTHGATEDOMAIN = "webchat_test"
QTICKETKEY = "boo"
AUTH_SERVICE = "Q!TheQBot@CServe.quakenet.org"
AUTH_OK_REGEX = "^You are now logged in as [^ ]+\\.$"
import dummyauthgate as AUTHGATEPROVIDER
