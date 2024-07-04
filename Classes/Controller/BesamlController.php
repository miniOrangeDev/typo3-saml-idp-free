<?php

namespace Miniorange\Idp\Controller;

use Exception;
use DOMDocument;
use Miniorange\Idp\Helper\AESEncryption;
use Miniorange\Idp\Helper\Constants;
use PDO;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use Miniorange\Idp\Helper\CustomerSaml;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Miniorange\Idp\Helper\Utilities;
use Miniorange\Idp\Helper\SAMLUtilities;
use Miniorange\Idp\Helper\PluginSettings;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use Miniorange\Domain\Repository\UserGroup\FrontendUserGroupRepository;


/**
 * BesamlController
 */
class BesamlController extends ActionController
{

    protected $response = null;
    protected $responseFactory = null;
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;
    private $myjson = null;
    private $itemRepository = null;
    private $tab = null;

    /**
     * @throws Exception
     */
    public function requestAction()
    {

        $version = new Typo3Version();
        $typo3Version = $version->getVersion();
        $send_email = $this->fetch_cust(Constants::EMAIL_SENT);
        $token = Constants::ENCRYPT_TOKEN;
        if(!$send_email)
            {
                $customer = new CustomerSaml();
                $site = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                $values=array($site);
                $email = !empty($GLOBALS['BE_USER']->user['email']) ? $GLOBALS['BE_USER']->user['email'] : $GLOBALS['BE_USER']->user['username'];
                $customer->submit_to_magento_team($email,'Installed Successfully', $values, $typo3Version);
                $this->update_cust(Constants::EMAIL_SENT,1);
                $this->update_cust('user_limit',AESEncryption::encrypt_data(10,$token));
                GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
            }
        //------------ IDENTITY PROVIDER SETTINGS---------------
        if (isset($_POST['option'])) {

            $sp_limit = 1;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $added_sps = $queryBuilder
                ->count('uid')
                ->from('saml')
                ->execute()
                ->fetchOne();
            error_log("New Request Received. \nPost Data: " . print_r($_POST, true));
            if ($_POST['option'] == 'save_connector_settings') {
                if ($_POST['idp_entity_id'] != null || $_POST['saml_login_url'] != null) {

                    error_log("Received IdP Settings : ");
                    $value1 = $this->validateURL($_POST['saml_login_url']);
                    $value2 = $this->validateURL($_POST['idp_entity_id']);

                    $obj = new BesamlController();
                    $obj->storeToDatabase($_POST, $sp_limit, $added_sps);
                    $this->selectedProvider = $_POST['sp_name'];
                } else {
                    Utilities::showErrorFlashMessage('Please provide proper values.');
                }
            }

            if ($_POST['option'] == 'mosaml_metadata_download') {
                $value1 = $this->validateURL($_POST['saml_login_url']);
                $value2 = $this->validateURL($_POST['idp_entity_id']);

                if ($value1 == 1 && $value2 == 1) {
                    SAMLUtilities::mo_saml_miniorange_generate_metadata($_POST['sp_name'], true);
                } else {
                    Utilities::showErrorFlashMessage('Fill all the fields to download the metadata file');
                }
            }

            if ($_POST['option'] == 'delete') {
                $selectedIdp = $_POST['sp_name'];
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                $queryBuilder->delete('saml')->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($selectedIdp, PDO::PARAM_STR)))->execute();
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                $added_idps = $queryBuilder
                    ->count('uid')
                    ->from('saml')
                    ->execute()
                    ->fetchOne();
                if ($added_idps == 0)
                    $this->selectedProvider = '';
                else {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                    $this->selectedProvider = $queryBuilder
                        ->select('uid')
                        ->from('saml')
                        ->execute()
                        ->fetchOne();
                }
                $this->view->assign('selectedProvider', $this->selectedProvider);
                $this->tab = 'Providers';
            }

            //------------ HANDLING SUPPORT QUERY---------------
            if ($_POST['option'] == 'mo_saml_contact_us_query_option') {
                if (isset($_POST['option']) and $_POST['option'] == "mo_saml_contact_us_query_option") {
                    error_log('Received support query.  ');
                    $this->support();
                }
            }

            //------------ SERVICE PROVIDER SETTINGS---------------
            if ($_POST['option'] === 'save_sp_settings') {
                if ($_POST['sp_name'] != null && $_POST['sp_entity_id'] != null && $_POST['acs_url'] != null) {
                    error_log('Received sp Setting.  ');


                    $this->saveSPSettings($_POST);
                    $this->selectedProvider = $_POST['sp_name'];
                    Utilities::showSuccessFlashMessage('SP Setting saved successfully.');
                } else {
                    Utilities::showErrorFlashMessage('Invalid input provided. Please check ACS URL and EntityID');
                }
            }

            //------------ VERIFY CUSTOMER---------------
            if ($_POST['option'] === 'mo_saml_verify_customer') {
                if (isset($_POST['option']) && $_POST['option'] === "mo_saml_verify_customer") {
                    error_log('Received verify customer request(login). ');
                    $this->account($_POST);
                }
            }

            //------------ HANDLE LOG OUT ACTION---------------
            if (isset($_POST['option']) && $_POST['option'] === 'logout') {


                if ($_POST['submit'] === "Change Account") {
                    $this->removeCustomer();
                } else if ($_POST['submit'] === "Log out") {
                    $status = $this->removeCustomer();
                    error_log("Logout Status: " . $status);
                    if ($status) {
                        Utilities::showSuccessFlashMessage('You account is removed successfully. Your license key is free and can be reused again');
                    } else {
                        Utilities::showErrorFlashMessage('Some error occurred while unregistering your license.');
                    }
                }

                $this->view->assign('status', 'not_logged');

            }


            //------------ CHANGING TABS---------------

            if (!empty($_POST['option'])) {
                if ($_POST['option'] == 'save_sp_settings') {
                    $this->tab = "Identity_Provider";
                } elseif ($_POST['option'] == 'save_connector_settings') {
                    $this->tab = "Identity_Provider";
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                    $query = $queryBuilder->select('*')->from('saml')->execute();
                    while ($row = $query->fetch()) {
                        $this->selectedProvider = $row['sp_name'];
                        break;
                    }
                } elseif ($_POST['option'] == 'add') {
                    $this->view->assign('selectedProvider', '');
                    $this->selectedProvider = '';
                    $this->tab = "Identity_Provider";
                } elseif ($_POST['option'] == 'edit') {
                    $this->view->assign('selectedProvider', $_POST['sp_name']);
                    $this->selectedProvider = $_POST['sp_name'];
                    $this->tab = "Identity_Provider";
                } elseif ($_POST['option'] == 'delete') {
                    $this->tab = "Providers";
                } else {
                    $this->tab = "Account";
                }
            }
        }

        $allUserGroups = GeneralUtility::makeInstance('Miniorange\\Idp\\Domain\\Repository\\UserGroup\\FrontendUserGroupRepository')->findAll();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $query = $queryBuilder->select('*')->from('saml')->execute();
        $providersArray = [];
        while ($row = $query->fetch()) {
            $providersArray[] = $row;
        }

        //------------ LOADING SAVED SETTINGS OBJECTS TO BE USED IN VIEW---------------

        $object = $this->fetch(Constants::SAML_IDPOBJECT);
        $object = isset($object) ? json_decode($object, true) : null;

        $spObject = $this->fetch(Constants::SAML_SPOBJECT);
        $spObject = isset($spObject) ? json_decode($spObject, true) : null;
        $this->view->assign('conf_providers', $providersArray);
        if (!$object) {
            $this->view->assign('idp_metadata_enable', 'disabled');
            $this->view->assign('showIDPSaveMessage', 'display:block');
        } else {
            $this->view->assign('idp_metadata_enable', '');
            $this->view->assign('showIDPSaveMessage', 'display:none');
        }

        //------------ LOADING SAVED SETTINGS OBJECTS TO BE USED IN VIEW---------------
        $this->view->assign('conf_idp', $object);
        $this->view->assign('conf_sp', $spObject);
        $cust_reg_status = $this->fetch_cust('cust_reg_status');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sso_users');
        $added_users = $queryBuilder
        ->count('id')
        ->from('sso_users')
        ->execute()
        ->fetchOne();
        $users_remaining = 10 - $added_users;

        //------------ LOADING VARIABLES TO BE USED IN VIEW---------------
        if ($cust_reg_status == 'logged') {
            $this->view->assign('log', '');
            $this->view->assign('status', 'logged');
            $this->view->assign('nolog', 'display:none');
            $this->view->assign('email', $this->fetch_cust('cust_email'));
            $this->view->assign('key', $this->fetch_cust('cust_key'));
            $this->view->assign('token', $this->fetch_cust('cust_token'));
            $this->view->assign('api_key', $this->fetch_cust('cust_api_key'));
            $this->view->assign('users_remaining',$users_remaining);
        } else {
            $this->view->assign('log', 'disabled');
            $this->view->assign('status', 'not_logged');
            $this->view->assign('nolog', 'display:block');
            $this->view->assign('email', $this->fetch_cust('cust_email'));
        }
        $this->view->assign('tab', $this->tab);
        $this->view->assign('extPath', Utilities::getExtensionRelativePath());
        $this->view->assign('resDir', Utilities::getResourceDir());

        if ($typo3Version >= 11.5) {
            return $this->responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($this->streamFactory->createStream($this->view->render()));
        }

    }

    public function validateURL($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return 1;
        } else {
            return 0;
        }
    }

//  LOGOUT CUSTOMER

    /**
     * @param $creds
     */
    public function storeToDatabase($creds, $sp_limit, $added_sps)
    {
        $this->myjson = json_encode($creds);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $uid = $queryBuilder->select('uid')->from('saml')
            ->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($creds['sp_name'], PDO::PARAM_STR)))
            ->execute()->fetch();
        
        $uid = is_array($uid) ? $uid['uid'] : $uid;
        if ($uid == null) {
            if($added_sps < $sp_limit)
            {
                error_log("inserting new row: ");
                $affectedRows = $queryBuilder
                    ->insert('saml')
                    ->values([
                        'sp_name' => $creds['sp_name'],
                        'idp_entity_id' => $creds['idp_entity_id'],
                        'saml_login_url' => $creds['saml_login_url'],
                        'object' => $this->myjson])
                    ->execute();
            }
            else
            {
                Utilities::showErrorFlashMessage('Provider limit exceeded! Please upgrade to the Premium Plan to add more providers.');
            }
        } else {
            error_log("Updating previous settings : " . $uid);
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)))
                ->set('idp_entity_id', $creds['idp_entity_id'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)))
                ->set('saml_login_url', $creds['saml_login_url'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)))
                ->set('object', $this->myjson)->execute();
        }
    }

    // ---- UPDATE SAML Settings

    /**
     * @param $var
     * @return bool|string
     */
    public function fetch($var)
    {

        if(!isset($this->selectedProvider) || is_null($this->selectedProvider))
        {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $query = $queryBuilder->select('*')->from('saml')->execute();
            while ($row = $query->fetch()) {
                $this->selectedProvider = $row['sp_name'];
                break;
            }
            $this->selectedProvider = isset($this->selectedProvider) ? $this->selectedProvider : null;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $variable = $queryBuilder->select($var)->from('saml')->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($this->selectedProvider, PDO::PARAM_STR)))->execute()->fetch();
        return is_array($variable) ? $variable[$var] : $variable;
    }

//    VALIDATE CERTIFICATE

    public function support()
    {
        if (!$this->mo_saml_is_curl_installed()) {
            Utilities::showErrorFlashMessage('ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Query submit failed.');
            return;
        }
        // Contact Us query
        $email = $_POST['mo_saml_contact_us_email'];
        $phone = $_POST['mo_saml_contact_us_phone'];
        $query = $_POST['mo_saml_contact_us_query'];

        $customer = new CustomerSaml();

        if ($this->mo_saml_check_empty_or_null($email) || $this->mo_saml_check_empty_or_null($query)) {
            Utilities::showErrorFlashMessage('Please enter a valid Email address. ');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Utilities::showErrorFlashMessage('Please enter a valid Email address. ');
        } else {
            $submitted = json_decode($customer->submit_contact($email, $phone, $query), true);
            if ($submitted['status'] == 'SUCCESS') {
                Utilities::showSuccessFlashMessage('Support query sent ! We will get in touch with you shortly.');
            } else {
                Utilities::showErrorFlashMessage('could not send query. Please try again later or mail us at info@miniorange.com');
            }
        }

    }

//    VALIDATE URLS

    public function mo_saml_is_curl_installed()
    {
        if (in_array('curl', get_loaded_extensions())) {
            return 1;
        } else {
            return 0;
        }
    }

//    VERIFY LICENSE KEY

    public function mo_saml_check_empty_or_null($value)
    {
        if (!isset($value) || empty($value)) {
            return true;
        }
        return false;
    }

    /**
     * @param $postArray
     */
    public function saveSPSettings($postArray)
    {
        error_log("saveSPSettings");
        error_log(print_r($postArray, true));
        if ($postArray["nameid_format"] == "emailAddress") {
            $postArray["sp_email_selected"] = "selected";
            $postArray["sp_unspecified_selected"] = "";
            $postArray["sp_transient_selected"] = "";
            $postArray["sp_persistent_selected"] = "";
        } elseif ($postArray["nameid_format"] == "unspecified") {
            $postArray["sp_email_selected"] = "";
            $postArray["sp_unspecified_selected"] = "selected";
            $postArray["sp_transient_selected"] = "";
            $postArray["sp_persistent_selected"] = "";
        } elseif ($postArray["nameid_format"] == "transient") {
            $postArray["sp_email_selected"] = "";
            $postArray["sp_unspecified_selected"] = "";
            $postArray["sp_transient_selected"] = "selected";
            $postArray["sp_persistent_selected"] = "";
        } elseif ($postArray["nameid_format"] == "persistent") {
            $postArray["sp_email_selected"] = "";
            $postArray["sp_unspecified_selected"] = "";
            $postArray["sp_transient_selected"] = "";
            $postArray["sp_persistent_selected"] = "selected";
        }

        $this->myjson = json_encode($postArray);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_SAML);
        $uid = $queryBuilder->select('uid')->from(Constants::TABLE_SAML)
            ->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($postArray['sp_name'], PDO::PARAM_STR)))
            ->execute()->fetch();
        $uid = is_array($uid) ? $uid['uid'] : $uid;
        if ($uid == null) {
            $affectedRows = $queryBuilder
                ->insert(Constants::TABLE_SAML)
                ->values([
                    'sp_entity_id' => $postArray['acs_url'],
                    'acs_url' => $postArray['acs_url'],
                    'sp_name' => $postArray['sp_name'],
                    'sp_default_relaystate' => $postArray['sp_default_relaystate'],
                    'spobject' => $this->myjson])
                ->execute();
        } else {
            $queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('sp_entity_id', $postArray['sp_entity_id'])->execute();
            $queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('acs_url', $postArray['acs_url'])->execute();
            $queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('sp_default_relaystate', $postArray['sp_default_relaystate'])->execute();
            $queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('spobject', $this->myjson)->execute();
        }
    }

//   HANDLE LOGIN FORM

    public function account($post)
    {
        $email = $post['email'];
        $password = $post['password'];
        $customer = new CustomerSaml();
        $customer->email = $email;
        $this->update_cust('cust_email', $email);
        $check_content = json_decode($customer->check_customer($email, $password), true);
        if ($check_content['status'] == 'CUSTOMER_NOT_FOUND') {
            $message = GeneralUtility::makeInstance(FlashMessage::class,
                'ERROR', $email . ' does not exist',
                FlashMessage::ERROR, true
            );
            Utilities::showErrorFlashMessage('Customer Not Found!');
        } elseif ($check_content['status'] == 'SUCCESS') {
            $key_content = json_decode($customer->get_customer_key($email, $password), true);
            if ($key_content['status']) {
                $this->save_customer($key_content, $email);
                Utilities::showSuccessFlashMessage('User retrieved successfully.');
            } else {
                Utilities::showErrorFlashMessage('It seems like you have entered the incorrect password');
            }
        }
    }

//  SAVE CUSTOMER

    public function update_cust($column, $value)
    {
        if ($this->fetch_cust('id') == null) {
            $this->insertValue();
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        $queryBuilder->update('customer')->where((string)$queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->execute();
    }

// FETCH CUSTOMER

    public function fetch_cust($col)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        $variable = $queryBuilder->select($col)->from('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetch();
        return is_array($variable) ? $variable[$col] : $variable;
    }

// ---- UPDATE CUSTOMER

    public function insertValue()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        $affectedRows = $queryBuilder->insert('customer')->values(['id' => '1'])->execute();
    }

    public function save_customer($content, $email)
    {
        $this->update_cust('cust_key', $content['id']);
        $this->update_cust('cust_api_key', $content['apiKey']);
        $this->update_cust('cust_token', $content['token']);
        $this->update_cust('cust_reg_status', 'logged');
        $this->update_cust('cust_email', $email);
    }


    public function removeCustomer()
    {
        $this->update_cust(Constants::CUSTOMER_KEY, '');
        $this->update_cust(Constants::CUSTOMER_API_KEY, '');
        $this->update_cust(Constants::CUSTOMER_TOKEN, '');
        $this->update_cust(Constants::CUSTOMER_REGSTATUS, '');
        $this->update_cust(Constants::CUSTOMER_EMAIL, '');
        $this->update_cust(Constants::CUSTOMER_OBJECT, '');
        $this->update_cust(Constants::CUSTOMER_CODE, '');
        return true;
    }

    public function save($column, $value, $table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->insert($table)->values([$column => $value])->execute();
    }

    public function update_saml_setting($column, $value)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_SAML);
        $queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($this->selectedProvider, PDO::PARAM_STR)))->set($column, $value)->execute();
    }

    public function validate_cert($saml_x509_certificate)
    {
        $certificate = openssl_x509_parse($saml_x509_certificate);
        foreach ($certificate as $key => $value) {
            if (empty($value)) {
                unset($saml_x509_certificate[$key]);
                return 0;
            } else {
                $saml_x509_certificate[$key] = $this->sanitize_certificate($value);
                if (!@openssl_x509_read($saml_x509_certificate[$key])) {
                    return 0;
                }
            }
        }

        if (empty($saml_x509_certificate)) {
            return 0;
        }

        return 1;
    }

}
