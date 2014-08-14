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
require_once("class-wpfront-user-role-editor-list.php");
require_once("class-wpfront-user-role-editor-add-edit.php");
require_once("class-wpfront-user-role-editor-delete.php");
require_once("class-wpfront-user-role-editor-restore.php");

if (!class_exists('WPFront_User_Role_Editor')) {

    /**
     * Main class of WPFront User Role Editor Plugin
     *
     * @author Syam Mohan <syam@wpfront.com>
     * @copyright 2014 WPFront.com
     */
    class WPFront_User_Role_Editor extends WPFront_Base {

        //Constants
        const VERSION = '1.2';
        const OPTIONS_GROUP_NAME = 'wpfront-user-role-editor-options-group';
        const OPTION_NAME = 'wpfront-user-role-editor-options';
        const PLUGIN_SLUG = 'wpfront-user-role-editor';

        public static $ROLE_CAPS = array('wpfront_list_roles', 'wpfront_create_roles', 'wpfront_edit_roles', 'wpfront_delete_roles');
        public static $DEFAULT_ROLES = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        public static $STANDARD_CAPABILITIES = array(
            'Dashboard' => array(
                'read' => array('administrator', 'editor', 'author', 'contributor', 'subscriber'),
                'edit_dashboard' => array('administrator')
            ),
            'Posts' => array(
                'publish_posts' => array('administrator', 'editor', 'author'),
                'edit_posts' => array('administrator', 'editor', 'author', 'contributor'),
                'delete_posts' => array('administrator', 'editor', 'author', 'contributor'),
                'edit_published_posts' => array('administrator', 'editor', 'author'),
                'delete_published_posts' => array('administrator', 'editor', 'author'),
                'edit_others_posts' => array('administrator', 'editor'),
                'delete_others_posts' => array('administrator', 'editor'),
                'read_private_posts' => array('administrator', 'editor'),
                'edit_private_posts' => array('administrator', 'editor'),
                'delete_private_posts' => array('administrator', 'editor'),
                'manage_categories' => array('administrator', 'editor')
            ),
            'Media' => array(
                'upload_files' => array('administrator', 'editor', 'author'),
                'unfiltered_upload' => array('administrator')
            ),
            'Pages' => array(
                'publish_pages' => array('administrator', 'editor'),
                'edit_pages' => array('administrator', 'editor'),
                'delete_pages' => array('administrator', 'editor'),
                'edit_published_pages' => array('administrator', 'editor'),
                'delete_published_pages' => array('administrator', 'editor'),
                'edit_others_pages' => array('administrator', 'editor'),
                'delete_others_pages' => array('administrator', 'editor'),
                'read_private_pages' => array('administrator', 'editor'),
                'edit_private_pages' => array('administrator', 'editor'),
                'delete_private_pages' => array('administrator', 'editor')
            ),
            'Comments' => array(
                'edit_comment' => array(),
                'moderate_comments' => array('administrator', 'editor')
            ),
            'Themes' => array(
                'switch_themes' => array('administrator'),
                'edit_theme_options' => array('administrator'),
                'edit_themes' => array('administrator'),
                'delete_themes' => array('administrator'),
                'install_themes' => array('administrator'),
                'update_themes' => array('administrator')
            ),
            'Plugins' => array(
                'activate_plugins' => array('administrator'),
                'edit_plugins' => array('administrator'),
                'install_plugins' => array('administrator'),
                'update_plugins' => array('administrator'),
                'delete_plugins' => array('administrator')
            ),
            'Users' => array(
                'list_users' => array('administrator'),
                'create_users' => array('administrator'),
                'edit_users' => array('administrator'),
                'delete_users' => array('administrator'),
                'promote_users' => array('administrator'),
                'add_users' => array('administrator'),
                'remove_users' => array('administrator')
            ),
            'Tools' => array(
                'import' => array('administrator'),
                'export' => array('administrator')
            ),
            'Admin' => array(
                'manage_options' => array('administrator'),
                'update_core' => array('administrator'),
                'unfiltered_html' => array('administrator', 'editor')
            ),
            'Links' => array(
                'manage_links' => array('administrator', 'editor')
            )
        );
        public static $DEPRECATED_CAPABILITIES = array(
            'Deprecated' => array(
                'edit_files' => array('administrator'),
                'level_0' => array('administrator', 'editor', 'author', 'contributor', 'subscriber'),
                'level_1' => array('administrator', 'editor', 'author', 'contributor'),
                'level_2' => array('administrator', 'editor', 'author'),
                'level_3' => array('administrator', 'editor'),
                'level_4' => array('administrator', 'editor'),
                'level_5' => array('administrator', 'editor'),
                'level_6' => array('administrator', 'editor'),
                'level_7' => array('administrator', 'editor'),
                'level_8' => array('administrator'),
                'level_9' => array('administrator'),
                'level_10' => array('administrator')
            )
        );
        public static $OTHER_CAPABILITIES = array(
            'Other Capabilities' => array(
            )
        );
        private static $CAPABILITIES = NULL;
        //Variables
        protected $options;

        function __construct() {
            parent::__construct(__FILE__, self::PLUGIN_SLUG);

            $this->add_menu($this->__('WPFront User Role Editor'), $this->__('User Role Editor'));
        }

        public function plugins_loaded() {
            //load plugin options
            $this->reload_option();
        }

        private function reload_option() {
            $this->options = new WPFront_User_Role_Editor_Options(self::OPTION_NAME, self::PLUGIN_SLUG);
        }

        public function admin_init() {
            register_setting(self::OPTIONS_GROUP_NAME, self::OPTION_NAME);

            add_action('wp_ajax_wpfront_user_role_editor_update_options', array($this, 'update_options_callback'));

            $add_new = new WPFront_User_Role_Editor_Add_Edit($this);
            $add_new->ajax_register();
            
            $restore = new WPFront_User_Role_Editor_Restore($this);
            $restore->ajax_register();
        }

        public function admin_menu() {
            parent::admin_menu();

            $menu_slug = WPFront_User_Role_Editor_List::MENU_SLUG;
            add_menu_page($this->__('Roles'), $this->__('Roles'), $this->get_capability_string('list'), $menu_slug, null, $this->pluginURL() . 'images/roles_menu.png', '69.9999');

            $page_hook_suffix = add_submenu_page($menu_slug, $this->__('Roles'), $this->__('All Roles'), $this->get_capability_string('list'), $menu_slug, array(new WPFront_User_Role_Editor_List($this), 'list_roles'));
            add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'enqueue_role_scripts'));
            add_action('admin_print_styles-' . $page_hook_suffix, array($this, 'enqueue_role_styles'));

            $page_hook_suffix = add_submenu_page($menu_slug, $this->__('Add New Role'), $this->__('Add New'), $this->get_capability_string('create'), WPFront_User_Role_Editor_Add_Edit::MENU_SLUG, array(new WPFront_User_Role_Editor_Add_Edit($this), 'add_edit_role'));
            add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'enqueue_role_scripts'));
            add_action('admin_print_styles-' . $page_hook_suffix, array($this, 'enqueue_role_styles'));

            $page_hook_suffix = add_submenu_page($menu_slug, $this->__('Restore Role'), $this->__('Restore'), $this->get_capability_string('edit'), WPFront_User_Role_Editor_Restore::MENU_SLUG, array(new WPFront_User_Role_Editor_Restore($this), 'restore_role'));
            add_action('admin_print_scripts-' . $page_hook_suffix, array($this, 'enqueue_role_scripts'));
            add_action('admin_print_styles-' . $page_hook_suffix, array($this, 'enqueue_role_styles'));
        }

        //add scripts
        public function enqueue_role_scripts() {
//            $jsRoot = $this->pluginURLRoot . 'js/';

            wp_enqueue_script('jquery');
        }

        //add styles
        public function enqueue_role_styles() {
            wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css', array(), '4.1.0');
            $styleRoot = $this->pluginURLRoot . 'css/';
            wp_enqueue_style('wpfront-user-role-editor-styles', $styleRoot . 'style.css', array(), self::VERSION);
        }

        //options page scripts
        public function enqueue_options_scripts() {
            $this->enqueue_role_scripts();
        }

        //options page styles
        public function enqueue_options_styles() {
            $this->enqueue_role_styles();

            $styleRoot = $this->pluginURLRoot . 'css/';
            wp_enqueue_style('wpfront-user-role-editor-options', $styleRoot . 'options.css', array(), self::VERSION);
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
                foreach (self::$ROLE_CAPS as $value) {
                    $role_admin->add_cap($value, TRUE);
                }
            } else {
                global $wp_roles;
                foreach ($wp_roles->role_objects as $key => $role) {
                    foreach (self::$ROLE_CAPS as $value) {
                        $role->remove_cap($value);
                    }
                }
            }

            echo admin_url('admin.php?page=' . self::PLUGIN_SLUG . '&settings-updated=true');
            die();
        }

        public function get_capability_string($capability) {
            if ($this->options->enable_role_capabilities())
                return 'wpfront_' . $capability . '_roles';

            return $capability . '_users';
        }

        public function permission_denied() {
            wp_die($this->__('You do not have sufficient permissions to access this page.'));
        }

        public function current_user_can($capability) {
            switch ($capability) {
                case 'list_roles':
                    return current_user_can($this->get_capability_string('list'));
                case 'edit_roles':
                    return current_user_can($this->get_capability_string('edit'));
                case 'delete_roles':
                    return current_user_can($this->get_capability_string('delete'));
                case 'create_roles':
                    return current_user_can($this->get_capability_string('create'));
                default :
                    return current_user_can($capability);
            }
        }

        public function create_nonce() {
            if (empty($_SERVER['REQUEST_URI'])) {
                $this->permission_denied();
                exit;
                return;
            }
            $referer = $_SERVER['REQUEST_URI'];
            echo '<input type = "hidden" name = "_wpnonce" value = "' . wp_create_nonce($referer) . '" />';
            echo '<input type = "hidden" name = "_wp_http_referer" value = "' . $referer . '" />';
        }

        public function verify_nonce() {
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
                    $this->permission_denied();
                    exit;
                }
            }
        }

        public function footer() {
            ?>
            <div class="footer">
                <a target="_blank" href="http://wpfront.com/contact/"><?php echo $this->__('Feedback'); ?></a> 
                |
                <a target="_blank" href="http://wpfront.com/donate/"><?php echo $this->__('Buy me a Beer'); ?></a> 
            </div>
            <?php
        }

        public function get_capabilities() {
            if (self::$CAPABILITIES != NULL)
                return self::$CAPABILITIES;

            self::$CAPABILITIES = array();

            foreach (self::$STANDARD_CAPABILITIES as $key => $value) {
                self::$CAPABILITIES[$key] = array();
                foreach ($value as $cap => $roles) {
                    self::$CAPABILITIES[$key][] = $cap;
                }
            }

            foreach (self::$DEPRECATED_CAPABILITIES as $key => $value) {
                self::$CAPABILITIES[$key] = array();
                foreach ($value as $cap => $roles) {
                    self::$CAPABILITIES[$key][] = $cap;
                }
            }

            if ($this->options->enable_role_capabilities())
                self::$CAPABILITIES['Roles (WPFront)'] = self::$ROLE_CAPS;

            reset(self::$OTHER_CAPABILITIES);
            $other_key = key(self::$OTHER_CAPABILITIES);

            global $wp_roles;
            foreach ($wp_roles->roles as $key => $role) {
                foreach ($role['capabilities'] as $cap => $value) {
                    $found = FALSE;
                    foreach (self::$CAPABILITIES as $g => $wcaps) {
                        if (in_array($cap, $wcaps)) {
                            $found = TRUE;
                            break;
                        }
                    }
                    if (!$found && !in_array($cap, self::$OTHER_CAPABILITIES[$other_key])) {
                        self::$OTHER_CAPABILITIES[$other_key][] = $cap;
                    }
                }
            }

            foreach (self::$OTHER_CAPABILITIES as $key => $value) {
                if (count($value) > 0)
                    self::$CAPABILITIES[$key] = $value;
            }

            return self::$CAPABILITIES;
        }

        public function display_deprecated() {
            return $this->options->display_deprecated();
        }
        
        public function enable_role_capabilities() {
            return $this->options->enable_role_capabilities();
        }
        
        public function remove_nonstandard_capabilities_restore() {
            return $this->options->remove_nonstandard_capabilities_restore();
        }

    }

}