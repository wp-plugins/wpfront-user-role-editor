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

/**
 * Template for WPFront User Role Editor Add Edit Role
 *
 * @author Syam Mohan <syam@wpfront.com>
 * @copyright 2014 WPFront.com
 */
?>

<?php
$this->verify_nonce();

$edit_role = null;
$disabled = FALSE;
if (!empty($_GET['edit_role'])) {
    $edit_role = get_role($_GET['edit_role']);
    if ($edit_role->name == 'administrator') {
        $disabled = TRUE;
    }
}

$valid_role_name = TRUE;
$valid_display_name = TRUE;
if (strtolower($_SERVER['REQUEST_METHOD']) === 'post' && !empty($_POST['createrole'])) {
    if ($edit_role == NULL) {
        if (empty($_POST['role_name'])) {
            $valid_role_name = FALSE;
        } else if (trim($_POST['role_name']) == '') {
            $valid_role_name = FALSE;
        }
    }

    if (empty($_POST['display_name'])) {
        $valid_display_name = FALSE;
    } else if (trim($_POST['display_name']) == '') {
        $valid_display_name = FALSE;
    }
}

$url = admin_url('admin.php');

$capabilities = array(
    'Dashboard' => array(
        'read',
        'edit_dashboard'
    ),
    'Posts' => array(
        'publish_posts',
        'edit_posts',
        'delete_posts',
        'edit_published_posts',
        'delete_published_posts',
        'edit_others_posts',
        'delete_others_posts',
        'read_private_posts',
        'edit_private_posts',
        'delete_private_posts',
        'manage_categories'
    ),
    'Media' => array(
        'upload_files',
        'unfiltered_upload'
    ),
    'Pages' => array(
        'publish_pages',
        'edit_pages',
        'delete_pages',
        'edit_published_pages',
        'delete_published_pages',
        'edit_others_pages',
        'delete_others_pages',
        'read_private_pages',
        'edit_private_pages',
        'delete_private_pages'
    ),
    'Comments' => array(
        'edit_comment',
        'moderate_comments'
    ),
    'Themes' => array(
        'switch_themes',
        'edit_theme_options',
        'edit_themes',
        'delete_themes',
        'install_themes',
        'update_themes'
    ),
    'Plugins' => array(
        'activate_plugins',
        'edit_plugins',
        'install_plugins',
        'update_plugins',
        'delete_plugins'
    ),
    'Users' => array(
        'list_users',
        'create_users',
        'edit_users',
        'delete_users',
        'promote_users',
        'add_users',
        'remove_users'
    ),
    'Tools' => array(
        'import',
        'export'
    ),
    'Admin' => array(
        'manage_options',
        'update_core',
        'unfiltered_html'
    ),
    'Links' => array(
        'manage_links'
    ),
    'Deprecated' => array(
        'edit_files',
        'level_0',
        'level_1',
        'level_2',
        'level_3',
        'level_4',
        'level_5',
        'level_6',
        'level_7',
        'level_8',
        'level_9',
        'level_10'
    )
);
if ($this->options->enable_role_capabilities()) {
    $capabilities['Roles (WPFront)'] = $this->role_caps;
}

global $wp_roles;
$other_caps = array();
foreach ($wp_roles->roles as $key => $role) {
    foreach ($role['capabilities'] as $cap => $value) {
        $found = FALSE;
        foreach ($capabilities as $s => $wcaps) {
            foreach ($wcaps as $wcap) {
                if ($wcap == $cap) {
                    $found = TRUE;
                    break;
                }
            }
            if ($found)
                break;
        }
        if (!$found) {
            $other_caps[] = $cap;
        }
    }
}

$other_caps = array_unique($other_caps);
if (!empty($other_caps)) {
    $capabilities['Other Capabilities'] = $other_caps;
}

$role_error = FALSE;
$role_exists = FALSE;
$role_name = '';
$display_name = '';
$role_success = FALSE;
$post_capabilities = array();

if ($edit_role != NULL) {
    global $wp_roles;
    $role_name = $edit_role->name;
    $display_name = $wp_roles->role_names[$edit_role->name];
    $post_capabilities = $edit_role->capabilities;
}

if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
    $post_capabilities = array();
    if (!empty($_POST['capabilities'])) {
        $post_capabilities = $_POST['capabilities'];
        foreach ($post_capabilities as $key => $value) {
            $post_capabilities[$key] = TRUE;
        }
    }
    if ($valid_role_name && $valid_display_name) {
        $display_name = trim($_POST['display_name']);

        if ($edit_role == NULL) {
            $role_name = trim($_POST['role_name']);
            $role_name = strtolower($role_name);
            $role_name = str_replace(' ', '_', $role_name);
            $role_name = preg_replace('/\W/', '', $role_name);
            if ($role_name == '')
                $valid_role_name = FALSE;
        }
        else {
            $role_name = $edit_role->name;
        }
    }
    if ($valid_role_name && $valid_display_name) {
        if ($edit_role == NULL & get_role($role_name) != NULL) {
            $role_exists = TRUE;
        } else {
            foreach ($post_capabilities as $pcap => $pvalue) {
                $found = FALSE;
                foreach ($capabilities as $group => $caps) {
                    foreach ($caps as $cap) {
                        if ($cap == $pcap) {
                            $found = TRUE;
                            break;
                        }
                    }
                    if ($found)
                        break;
                }
                if (!$found) {
                    unset($post_capabilities[$pcap]);
                }
            }

            if ($edit_role == NULL) {
                $role_error = add_role($role_name, $display_name, $post_capabilities);
                if ($role_error == NULL) {
                    $role_error = TRUE;
                } else {
                    $role_error = FALSE;
                    $role_success = TRUE;
                }
            } else {
                $caps = array();
                foreach ($post_capabilities as $cap => $value) {
                    $caps[$cap] = TRUE;
                }
                global $wp_roles;
                $wp_roles->roles[$edit_role->name] = array(
                    'name' => $display_name,
                    'capabilities' => $caps
                );
                update_option($wp_roles->role_key, $wp_roles->roles);
                $wp_roles->role_objects[$edit_role->name] = new WP_Role($edit_role->name, $caps);
                $wp_roles->role_names[$edit_role->name] = $display_name;
                $role_success = TRUE;
            }
        }
    }
}

if ($role_success) {
    echo '<script>document.location = "' . admin_url('admin.php?page=' . self::PLUGIN_SLUG . '-all-roles') . '";</script>';
    exit();
    return;
}

function wpfront_user_role_editor_list_capabilities($self, $capabilities, $post_capabilities, $display_deprecated, $disabled) {
    ?>
    <div class="metabox-holder">
        <?php
        foreach ($capabilities as $group => $caps) {
            ?>
            <div class="postbox <?php echo $group == 'Deprecated' ? 'deprecated' : 'active'; ?> <?php echo $group == 'Deprecated' && !$display_deprecated ? 'hidden' : ''; ?>">
                <h3 class="hndle">
                    <input type="checkbox" class="select-all" id="<?php echo str_replace(' ', '_', $group); ?>" <?php echo $disabled || $group == 'Deprecated' ? 'disabled' : ''; ?> />
                    <label for="<?php echo str_replace(' ', '_', $group); ?>"><?php echo $self->__($group); ?></label>
                </h3>
                <div class="inside">
                    <div class="main">
                        <?php
                        foreach ($caps as $cap) {
                            ?>
                            <div>
                                <input type="checkbox" id="<?php echo $cap; ?>" name="capabilities[<?php echo $cap; ?>]" <?php echo array_key_exists($cap, $post_capabilities) ? 'checked' : ''; ?> <?php echo $disabled || $group == 'Deprecated' ? 'disabled' : ''; ?> />
                                <label for="<?php echo $cap; ?>"><?php echo $cap; ?></label>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}
?>

<style type="text/css">
    div.role-add-new form#createuser table.sub-head {
        width: 100%;
    }

    div.role-add-new form#createuser table.sub-head th.sub-head {
        text-align: left;
        padding: 0px;
    }

    div.role-add-new form#createuser table.sub-head th.sub-head h3 {
    }

    div.role-add-new form#createuser table.sub-head td.sub-head-controls {
        text-align: right;
        padding: 0px;
    }

    div.role-add-new form#createuser table.sub-head td.sub-head-controls div {
        display: inline-block;
        vertical-align: top;
    }

    div.role-add-new form#createuser table.sub-head td.sub-head-controls div.spacer {
        width: 10px;
        height: 0px;
    }

    div.role-add-new form#createuser table.sub-head td.sub-head-controls input.select-all, div.role-add-new form#createuser table.sub-head td.sub-head-controls input.select-none {
        width: 100px;
    }

    div.role-add-new div.metabox-holder div.postbox {
        margin-bottom: 8px;
    }

    div.role-add-new div.metabox-holder div.postbox.deprecated {
        filter: alpha(opacity=80);
        opacity: 0.8;
    }

    div.role-add-new div.metabox-holder div.postbox h3.hndle {
        cursor: default;
    }

    div.role-add-new div.metabox-holder div.postbox div.inside div.main div {
        padding: 2px 0px;
        display: inline-block;
        width: 250px;
    }

    div.role-add-new div.metabox-holder div.postbox label {
        vertical-align: top;
    }
    
    div.role-add-new div.footer {
        text-align: center;
    }
</style>

<div class="wrap role-add-new">
    <h2 id="add-new-role">
        <?php echo $edit_role == NULL ? $this->__('Add New Role') : $this->__('Edit Role'); ?>
        <?php
        if ($edit_role != NULL && current_user_can($this->get_capability_string('create'))) {
            ?>
            <a href="<?php echo $url . '?page=' . self::PLUGIN_SLUG . '-add-new'; ?>" class="add-new-h2"><?php echo $this->__('Add New'); ?></a>
            <?php
        }
        ?>
    </h2>
    <?php
    if ($role_exists) {
        ?>
        <div class="error below-h2">
            <p>
                <strong><?php echo $this->__('ERROR'); ?></strong>: <?php echo $this->__('This role already exists in this site.'); ?>
            </p>
        </div>
        <?php
    } else if ($role_error) {
        ?>
        <div class="error below-h2">
            <p>
                <strong><?php echo $this->__('ERROR'); ?></strong>: <?php echo $this->__('There was an unexpected error while performing this action.'); ?>
            </p>
        </div>
        <?php
    }
    ?>
    <?php
    if ($edit_role == NULL) {
        ?>
        <p><?php echo $this->__('Create a brand new role and add it to this site.'); ?></p>
        <?php
    }
    ?>
    <form method="post" id="createuser" name="createuser" class="validate">
        <?php $this->create_nonce(); ?>
        <table class="form-table">
            <tbody>
                <tr class="form-field form-required <?php echo $valid_display_name ? '' : 'form-invalid' ?>">
                    <th scope="row"><label for="display_name">
                            <?php echo $this->__('Display Name'); ?> <span class="description">(<?php echo $this->__('required'); ?>)</span></label>
                    </th>
                    <td>
                        <input name="display_name" type="text" id="display_name" value="<?php echo $display_name; ?>" aria-required="true" <?php echo $disabled ? 'disabled' : ''; ?> />
                    </td>
                </tr>
                <tr class="form-field form-required <?php echo $valid_role_name ? '' : 'form-invalid' ?>">
                    <th scope="row"><label for="role_name">
                            <?php echo $this->__('Role Name'); ?> <span class="description">(<?php echo $this->__('required'); ?>)</span></label>
                    </th>
                    <td>
                        <input name="role_name" type="text" id="role_name" value="<?php echo $role_name; ?>" aria-required="true" <?php echo $disabled || $edit_role != NULL ? 'disabled' : ''; ?> />
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="form-table sub-head">
            <tbody>
                <tr>
                    <th class="sub-head"><h3><?php echo $this->__('Capabilities'); ?></h3></th>
            <td class="sub-head-controls">
                <div>
                    <select <?php echo $disabled ? 'disabled' : ''; ?>>
                        <option value=""><?php echo $this->__('Copy from'); ?></option>
                        <?php
                        global $wp_roles;
                        $names = $wp_roles->get_names();
                        asort($names, SORT_STRING | SORT_FLAG_CASE);
                        foreach ($names as $key => $value) {
                            ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option> 
                            <?php
                        }
                        ?>
                    </select>
                    <input type="button" id="cap_apply" name="cap_apply" class="button action" value="<?php echo $this->__('Apply'); ?>" <?php echo $disabled ? 'disabled' : ''; ?> />  
                </div>
                <div class="spacer"></div>
                <div>
                    <input type="button" class="button action chk-helpers select-all" value="<?php echo $this->__('Select All'); ?>" <?php echo $disabled ? 'disabled' : ''; ?> />               
                    <input type="button" class="button action chk-helpers select-none" value="<?php echo $this->__('Select None'); ?>" <?php echo $disabled ? 'disabled' : ''; ?> />
                </div>
            </td>
            </tr>
            </tbody>
        </table>
        <?php wpfront_user_role_editor_list_capabilities($this, $capabilities, $post_capabilities, $this->options->display_deprecated(), $disabled); ?>
        <p class="submit">
            <input type="submit" name="createrole" id="createusersub" class="button button-primary" value="<?php echo $edit_role == NULL ? $this->__('Add New Role') : $this->__('Update Role'); ?>" <?php echo $disabled ? 'disabled' : ''; ?> />
        </p>
    </form>
    <div class="footer"><a target="_blank" href="http://wpfront.com/contact/">Feedback</a> | <a target="_blank" href="http://wpfront.com/">wpfront.com</a></div>
</div>

<script type="text/javascript">
    (function($) {
        function change_select_all(chk) {
            var chks = chk.closest("div.main").find("input");
            if (chks.length == chks.filter(":checked").length) {
                chk.closest("div.postbox").find("input.select-all").prop("checked", true);
            }
            else {
                chk.closest("div.postbox").find("input.select-all").prop("checked", false);
            }
        }

        $("div.role-add-new div.postbox input.select-all").click(function() {
            $(this).parent().next().find("input").prop("checked", $(this).prop("checked"));
        });

        $("div.role-add-new div.postbox div.main input").click(function() {
            change_select_all($(this));
        });

        $("div.role-add-new table.sub-head td.sub-head-controls input.chk-helpers").click(function() {
            if ($(this).hasClass('select-all')) {
                $("div.role-add-new div.postbox.active").find("input").prop("checked", true);
            }
            else if ($(this).hasClass('select-none')) {
                $("div.role-add-new div.postbox.active").find("input").prop("checked", false);
            }
        });

<?php
if ($edit_role == NULL) {
    ?>
            $("#display_name").keyup(function() {
                if ($.trim($(this).val()) == "")
                    return;
                $("#role_name").val($.trim($(this).val()).toLowerCase().replace(/ /g, "_").replace(/\W/g, ""));
            });

            $("#role_name").blur(function() {
                var ele = $(this);
                var str = $.trim(ele.val()).toLowerCase();
                str = str.replace(/ /g, "_").replace(/\W/g, "");
                ele.val(str);
                if (str != "") {
                    ele.parent().parent().removeClass("form-invalid");
                }
            });
    <?php
}
?>

        $("#display_name").blur(function() {
            if ($.trim($(this).val()) != "") {
                $(this).parent().parent().removeClass("form-invalid");
            }
            $("#role_name").blur();
        });

        $("#createusersub").click(function() {
            var role_name = $("#role_name");
            var display_name = $("#display_name");
            if ($.trim(role_name.val()) == "") {
                role_name.parent().parent().addClass("form-invalid");
            }

            if ($.trim(display_name.val()) == "") {
                display_name.parent().parent().addClass("form-invalid");
            }

            if ($.trim(display_name.val()) == "") {
                display_name.focus();
                return false;
            }

            if ($.trim(role_name.val()) == "") {
                role_name.focus();
                return false;
            }

            return true;
        });

        $("#cap_apply").click(function() {
            if ($(this).prev().val() == "")
                return;

            var button = $(this).prop("disabled", true);
            var data = {
                "action": "wpfront_user_role_editor_copy_capabilities",
                "role": $(this).prev().val()
            };
            $.post(ajaxurl, data, function(response) {
                $("div.role-add-new div.postbox input").prop("checked", false);
                for (m in response) {
                    change_select_all($("div.role-add-new input#" + m).prop("checked", response[m]));
                }
                button.prop("disabled", false);
            }, 'json');
        });

        $("div.role-add-new div.postbox div.main input:first-child").each(function() {
            change_select_all($(this));
        });
    })(jQuery);
</script>