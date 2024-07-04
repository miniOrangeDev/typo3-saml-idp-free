<?php

namespace Miniorange\Idp\Helper;

class Constants
{
    //SAML Constants
    const SAML = 'SAML';
    const AUTHN_REQUEST = 'AuthnRequest';
    const SAML_RESPONSE = 'SamlResponse';
    const HTTP_REDIRECT = 'HttpRedirect';
    const LOGOUT_REQUEST = 'LogoutRequest';
    const FE_USER_EMAIL = "fe_email";
    const FEUSER_TYPO3_SES_INDEX = "fe_ses_id";
    const FEUSER_IDP_SESSION_INDEX = "session_index";

    //Table Names
    const TABLE_SAML = 'saml';
    const TABLE_CUSTOMER = 'customer';
    const TABLE_FE_USERS = 'fe_users';

    // COLUMNS IN CUSTOMER TABLE
    const CUSTOMER_EMAIL = "cust_email";
    const CUSTOMER_KEY = "cust_key";
    const CUSTOMER_API_KEY = "cust_api_key";
    const CUSTOMER_TOKEN = "cust_token";
    const CUSTOMER_REGSTATUS = "cust_reg_status";
    const CUSTOMER_CODE = "cust_code";
    const CUSTOMER_OBJECT = "customer_object";

    //SAML Table Columns
    const IDP_BINDING_TYPE = 'login_binding_type';
    const IDP_X509_CERTIFICATE = 'x509_certificate';
    const IDP_LOGOUT_URL = 'saml_logout_url';
    const IDP_LOGIN_URL = 'saml_login_url';
    const SAML_IDPOBJECT = 'object';

    const SAML_SPOBJECT = 'spobject';
    const REDIRECT_URL = 'login_redirect_url';
    const SAML_LOGOUT_URL = 'slo_url';

    const SAML_GMOBJECT = 'gmobject';
    const SAML_ATTROBJECT = 'attrobject';
    const SAML_CUSTOM_ATTROBJECT = 'cattrobject';

    //ATTRIBUTE TABLE COLUMNS
    const ATTRIBUTE_USERNAME = 'saml_am_username';
    const ATTRIBUTE_EMAIL = 'saml_am_email';
    const ATTRIBUTE_FNAME = 'saml_am_fname';
    const ATTRIBUTE_LNAME = 'saml_am_lname';
    const ATTRIBUTE_ADDRESS = 'saml_am_address';
    const ATTRIBUTE_PHONE = 'saml_am_phone';
    const ATTRIBUTE_CITY = 'saml_am_city';
    const ATTRIBUTE_COUNTRY = 'saml_am_country';
    const ATTRIBUTE_GROUPS = 'saml_am_groups';
    const ATTRIBUTE_ZIP = 'saml_am_zip';
    const ATTRIBUTE_TITLE = 'saml_am_title';
    const ATTRIBUTE_UPDATE = 'saml_am_update_attr';

    //Names
    const SP_CERT = 'sp-cert.crt';
    const SP_KEY = 'sp-cert.key';
    const RESOURCE_FOLDER = 'resources';
    const TEST_RELAYSTATE = 'testconfig';
    const SP_ALTERNATE_CERT = 'miniorange_sp_cert.crt';
    const SP_ALTERNATE_KEY = 'miniorange_sp_priv_key.key';

    //Images
    const IMAGE_RIGHT = 'right.png';
    const IMAGE_WRONG = 'wrong.png';

    const HOSTNAME = 'https://login.xecurify.com';
    const HASH = 'aec500ad83a2aaaa7d676c56d8015509d439d56e0e1726b847197f7f089dd8ed';
    const APPLICATION_NAME = 'typo3_saml_premium_plan';

    const MINIORANGE_PRIVATE_KEY = '-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDxJPzwE8E1AFVU
+rmMFwUjRI81BUp7Q/rjP1xUQsPv3qvv1YanTt1r01pI7fD4MboOSMQa9jBqEoJL
0bPFAxOrunnIdTZrLaFogxaGih0DOhueUBsbbeHWRM7FvF4TGlTzGpIh77fDMiop
DKeUi/jGYSH4zLxwu98M6+4yaR9WQtFI5GZ6pMAj/B407eEptDewpAv19GAETwyK
l4oNwoGg+30j6sCuGyi/9XZrVCjZ3LXxzLjKbzWf7wQi+CZtrqtlySEt/pSuSHlx
mcq1inIvN3cPHiGjrDhhSWPstgJqK3XqsDFwCDQLIl7hMuWTTBV8npZgIohARcl9
lCLlB2v5AgMBAAECggEBAJMPKo3Cjl4jQObdLJCpbUGvyuHbwytfLr6tYkIkoVdE
ZXiSsFaR+uiJ8RJuwTirIrsZVzbWEnptpTZVwZsRIErnIuPDz5cXMcsJvo/35G+W
XUdztMRKg6JnPe5KzNg7g7jp6Fp31YpdqmZ0SDKIFgPtMomHWhgqCoHX0+C8SRcR
44AM9f1As0wW0fpe8QcWuL1WvTwELfuigpubZgeR/R7tjQlK5G2gnUMRRNZssnud
BxFM+YpuQDWRhvqavX17YadWEYHhIrfc+LXpBEQqylT7iMOsRtUJb4EaPHtPVWSH
3lf7905KyGtbgJmtx6TaVD25kKJWqlvJNZku1QPq5h0CgYEA+iGofHKZZPYJgtHt
kGYCJjuJTRMdWEkE9uU6IIOBb41ReVdf/laH/y59DgYhkwZlNmqJpXHBDmw6DhGt
B36lm6QUBSqF7dpRKBu9mkGJhXq46pA+7YYcZPja/poZqpHwKgFyQL85r94Vmya5
GtVfZbD/xBWyl1HJcj8Tjr/RFVsCgYEA9s1aJZllIrFQGr+IUPtlxQDqfAxroFuf
kWOOlODfaumIQlxDpkOvqQKDiM01p2tbwkg6Mui3nbUR1zmq8Xr8OxwEcJvFo601
rqrkWtk52F6mzR3/g9cM/O4Q+9mLrbIPlM3exMSZNPyOUxPYo6wI5sCcZ4Pql2oz
PB2BgZWegDsCgYEAxtAX9oq0KA5zstqfZAXgsXjjAjMuQO0RGBlNIWjFaeA+oR70
+tjWkdrmpU30Q8NykVUPAUSweYFGh8Y+7NUaVuKM0ypgd5Tuqt2Zz9FFuKW58x8i
FXNigFNY5cOgoPYMmaa6pIIaHRJ9w+t8d7qfb9nHAZlpcWMdpkRCjFUkoD8CgYA0
V2rV7IlZaTdu5M35NsfnlwEj29J7iNL3l6CmjGZ1mx+Ny1mBintXobPZsIO/hPJJ
6t2E7Hv8k2k8Nvu9CPMzaga7Bx5MTzDCYXXampU9AR3pBIKrKFGV1rt9Xi7UYJ4T
VfH30yYW6bUZg2z/kT22CoVhIsX+5MQG7M8Jd3zM/wKBgFV2r23OXcZaNpZwOPEn
OAgzQvdofg6KNRo/gaQQuTkPy66+qf6boyZw2CRk7PeuvZdfhvzwO+tlB479KbkE
BoU9PqQQJhK44E/CzohErA8Prol2gW/r6DaY4SXGj6CYwKprHd+jOngtK/liERmZ
aYPmHE7sLjzNoEjp5aj2cB78
-----END PRIVATE KEY-----';

    const MINIORANGE_PUBLIC_CERTIFICATE = '-----BEGIN CERTIFICATE-----
MIID/zCCAuegAwIBAgIJAMePaIrAItqFMA0GCSqGSIb3DQEBCwUAMIGVMQswCQYD
VQQGEwJJTjEUMBIGA1UECAwLTUFIQVJBU0hUUkExDTALBgNVBAcMBFBVTkUxEzAR
BgNVBAoMCk1JTklPUkFOR0UxEzARBgNVBAsMCk1JTklPUkFOR0UxEzARBgNVBAMM
Ck1JTklPUkFOR0UxIjAgBgkqhkiG9w0BCQEWE2luZm9AbWluaW9yYW5nZS5jb20w
HhcNMTYwMjA4MTYzNzMzWhcNMjYwMjA1MTYzNzMzWjCBlTELMAkGA1UEBhMCSU4x
FDASBgNVBAgMC01BSEFSQVNIVFJBMQ0wCwYDVQQHDARQVU5FMRMwEQYDVQQKDApN
SU5JT1JBTkdFMRMwEQYDVQQLDApNSU5JT1JBTkdFMRMwEQYDVQQDDApNSU5JT1JB
TkdFMSIwIAYJKoZIhvcNAQkBFhNpbmZvQG1pbmlvcmFuZ2UuY29tMIIBIjANBgkq
hkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8ST88BPBNQBVVPq5jBcFI0SPNQVKe0P6
4z9cVELD796r79WGp07da9NaSO3w+DG6DkjEGvYwahKCS9GzxQMTq7p5yHU2ay2h
aIMWhoodAzobnlAbG23h1kTOxbxeExpU8xqSIe+3wzIqKQynlIv4xmEh+My8cLvf
DOvuMmkfVkLRSORmeqTAI/weNO3hKbQ3sKQL9fRgBE8MipeKDcKBoPt9I+rArhso
v/V2a1Qo2dy18cy4ym81n+8EIvgmba6rZckhLf6Urkh5cZnKtYpyLzd3Dx4ho6w4
YUlj7LYCait16rAxcAg0CyJe4TLlk0wVfJ6WYCKIQEXJfZQi5Qdr+QIDAQABo1Aw
TjAdBgNVHQ4EFgQU3Mp268a2wkuy4lkHJwYz1p+BMD8wHwYDVR0jBBgwFoAU3Mp2
68a2wkuy4lkHJwYz1p+BMD8wDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOC
AQEAcRg44YpnKS0yjd+IByIxqModT6N86QzfcmcHEXCkAN8HWb6pEj0/BBWMb/Bt
SpZNCzrk99y30rAzZxF2yNdvG+0NO1g48/bnE9Usg4DoYZTQ1jsh/2shALPRu6RH
HFmGtoqRtQzPgUoOuzBJg2/+rTFM65snTQc3uaOZu440bhwMbI/mrD6Si2jw0/7d
uET+zPo5BJl1YRrX4EuzP4/ZV2MiD1Pwy0/QZ27mqSsaFILGG/bG4YwsQ73ODKVn
GTV+AezX2owcZF5QqDRGX5fG0d094JdSFCCvoWdCIBYSvnnCXQiq5nV8l2p3SRAl
EfCUl6tQwLsTOgUKXgcp1+L/AQ==
-----END CERTIFICATE-----';

    const AREA_OF_INTEREST = "TYPO3 SAML SP PREMIUM";
    const ENCRYPT_TOKEN = 'BEOESIA9Q3G7FZ3';
    const EMAIL_SENT = 'isEmailSent';

    const DEFAULT_CUSTOMER_KEY     = "16555";
    const DEFAULT_API_KEY         = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
}
