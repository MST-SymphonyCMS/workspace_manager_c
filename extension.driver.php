<?php

require EXTENSIONS . '/workspace_manager_c/lib/class.helpers.php';

use workspace_manager_c\Helpers as Helpers;

Class extension_Workspace_manager_c extends Extension
{
    const ID = 'workspace_manager_c';

    public function __construct()
    {
        parent::__construct();
        $this->settings = (object)Symphony::Configuration()->get(self::ID);
    }

    public function install()
    {
        $this->config();
    }

    public function update()
    {
        $this->config();
    }

    function config()
    {
        Symphony::Configuration()->set('font_family', 'Monaco', self::ID);
        Symphony::Configuration()->set('font_size', '8.4pt', self::ID);
        Symphony::Configuration()->set('line_height', '148%', self::ID);
        Symphony::Configuration()->write();
    }

    /*
     * Set naviagtion
     */
    public function fetchNavigation()
    {
        $children = array(
            array(
                'relative' => false,
                'link' => 'workspace/manager/',
                'name' => 'Home',
                'visible' => 'yes'
            )
        );
        $entries = scandir(WORKSPACE);
        foreach ($entries as $entry) {
            if ($entry == '.' or $entry == '..') continue;
            if (is_dir(WORKSPACE . '/' . $entry)) {
                array_push($children,
                    array(
                        'relative' => false,
                        'link' => '/workspace/manager/' . $entry . '/',
                        'name' => Helpers::capitalizeWords($entry),
                        'visible' => 'yes'
                    )
                );
            }
        }
        return array(
            array(
                'name' => 'Workspace',
                'type' => 'structure',
                'index' => '250',
                'children' => $children
            )
        );
    }


// Delegates ***************************

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/all/',
                'delegate' => 'ModifySymphonyLauncher',
                'callback' => 'modifyLauncher'
            ),
            array(
                'page' => '/backend/',
                'delegate' => 'AdminPagePreGenerate',
                'callback' => 'adminPagePreGenerate'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'AddCustomPreferenceFieldsets',
                'callback' => 'appendPreferences'
            )/*,
            array(
                'page' => '/system/preferences/',
                'delegate' => 'Save',
                'callback' => 'savePreferences'
            )*/
        );
    }

    public function modifyLauncher()
    {
        $page = trim($_GET['symphony-page'], '/');
        if (!$page) return;

        if ($offset = $this->startsWith($page, 'blueprints/pages/template')) {
            $new_page = 'view/template' . substr($page, $offset);
        } elseif ($offset = $this->startsWith($page, 'workspace/editorframe')) {
            $new_page = 'editorframe';
        } elseif ($offset = $this->startsWith($page, 'workspace/manager')) {
            $new_page = (isset($_POST['ajax']) ? 'ajax/' : 'view/') . 'manager/' . substr($page, $offset);
        } elseif ($offset = $this->startsWith($page, 'workspace/editor')) {
            $new_page = (isset($_POST['ajax']) ? 'ajax/' : 'view/') . 'editor/' . substr($page, $offset);
        } else {
            return;
        }

        $_GET['symphony-page'] = '/extension/workspace_manager_c/' . $new_page;
    }

    function startsWith($main_string, $test_string)
    {
        $length = strlen($test_string);
        return (strncmp($main_string, $test_string, $length) == 0) ? $length : false;
    }

    /**
    * Modify admin pages.
    */
    public function adminPagePreGenerate($context)
    {
        $page = $context['oPage'];
        $callback = Symphony::Engine()->getPageCallback();
        $driver = $callback['driver'];
        if ($driver == "blueprintspages") {
            //echo var_dump($callback['context']); die;
            if ($callback['context']['action'] == 'edit') {
                $template = PageManager::fetchPageByID($callback['context']['id']);
                //echo var_dump($template); die;
                $ul = $page->Context->getChildByName('ul', 0);
                $ul->prependChild(
                    new XMLElement(
                        'li',
                        Widget::Anchor(
                            __('Edit Page Template'),
                            SYMPHONY_URL . '/blueprints/pages/template/'
                            . $template['handle'] . '/',
                            'Edit Page Template',
                            'button'
                        )
                    )
                );
            } elseif ($table = $page->Form->getChildByName('table', 0)) {
                $tbody = $table->getChildByName('tbody', 0);
                foreach ($tbody->getChildren() as $tr) {
                    $td = $tr->getChild(1);
                    if ($td) {
                        $value = $td->getValue();
                        $td->replaceValue(
                            Widget::Anchor(
                                __($value),
                                SYMPHONY_URL . '/blueprints/pages/template/' . pathinfo($value, PATHINFO_FILENAME) . '/'
                            )
                        );
                    }
                }
            }
        }
    }

    public function appendPreferences($context)
    {
        $mode = strtolower($this->settings->mode);

        $fieldset = new XMLElement(
            'fieldset',
            new XMLElement('legend', 'Workspace Manager C'),
            array('class' => 'settings')
        );

        $two_columns = new XMLElement('div', null, array('class' => 'two columns'));
        $two_columns->appendChild(
            Widget::Label(
                __('Font Family'),
                Widget::Input(
                    'settings[' . self::ID . '][font_family]', $this->settings->font_family
                ),
                null, null,
                array('class' => 'column')
            )
        );
        $two_columns->appendChild(
            Widget::Label(
                __('Font Size'),
                Widget::Input(
                    'settings[' . self::ID . '][font_size]', $this->settings->font_size
                ),
                null, null,
                array('class' => 'column')
            )
        );
        $fieldset->appendChild($two_columns);

        $one_column = new XMLElement('div', null, array('class' => 'column'));
        $one_column->appendChild(
            Widget::Label(
                __('Line Height'),
                Widget::Input(
                    'settings[' . self::ID . '][line_height]', $this->settings->line_height
                ),
                null, null,
                array('class' => 'column')
            )
        );
        $fieldset->appendChild($one_column);
        $context['wrapper']->appendChild($fieldset);
    }
}