<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Bc_ProtectForm extends Module
{
    public function __construct()
    {
        $this->name = 'bc_protectform';
        $this->tab = 'front_office_features';
        $this->version = '0.1.0';
        $this->author = 'BestCoding.net';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('BC Form Protection');
        $this->description = $this->l('Adds CSRF protection to various PrestaShop forms');
        $this->ps_versions_compliancy = array('min' => '1.6.0.0', 'max' => '8.99.99');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('actionObjectCustomerAddBefore') &&
            $this->registerHook('actionCustomerLoginBefore') &&
            $this->registerHook('actionBeforeAuthentication') &&
            Configuration::updateValue('BC_PROTECTFORM_SIGNUP', 1) &&
            Configuration::updateValue('BC_PROTECTFORM_LOGIN', 1);
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            Configuration::deleteByName('BC_PROTECTFORM_SIGNUP') &&
            Configuration::deleteByName('BC_PROTECTFORM_LOGIN');
    }

    public function getContent()
    {
        $output = '';
        
        if (Tools::isSubmit('submit' . $this->name)) {
            $signup_enabled = (int)Tools::getValue('BC_PROTECTFORM_SIGNUP');
            $login_enabled = (int)Tools::getValue('BC_PROTECTFORM_LOGIN');
            
            Configuration::updateValue('BC_PROTECTFORM_SIGNUP', $signup_enabled);
            Configuration::updateValue('BC_PROTECTFORM_LOGIN', $login_enabled);
            
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
        
        return $output . $this->renderForm();
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable CSRF protection for Signup Form'),
                        'name' => 'BC_PROTECTFORM_SIGNUP',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable CSRF protection for Login Form'),
                        'name' => 'BC_PROTECTFORM_LOGIN',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => array(
                'BC_PROTECTFORM_SIGNUP' => Configuration::get('BC_PROTECTFORM_SIGNUP'),
                'BC_PROTECTFORM_LOGIN' => Configuration::get('BC_PROTECTFORM_LOGIN'),
            ),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function hookDisplayHeader()
    {
        // Generate CSRF token if not exists
        if (!isset($this->context->cookie->bc_csrf_token)) {
            $this->context->cookie->bc_csrf_token = md5(uniqid(rand(), true));
            $this->context->cookie->write();
        }

        // Add JS file for automatic token injection
        $this->context->controller->addJS($this->_path . 'views/js/bc_protectform.js');
        
        // Pass configuration to JavaScript
        Media::addJsDef(array(
            'bcProtectformConfig' => array(
                'token' => $this->context->cookie->bc_csrf_token,
                'signup_enabled' => (bool)Configuration::get('BC_PROTECTFORM_SIGNUP'),
                'login_enabled' => (bool)Configuration::get('BC_PROTECTFORM_LOGIN'),
            )
        ));

        // If there was a CSRF error, restore form data from cookie
        if (isset($this->context->cookie->bc_form_data)) {
            $formData = json_decode($this->context->cookie->bc_form_data, true);
            if ($formData) {
                Media::addJsDef(array(
                    'bcFormData' => $formData
                ));
            }
            // Clear the stored form data
            unset($this->context->cookie->bc_form_data);
            $this->context->cookie->write();
        }

        // Display error message if exists
        if (isset($this->context->cookie->bc_csrf_error)) {
            if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
                $this->context->controller->warning[] = $this->context->cookie->bc_csrf_error;
            } else {
                $this->context->controller->errors[] = $this->context->cookie->bc_csrf_error;
            }
            unset($this->context->cookie->bc_csrf_error);
            $this->context->cookie->write();
        }
    }

    private function validateCsrfToken()
    {
        $token = Tools::getValue('bc_csrf_token');
        if (!$token || $token !== $this->context->cookie->bc_csrf_token) {
            return false;
        }
        return true;
    }

    private function handleInvalidToken($message)
    {
        // Store form data in cookie
        $formData = array();
        foreach ($_POST as $key => $value) {
            if ($key !== 'bc_csrf_token' && $key !== 'password') {
                $formData[$key] = $value;
            }
        }
        $this->context->cookie->bc_form_data = json_encode($formData);
        
        // Store error message
        $this->context->cookie->bc_csrf_error = $this->l($message);
        $this->context->cookie->write();

        // Redirect to the same page to show the error
        Tools::redirect($_SERVER['HTTP_REFERER'] ?? $this->context->link->getPageLink('index'));
        exit;
    }

    // Signup form validation (all versions)
    public function hookActionObjectCustomerAddBefore($params)
    {
        if (!Configuration::get('BC_PROTECTFORM_SIGNUP')) {
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->handleInvalidToken('Security token is invalid. Please try submitting the form again.');
        }
    }

    // Login form validation (PS 1.7+)
    public function hookActionCustomerLoginBefore($params)
    {
        if (!Configuration::get('BC_PROTECTFORM_LOGIN')) {
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->handleInvalidToken('Security token is invalid. Please try logging in again.');
        }
    }

    // Login form validation (PS 1.6)
    public function hookActionBeforeAuthentication($params)
    {
        if (!Configuration::get('BC_PROTECTFORM_LOGIN')) {
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->handleInvalidToken('Security token is invalid. Please try logging in again.');
        }
    }
} 