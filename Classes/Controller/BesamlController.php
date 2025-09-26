<?php

namespace Miniorange\Idp\Controller;

use Exception;
use DOMDocument;
use Miniorange\Idp\Helper\AESEncryption;
use Miniorange\Idp\Helper\Constants;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use Miniorange\Idp\Helper\CustomerSaml;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;
use Miniorange\Idp\Helper\Utilities;
use Miniorange\Idp\Helper\SAMLUtilities;
use Miniorange\Idp\Helper\PluginSettings;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use Miniorange\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use TYPO3\CMS\Core\Database\Connection;


/**
 * BesamlController
 */
class BesamlController extends ActionController
{
    /**
     * Helper method to execute database queries with TYPO3 v12/v13 compatibility
     */
    private function executeQuery($queryBuilder, $isSelect = true)
    {
        // Check if the new methods exist (TYPO3 v13+)
        if (method_exists($queryBuilder, 'executeQuery') && method_exists($queryBuilder, 'executeStatement')) {
            return $isSelect ? $queryBuilder->executeQuery() : $queryBuilder->executeStatement();
        } else {
            // TYPO3 v12 and earlier - use legacy methods
            return $queryBuilder->execute();
        }
    }

    /**
     * Helper method to fetch data with TYPO3 v12/v13 compatibility
     */
    private function fetchData($result, $method = 'fetch')
    {
        // Check if the new methods exist (TYPO3 v13+)
        if (method_exists($result, 'fetchAssociative') && method_exists($result, 'fetchAllAssociative')) {
            switch ($method) {
                case 'fetch':
                    return $result->fetchAssociative();
                case 'fetchAll':
                    return $result->fetchAllAssociative();
                case 'fetchOne':
                    return $result->fetchOne();
                default:
                    return $result->fetchAssociative();
            }
        } else {
            // TYPO3 v12 and earlier - use legacy methods
            return $result->$method();
        }
    }

    protected $response = null;
    protected $selectedProvider;
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
        $customer = new CustomerSaml();
        $timestamp = Utilities::fetch_cust(Constants::TIMESTAMP);
        if($timestamp==NULL)
            {
                $timestamp = time();
                $site = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                $email = !empty($GLOBALS['BE_USER']->user['email']) ? $GLOBALS['BE_USER']->user['email'] : $GLOBALS['BE_USER']->user['username'];
                $values=array($site);
                $data = [
                    'timeStamp' => $timestamp,
                    'adminEmail' => $email,
                    'domain' => $site,
                    'pluginName' => 'Typo3 IDP Free',
                    'pluginVersion' => Constants::PLUGIN_VERSION,
                    'pluginFirstPageVisit' => 'Providers',
                    'environmentName' => 'TYPO3',
                    'environmentVersion' => $typo3Version,
                    'IsFreeInstalled' => 'Yes',
                    'FreeInstalledDate' =>  date('Y-m-d H:i:s')
                ];
                $customer->syncPluginMetrics($data);
                $this->update_cust(Constants::TIMESTAMP, $timestamp);
                $this->update_cust('user_limit',AESEncryption::encrypt_data(10,$token));
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_SAML);
                $uid = $this->fetchData(
                    $this->executeQuery($queryBuilder->select('uid')->from(Constants::TABLE_SAML), true),
                    'fetchOne'
                );
                GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
            }
        //------------ IDENTITY PROVIDER SETTINGS---------------
        if (isset($_POST['option'])) {

            $sp_limit = 1;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $added_sps = $this->fetchData(
                $this->executeQuery($queryBuilder
                    ->count('uid')
                    ->from('saml'), true),
                'fetchOne'
            );
            error_log("New Request Received. \nPost Data: " . print_r($_POST, true));
            if ($_POST['option'] == 'save_connector_settings') {
                if ($_POST['idp_entity_id'] != null && $_POST['saml_login_url'] != null && $_POST['sp_name'] != null) {

                    error_log("Received IdP Settings : ");
                    $value1 = $this->validateURL($_POST['saml_login_url']);
                    $value2 = $this->validateURL($_POST['idp_entity_id']);

                    if ($value1 == 1 && $value2 == 1) {
                        $obj = new BesamlController();
                        $obj->storeToDatabase($_POST, $sp_limit, $added_sps);
                        $this->selectedProvider = $_POST['sp_name'];
                        Utilities::showSuccessFlashMessage('Identity Provider settings saved successfully.');
                    } else {
                        Utilities::showErrorFlashMessage('Please provide valid URLs for SAML Login URL and IDP Entity ID.');
                    }
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
                $this->executeQuery($queryBuilder->delete('saml')->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($selectedIdp))), false);
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                $added_idps = $this->fetchData(
                    $this->executeQuery($queryBuilder
                        ->count('uid')
                        ->from('saml'), true),
                    'fetchOne'
                );
                if ($added_idps == 0)
                    $this->selectedProvider = '';
                else {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                    $this->selectedProvider = $this->fetchData(
                        $this->executeQuery($queryBuilder
                            ->select('uid')
                            ->from('saml'), true),
                        'fetchOne'
                    );
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
                    $query = $this->executeQuery($queryBuilder->select('*')->from('saml'), true);
                    while ($row = $this->fetchData($query, 'fetch')) {
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
        $query = $this->executeQuery($queryBuilder->select('*')->from('saml'), true);
        $providersArray = [];
        while ($row = $this->fetchData($query, 'fetch')) {
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
        $added_users = $this->fetchData(
            $this->executeQuery($queryBuilder
                ->count('id')
                ->from('sso_users'), true),
            'fetchOne'
        );
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
        $uid = $this->fetchData(
            $this->executeQuery($queryBuilder->select('uid')->from('saml')
                ->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($creds['sp_name']))), true),
            'fetch'
        );
        
        $uid = is_array($uid) ? $uid['uid'] : $uid;
        if ($uid == null) {
            if($added_sps < $sp_limit)
            {
                error_log("inserting new row: ");
                $this->executeQuery($queryBuilder
                    ->insert('saml')
                    ->values([
                        'sp_name' => $creds['sp_name'],
                        'idp_entity_id' => $creds['idp_entity_id'],
                        'saml_login_url' => $creds['saml_login_url'],
                        'object' => $this->myjson]), false);
            }
            else
            {
                Utilities::showErrorFlashMessage('Provider limit exceeded! Please upgrade to the Premium Plan to add more providers.');
            }
        } else {
            error_log("Updating previous settings : " . $uid);
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $this->executeQuery($queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, )))
                ->set('idp_entity_id', $creds['idp_entity_id']), false);
            $this->executeQuery($queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, )))
                ->set('saml_login_url', $creds['saml_login_url']), false);
            $this->executeQuery($queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, )))
                ->set('object', $this->myjson), false);
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
        $query = $this->executeQuery($queryBuilder->select('*')->from('saml'), true);
        while ($row = $this->fetchData($query, 'fetch')) {
            $this->selectedProvider = $row['sp_name'];
            break;
        }
            $this->selectedProvider = isset($this->selectedProvider) ? $this->selectedProvider : null;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $variable = $this->fetchData(
            $this->executeQuery($queryBuilder->select($var)->from('saml')->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($this->selectedProvider, ))), true),
            'fetch'
        );
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
        $uid = $this->fetchData(
            $this->executeQuery($queryBuilder->select('uid')->from(Constants::TABLE_SAML)
                ->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($postArray['sp_name'], ))), true),
            'fetch'
        );
        $uid = is_array($uid) ? $uid['uid'] : $uid;
        if ($uid == null) {
            $this->executeQuery($queryBuilder
                ->insert(Constants::TABLE_SAML)
                ->values([
                    'sp_entity_id' => $postArray['acs_url'],
                    'acs_url' => $postArray['acs_url'],
                    'sp_name' => $postArray['sp_name'],
                    'sp_default_relaystate' => $postArray['sp_default_relaystate'],
                    'spobject' => $this->myjson]), false);
        } else {
            $this->executeQuery($queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('sp_entity_id', $postArray['sp_entity_id']), false);
            $this->executeQuery($queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('acs_url', $postArray['acs_url']), false);
            $this->executeQuery($queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('sp_default_relaystate', $postArray['sp_default_relaystate']), false);
            $this->executeQuery($queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('spobject', $this->myjson), false);
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
                ContextualFeedbackSeverity::ERROR, true
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
        $this->executeQuery($queryBuilder->update('customer')->where((string)$queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, )))->set($column, $value), false);
    }

// FETCH CUSTOMER

    public function fetch_cust($col)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        $variable = $this->fetchData(
            $this->executeQuery($queryBuilder->select($col)->from('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, ))), true),
            'fetch'
        );
        return is_array($variable) ? $variable[$col] : $variable;
    }

// ---- UPDATE CUSTOMER

    public function insertValue()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        $this->executeQuery($queryBuilder->insert('customer')->values(['id' => '1']), false);
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
        $this->executeQuery($queryBuilder->insert($table)->values([$column => $value]), false);
    }

    public function update_saml_setting($column, $value)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_SAML);
        $this->executeQuery($queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('sp_name', $queryBuilder->createNamedParameter($this->selectedProvider, )))->set($column, $value), false);
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
