<?php

/*
  WPFront User Role Editor Plugin
  Copyright (C) 2014, WPFront.com
  Website: wpfront.com
  Contact: syam@wpfront.com

  WPFront User Role Editor Plugin is distributed under the GNU General Public License, Version 3,
  June 2007. Copyright (C) 2007 Free Software Foundation, Inc., 51 Franklin
  St, Fifth Floor, Boston, MA 02110, USA

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once("base/class-wpfront-base.php");
require_once("class-wpfront-user-role-editor-options.php");

if (!class_exists('WPFront_User_Role_Editor')) {

    /**
     * Main class of WPFront User Role Editor Plugin
     *
     * @author Syam Mohan <syam@wpfront.com>
     * @copyright 2014 WPFront.com
     */
    class WPFront_User_Role_Editor extends WPFront_Base {

        //Constants
        const VERSION = '0.3.1';
        const OPTIONS_GROUP_NAME = 'wpfront-user-role-editor-options-group';
        const OPTION_NAME = 'wpfront-user-role-editor-options';
        const PLUGIN_SLUG = 'wpfront-user-role-editor';

        //Variables
        protected $options;
        protected $role_caps = array('wpfront_list_roles', 'wpfront_create_roles', 'wpfront_edit_roles', 'wpfront_delete_roles');
        protected $builtin_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        protected $delete_roles;

        function __construct() {
            parent::__construct(__FILE__, self::PLUGIN_SLUG);

            $this->add_menu($this->__('WPFront User Role Editor'), $this->__('User Role Editor'));
        }

        public function admin_init() {
            register_setting(self::OPTIONS_GROUP_NAME, self::OPTION_NAME);

            add_action('wp_ajax_wpfront_user_role_editor_update_options', array($this, 'update_options_callback'));
            add_action('wp_ajax_wpfront_user_role_editor_copy_capabilities', array($this, 'copy_capabilities_callback'));
        }

        public function get_capability_string($capability) {
            if ($this->options->enable_role_capabilities())
                return 'wpfront_' . $capability . '_roles';

            return $capability . '_users';
        }

        public function admin_menu() {
            parent::admin_menu();

            $menu_slug = self::PLUGIN_SLUG . '-all-roles';
            add_menu_page($this->__('Roles'), $this->__('Roles'), $this->get_capability_string('list'), $menu_slug, null, $this->pluginURL() . 'images/roles_menu.png', '69.9999');

            $page_hook_suffix = add_submenu_page($menu_slug, $this->__('Roles'), $this->__('All Roles'), $this->get_capability_string('list'), $menu_slug, array($this, 'list_roles'));
            add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'enqueue_scripts'));
            add_action('admin_print_styles-' . $page_hook_suffix, array($this, 'enqueue_styles'));

            $page_hook_suffix = add_submenu_page($menu_slug, $this->__('Add New Role'), $this->__('Add New'), $this->get_capability_string('create'), self::PLUGIN_SLUG . '-add-new', array($this, 'add_new_role'));
            add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'enqueue_scripts'));
            add_action('admin_print_styles-' . $page_hook_suffix, array($this, 'enqueue_styles'));
        }

        //add scripts
        public function enqueue_scripts() {
//            $jsRoot = $this->pluginURLRoot . 'js/';
            
            wp_enqueue_script('jquery');
        }

        //add styles
        public function enqueue_styles() {
//            $cssRoot = $this->pluginURLRoot . 'css/';
        }

        //options page scripts
        public function enqueue_options_scripts() {
            $this->enqueue_scripts();
        }

        //options page styles
        public function enqueue_options_styles() {
            $this->enqueue_styles();
            
            $styleRoot = $this->pluginURLRoot . 'css/';
            wp_enqueue_style('wpfront-user-role-editor-options', $styleRoot . 'options.css', array(), self::VERSION);
        }

        public function plugins_loaded() {
            //load plugin options
            $this->reload_option();
        }

        private function reload_option() {
            $this->options = new WPFront_User_Role_Editor_Options(self::OPTION_NAME, self::PLUGIN_SLUG);
        }

        public function options_page() {
            if (!current_user_can('manage_options')) {
                wp_die($this->__('You do not have sufficient permissions to access this page.'));
                return;
            }

            include($this->pluginDIRRoot . 'templates/options-template.php');
        }

        public function update_options_callback() {
            check_ajax_referer($_POST['referer'], 'nonce');

            $options = array();
            if (!empty($_POST[self::OPTION_NAME]))
                $options = $_POST[self::OPTION_NAME];
            update_option(self::OPTION_NAME, $options);

            $this->reload_option();

            if ($this->options->enable_role_capabilities()) {
                $role_admin = get_role('administrator');
                foreach ($this->role_caps as $value) {
                    $role_admin->add_cap($value, TRUE);
                }
            } else {
                global $wp_roles;
                foreach ($wp_roles->role_objects as $key => $role) {
                    foreach ($this->role_caps as $value) {
                        $role->remove_cap($value);
                    }
                }
            }

            echo admin_url('admin.php?page=' . self::PLUGIN_SLUG . '&settings-updated=true');
            die();
        }

        public function list_roles() {
            $no_access = FALSE;

            if (!current_user_can($this->get_capability_string('list'))) {
                $no_access = TRUE;
            }

            if (!empty($_GET['edit_role'])) {
                if (!current_user_can($this->get_capability_string('edit'))) {
                    $no_access = TRUE;
                } else {
                    $edit_role = $_GET['edit_role'];
                    if (get_role($edit_role) == NULL) {
                        $no_access = TRUE;
                    } else {
                        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
                            $roles = get_editable_roles();
                            $no_access = !array_key_exists($edit_role, $roles);
                            if ($edit_role == 'administrator')
                                $no_access = TRUE;
                        }
                    }
                }

                if (!$no_access) {
                    include($this->pluginDIRRoot . 'templates/add-edit-role.php');
                    return;
                }
            } else if (!empty($_GET['delete_role'])) {
                if (!current_user_can($this->get_capability_string('delete'))) {
                    $no_access = TRUE;
                } else {
                    $delete_role = $_GET['delete_role'];
                    if (get_role($delete_role) == NULL) {
                        $no_access = TRUE;
                    } else {
                        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
                            $roles = get_editable_roles();
                            $no_access = !array_key_exists($delete_role, $roles);
                            if ($delete_role == 'administrator')
                                $no_access = TRUE;
                        }
                    }
                    $this->delete_roles = array($delete_role);
                }

                if (!$no_access) {
                    include($this->pluginDIRRoot . 'templates/delete-role.php');
                    return;
                }
            } else if ((!empty($_POST['doaction_top']) && !empty($_POST['action_top'])) || (!empty($_POST['doaction_bottom']) && !empty($_POST['action_bottom']))) {
                $action = $_POST['action_top'] == "-1" ? $_POST['action_bottom'] : $_POST['action_top'];
                switch ($action) {
                    case 'delete':
                        $this->delete_roles = array();
                        global $wp_roles;
                        foreach ($wp_roles->role_names as $role => $name) {
                            if (!empty($_POST['cb-select-' . $role]))
                                $this->delete_roles[] = $role;
                        }

                        if (!empty($this->delete_roles)) {
                            include($this->pluginDIRRoot . 'templates/delete-role.php');
                            return;
                        }
                }
            } else if (!empty($_POST['confirm-delete'])) {
                include($this->pluginDIRRoot . 'templates/delete-role.php');
                return;
            }

            if ($no_access) {
                wp_die($this->__('You do not have sufficient permissions to access this page.'));
                return;
            }

            include($this->pluginDIRRoot . 'templates/list-roles.php');
        }

        public function add_new_role() {
            if (!current_user_can($this->get_capability_string('create'))) {
                wp_die($this->__('You do not have sufficient permissions to access this page.'));
                return;
            }

            include($this->pluginDIRRoot . 'templates/add-edit-role.php');
        }

        public function copy_capabilities_callback() {
            if (empty($_POST['role'])) {
                echo '{}';
                die();
                return;
            }

            $role = get_role($_POST['role']);
            if ($role == NULL) {
                echo '{}';
                die();
                return;
            }

            echo json_encode($role->capabilities);
            die();
        }

        protected function create_nonce() {
            if (empty($_SERVER['REQUEST_URI'])) {
                wp_die($this->__('You do not have sufficient permissions to access this page.'));
                exit;
            }
            $referer = $_SERVER['REQUEST_URI'];
            echo '<input type = "hidden" name = "_wpnonce" value = "' . wp_create_nonce($referer) . '" />';
            echo '<input type = "hidden" name = "_wp_http_referer" value = "' . $referer . '" />';
        }

        protected function verify_nonce() {
            if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
                $flag = TRUE;
                if (empty($_POST['_wpnonce'])) {
                    $flag = FALSE;
                } else if (empty($_POST['_wp_http_referer'])) {
                    $flag = FALSE;
                } else if (!wp_verify_nonce($_POST['_wpnonce'], $_POST['_wp_http_referer'])) {
                    $flag = FALSE;
                }

                if (!$flag) {
                    wp_die($this->__('You do not have sufficient permissions to access this page.'));
                    exit;
                }
            }
        }

    }

}