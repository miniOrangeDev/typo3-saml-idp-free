<?php

namespace Miniorange\Idp\Controller;

use Miniorange\Idp\Helper\Constants;
use Miniorange\Idp\Helper\Messages;
use Miniorange\Idp\Helper\PluginSettings;
use Miniorange\Idp\Domain\Model\Fesaml;
use Miniorange\SSO;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Miniorange\Idp\Helper\SAMLUtilities;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;
use Miniorange\Idp\Helper\Actions;
use Miniorange\Classes;
use Miniorange\Idp\Helper\SamlResponse;
use Miniorange\Idp\Helper;
use Miniorange\Idp\Helper\Lib\XMLSecLibs\XMLSecurityKey;
use Miniorange\Idp\Helper\Utilities;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Database\Connection;
use Miniorange\Idp\Helper\MiniOrangeAuthnRequest;
use DOMDocument;
use TYPO3\CMS\Core\Information\Typo3Version;
use Miniorange\Idp\Helper\AESEncryption;
use Miniorange\Idp\Helper\CustomerSaml;


/***
 *
 * This file is part of the "SAML SP Premium" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Miniorange <info@xecurify.com>
 *
 ***/

/**
 * FesamlController
 */
class FesamlController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    protected $fesamlRepository = null;

    protected $idp_name = null;

    protected $acs_url = null;

    protected $sp_entity_id = null;

    protected $force_authn = null;

    protected $saml_login_url = null;
    protected $x509_certificate = null;
    protected $uid = 1;
    private $idp_entity_id = null;
    private $issuer = null;
    private $name_id_attr = null;
    private $saml_sp_object = null;
    private $name_id_attr_format = null;
    private $state = null;
    private $audience = null;
    private $inResponseTo = null;
    private $ssoUrl = null;
    private $destination = null;
    private $bindingType = null;
    private $signedAssertion = null;
    private $signedResponse = null;

    /**
     * action list
     *
     * @param Miniorange\Idp\Domain\Model\Fesaml
     * @param Miniorange\Idp\Domain\Model\Fesaml
     * @return void
     * @return void
     */
    public function listAction()
    {
        $samlmodels = $this->samlmodelRepository->findAll();
        $this->view->assign('samlmodels', $samlmodels);
    }

    /**
     * action print
     *
     * @param Miniorange\Idp\Domain\Model\Fesaml
     * @return void
     * @throws \Exception
     */
    public function requestAction()
    {
        $version = new Typo3Version();
        $typo3Version = $version->getVersion();
        if (isset($_REQUEST['SAMLRequest']) || isset($_POST['SAMLRequest'])) {

            $samlRequest = isset($_POST['SAMLRequest']) ? $_POST['SAMLRequest'] : $_REQUEST['SAMLRequest'];
            $decodedSamlRequest = base64_decode($samlRequest);

            $samlRequest = @gzinflate($decodedSamlRequest);
            if ($samlRequest === false) {
                // If gzinflate fails, assume the request is not compressed
                $samlRequest = $decodedSamlRequest;
            }

            $document = new DOMDocument();
            $document->loadXML($samlRequest);

            $samlRequestXML = $document->firstChild;
            $authnRequest = new MiniOrangeAuthnRequest($samlRequestXML);
            $acs_url_from_request = $authnRequest->getAssertionConsumerServiceURL();
            $sp_issuer_from_request = $authnRequest->getIssuer();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $sp_name = $queryBuilder->select('sp_name')->from('saml')->where($queryBuilder->expr()->eq('sp_entity_id', $queryBuilder->createNamedParameter($sp_issuer_from_request, \PDO::PARAM_STR)))->execute()->fetch();
            if($sp_name)
            {
                $sp_name = is_array($sp_name) ? $sp_name['sp_name'] : $sp_name;
            }
            else
            {
                echo("Someting went wrong. Please contact your administrator!");exit;
            }
        }
        if (isset($_REQUEST['sp_name'])) {
            $sp_name = $_REQUEST['sp_name'];
        }
        $this->controlAction($sp_name);

        if (isset($_REQUEST['option']) and $_REQUEST['option'] == 'mosaml_metadata') {
            SAMLUtilities::mo_saml_miniorange_generate_metadata($sp_name);
        } else {
            foreach ($_REQUEST as $key => $value) {
                if (strpos($value, "option=mosaml_metadata") !== false) {
                    SAMLUtilities::mo_saml_miniorange_generate_metadata($sp_name);
                    break;
                }
            }
        }
        $idpArray = json_decode($this->saml_idp_object);
        $issuer = $idpArray->idp_entity_id;
        if (is_null($GLOBALS['TSFE']->fe_user->user)) {
            $responseFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Psr\Http\Message\ResponseFactoryInterface::class
            );

            $response = $responseFactory
                ->createResponse()
                ->withAddedHeader('location', $issuer);
            return $response;
        }

        $user = $GLOBALS['TSFE']->fe_user->user;
        $email = $GLOBALS['TSFE']->fe_user->user['email'];
        $username = $GLOBALS['TSFE']->fe_user->user['username'];
        $attribute = array();
        $temp = explode(' ', $username);
        $attribute["FirstName"] = $GLOBALS['TSFE']->fe_user->user['first_name'];
        $attribute["LastName"] = $GLOBALS['TSFE']->fe_user->user['last_name'];
        $attribute["Group ID"] = $GLOBALS['TSFE']->fe_user->user['usergroup'];
        $attribute["Address"] = $GLOBALS['TSFE']->fe_user->user['address'];
        $attribute["Fax"] = $GLOBALS['TSFE']->fe_user->user['fax'];
        $attribute["Telephone"] = $GLOBALS['TSFE']->fe_user->user['telephone'];
        $attribute["Zip"] = $GLOBALS['TSFE']->fe_user->user['zip'];
        $attribute["City"] = $GLOBALS['TSFE']->fe_user->user['city'];
        $attribute["Country"] = $GLOBALS['TSFE']->fe_user->user['country'];
        $spArray = json_decode($this->saml_sp_object);
        $name_id_attr = $spArray->nameid_format;
        $acs_url = $spArray->acs_url;

        $name_id_attr_format = "1.1:nameid-format:emailAddress";
        $idp_assertion_signed = TRUE;
        $relayState = $spArray->sp_default_relaystate;
        $audience = $spArray->sp_entity_id;
        $inResponseTo = "";

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sso_users');
        $id = $queryBuilder->select('id')->from('sso_users')->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)))->execute()->fetch();

        if(!$id)
        {
            $token = Constants::ENCRYPT_TOKEN;
            $uLimit = AESEncryption::decrypt_data(Utilities::fetch_cust('user_limit'), $token);
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sso_users');
            $added_users = $queryBuilder
            ->count('id')
            ->from('sso_users')
            ->execute()
            ->fetchOne();
            if($added_users < $uLimit)
            {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sso_users');
                $queryBuilder->insert('sso_users')->values(['username' => $username])->execute();
            }
            else
            {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                $email_sent = Utilities::fetch_cust(Constants::EMAIL_SENT);
                if($email_sent < 10)
                {
                    $site = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                    $customer = new CustomerSaml();
                    $customer->submit_to_magento_team_autocreate_limit_exceeded($site, $typo3Version);
                    $queryBuilder = Utilities::update_cust(Constants::EMAIL_SENT,$email_sent+1);
                }
                echo("User Limit Exceeded! Please contact your administrator.");exit;
            }
        }
        if (isset($_REQUEST['RelayState'])) {
            $relayState = $_REQUEST['RelayState'];
        }
        $saml_response_obj = new GenerateResponse($email, $username, $acs_url, $issuer, $audience, $inResponseTo, $name_id_attr, $name_id_attr_format, $idp_assertion_signed, $attribute);

        $saml_response = $saml_response_obj->createSamlResponse();

        setcookie("response_params", "");

        self::_send_response($saml_response, $relayState, $acs_url);

        if ($typo3Version >= 11.5) {
            return $this->responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($this->streamFactory->createStream($this->view->render()));
        }

    }

    /**
     * action control
     *
     * @return void
     */
    public function controlAction($sp_name)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $this->saml_sp_object = $queryBuilder->select(Constants::SAML_SPOBJECT)->from('saml')->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($sp_name, \PDO::PARAM_STR)))->execute()->fetch();
        $this->saml_idp_object = $queryBuilder->select(Constants::SAML_IDPOBJECT)->from('saml')->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($sp_name, \PDO::PARAM_STR)))->execute()->fetch();
        $this->saml_sp_object = is_array($this->saml_sp_object) ? $this->saml_sp_object[Constants::SAML_SPOBJECT] : $this->saml_sp_object;
        $this->saml_idp_object = is_array($this->saml_idp_object) ? $this->saml_idp_object[Constants::SAML_IDPOBJECT] : $this->saml_idp_object;

    }

    public function _send_response($saml_response, $ssoUrl, $acs_url)
    {


        $saml_response = base64_encode($saml_response);
        ?>
        <form id="responseform" action="<?php echo $acs_url; ?>" method="post">
            <input type="hidden" name="SAMLResponse" value="<?php echo htmlspecialchars($saml_response); ?>"/>
            <input type="hidden" name="RelayState" value="<?php echo $ssoUrl; ?>"/>
        </form>
        <script>
            setTimeout(function () {
                document.getElementById('responseform').submit();
            }, 100);
        </script>
        <?php

        exit;
    }

    public function build()
    {
        $requestXmlStr = $this->generateXML();
        if (empty($this->bindingType) || $this->bindingType == Constants::HTTP_REDIRECT) {
            $deflatedStr = gzdeflate($requestXmlStr);
            $base64EncodedStr = base64_encode($deflatedStr);
            $urlEncoded = urlencode($base64EncodedStr);
            $requestXmlStr = $urlEncoded;
        }
        return $requestXmlStr;
    }

    private function generateXML()
    {
        $requestXmlStr = '<?xml version="1.0" encoding="UTF-8"?>' . ' <samlp:AuthnRequest 
                                xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" 
                                xmlns="urn:oasis:names:tc:SAML:2.0:assertion" ID="' . $this->generateID() . '"  Version="2.0" IssueInstant="' . $this->generateTimestamp() . '"';
        // add force authn element
        if ($this->force_authn) {
            $requestXmlStr .= ' ForceAuthn="true"';
        }
        $requestXmlStr .= '     ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" AssertionConsumerServiceURL="' . $this->acs_url . '"      Destination="' . $this->destination . '">
                                <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $this->sp_entity_id . '</saml:Issuer>
                                <samlp:NameIDPolicy AllowCreate="true" Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified"/>
                            </samlp:AuthnRequest>';
        return $requestXmlStr;
    }

    public function generateID()
    {
        $str = $this->stringToHex($this->generateRandomBytes(21));
        return '_' . $str;
    }

    /**
     * @param $bytes
     * @return string
     */
    public static function stringToHex($bytes)
    {
        $ret = '';
        for ($i = 0; $i < strlen($bytes); $i++) {
            $ret .= sprintf('%02x', ord($bytes[$i]));
        }
        return $ret;
    }

    /**
     * @param $length
     * @param $fallback
     * @return string
     */
    public function generateRandomBytes($length, $fallback = TRUE)
    {
        return openssl_random_pseudo_bytes($length);
    }

    /**
     * @param $instant
     * @return false|string
     */
    public function generateTimestamp($instant = NULL)
    {
        if ($instant === NULL) {
            $instant = time();
        }
        return gmdate('Y-m-d\\TH:i:s\\Z', $instant);
    }

}

