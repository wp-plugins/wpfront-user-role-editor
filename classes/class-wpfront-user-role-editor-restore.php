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


if (!class_exists('WPFront_User_Role_Editor_Restore')) {

    /**
     * Restore Role
     *
     * @author Syam Mohan <syam@wpfront.com>
     * @copyright 2014 WPFront.com
     */
    class WPFront_User_Role_Editor_Restore {

        const MENU_SLUG = 'wpfront-user-role-editor-restore';

        private $main;
        private $roles;

        function __construct($main) {
            $this->main = $main;
        }

        public function ajax_register() {
            add_action('wp_ajax_wpfront_user_role_editor_restore_role', array($this, 'restore_role_callback'));
        }

        private function can_edit() {
            return $this->main->current_user_can('edit_roles');
        }

        public function restore_role() {
            if (!$this->can_edit()) {
                $this->main->permission_denied();
                return;
            }

            global $wp_roles;
            $site_roles = $wp_roles->role_names;

            foreach (WPFront_User_Role_Editor::$DEFAULT_ROLES as $value) {
                $text = $this->__(ucfirst($value));
                if (array_key_exists($value, $site_roles))
                    $text = $site_roles[$value];

                $this->roles[$value] = $text;
            }

            include($this->main->pluginDIR() . 'templates/restore-role.php');
        }

        public function restore_role_callback() {
            $result = FALSE;
            $message = 'Unexpected error while restoring role.';

            if (empty($_POST['role'])) {
                $message = 'Role not found.';
            } elseif (!$this->can_edit()) {
                $message = 'Permission denied.';
            } else {
                $role_name = $_POST['role'];
                if (!in_array($role_name, WPFront_User_Role_Editor::$DEFAULT_ROLES)) {
                    $message = 'Role not found.';
                } else {
                    $role = get_role($role_name);
                    if ($role == NULL)
                        $role = add_role($role_name, $this->__(ucfirst($role_name)));
                    if ($role != NULL) {

                        foreach (WPFront_User_Role_Editor::$STANDARD_CAPABILITIES as $group => $caps) {
                            foreach ($caps as $cap => $roles) {
                                if (in_array($role->name, $roles))
                                    $role->add_cap($cap);
                                else
                                    $role->remove_cap($cap);
                            }
                        }

                        foreach (WPFront_User_Role_Editor::$DEPRECATED_CAPABILITIES as $group => $caps) {
                            foreach ($caps as $cap => $roles) {
                                if (in_array($role->name, $roles))
                                    $role->add_cap($cap);
                                else
                                    $role->remove_cap($cap);
                            }
                        }

                        if ($this->main->remove_nonstandard_capabilities_restore()) {
                            foreach (WPFront_User_Role_Editor::$ROLE_CAPS as $value) {
                                $role->remove_cap($value);
                            }

                            $this->main->get_capabilities();
                            foreach (WPFront_User_Role_Editor::$OTHER_CAPABILITIES as $group => $caps) {
                                foreach ($caps as $cap) {
                                    $role->remove_cap($cap);
                                }
                            }
                        }

                        if ($role->name == 'administrator' && $this->main->enable_role_capabilities()) {
                            foreach (WPFront_User_Role_Editor::$ROLE_CAPS as $value) {
                                $role->add_cap($value);
                            }
                        }
                        
                        $result = TRUE;
                        $message = '';
                    }
                }
            }

            echo sprintf('{ "result": %s, "message": "%s" }', $result ? 'true' : 'false', $this->__('ERROR') . ': ' . $this->__($message));
            die();
        }

        private function __($s) {
            return $this->main->__($s);
        }

        private function image_url() {
            return $this->main->pluginURL() . 'images/';
        }

    }

}
