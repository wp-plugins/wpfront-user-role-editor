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
 * Template for WPFront User Role Editor List Roles
 *
 * @author Syam Mohan <syam@wpfront.com>
 * @copyright 2014 WPFront.com
 */
?>

<?php
$this->verify_nonce();

function wpfront_user_role_editor_roles_table_header($self) {
    ?>
    <tr>
        <th scope="col" id="cb" class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-1"><?php echo $self->__('Select All'); ?></label>
            <input id="cb-select-all-1" type="checkbox" />
        </th>
        <th scope="col" id="rolename" class="manage-column column-rolename">
            <a><span><?php echo $self->__('Display Name'); ?></span></a>
        </th>
        <th scope="col" id="rolename" class="manage-column column-rolename">
            <a><span><?php echo $self->__('Role Name'); ?></span></a>
        </th>
        <th scope="col" id="roletype" class="manage-column column-roletype">
            <a><span><?php echo $self->__('Type'); ?></span></a>
        </th>
        <th scope="col" id="usercount" class="manage-column column-usercount num">
            <a><span><?php echo $self->__('Users'); ?></span></a>
        </th>
        <th scope="col" id="capscount" class="manage-column column-capscount num">
            <a><span><?php echo $self->__('Capabilities'); ?></span></a>
        </th>
    </tr>
    <?php
}

function wpfront_user_role_editor_roles_table_bulk_actions($self, $position, $role_data) {
    ?>
    <div class="tablenav <?php echo $position; ?>">
        <div class="alignleft actions bulkactions">
            <select name="action_<?php echo $position; ?>">
                <option value="-1" selected="selected"><?php echo $self->__('Bulk Actions'); ?></option>
                <?php if (current_user_can($self->get_capability_string('delete'))) { ?>
                    <option value="delete"><?php echo $self->__('Delete'); ?></option>
                <?php } ?>
            </select>
            <input type="submit" name="doaction_<?php echo $position; ?>" class="button bulk action" value="<?php echo $self->__('Apply'); ?>">
        </div>
        <div class="tablenav-pages one-page"><span class="displaying-num"><?php echo sprintf($self->__('%s item(s)'), count($role_data)); ?></span>
            <br class="clear">
        </div>
    </div>
    <?php
}

$role_data = array();
global $wp_roles;
$roles = $wp_roles->role_names;
asort($roles, SORT_STRING | SORT_FLAG_CASE);
$builtin_count = 0;
$custom_count = 0;

foreach ($roles as $key => $value) {
    $role_data[$key] = array(
        'role_name' => $key,
        'display_name' => $value,
        'role_type' => in_array($key, $this->builtin_roles) ? 'Built-In' : 'Custom',
        'usercount' => count(get_users(array('role' => $key))),
        'capscount' => count($wp_roles->roles[$key]['capabilities'])
    );
    if (in_array($key, $this->builtin_roles))
        $builtin_count++;
    else
        $custom_count++;
}

$url = admin_url('admin.php');
$page_url = $url . '?page=' . self::PLUGIN_SLUG . '-all-roles';

$list = 'all';
if (!empty($_GET['list']))
    $list = $_GET['list'];

switch ($list) {
    case 'all':
    case 'haveusers':
    case 'nousers':
    case 'builtin':
    case 'custom':
        break;
    default :
        $list = 'all';
        break;
}

$search = '';
if (!empty($_POST['search']) && !empty($_POST['search-submit'])) {
    $search = trim($_POST['search']);
}
?>

<style type="text/css">
    div.wrap div.footer {
        text-align: center;
    }
</style>

<div class="wrap">
    <h2>
        <?php
        echo $this->__('Roles');
        if (current_user_can($this->get_capability_string('create'))) {
            ?>
            <a href="<?php echo $url . '?page=' . self::PLUGIN_SLUG . '-add-new'; ?>" class="add-new-h2"><?php echo $this->__('Add New'); ?></a>
            <?php
        }
        if ($search != '') {
            ?>
            <span class="subtitle"><?php echo sprintf($this->__('Search results for “%s”'), $search); ?> </span>
        <?php } ?>
    </h2>
    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo $page_url; ?>" class="<?php echo $list == 'all' ? 'current' : ''; ?>">
                <?php echo $this->__('All'); ?>
                <span class="count"><?php echo '(' . count($role_data) . ')'; ?></span>
            </a>
            |
        </li>
        <li class="haveusers">
            <a href="<?php echo $page_url . '&list=haveusers'; ?>" class="<?php echo $list == 'haveusers' ? 'current' : ''; ?>">
                <?php
                $count = 0;
                foreach ($role_data as $value) {
                    if ($value['usercount'] > 0) {
                        $count++;
                    }
                }
                echo $this->__('Having Users');
                ?>
                <span class="count"><?php echo '(' . $count . ')'; ?></span>
            </a>
            |
        </li>
        <li class="nousers">
            <a href="<?php echo $page_url . '&list=nousers'; ?>" class="<?php echo $list == 'nousers' ? 'current' : ''; ?>">
                <?php echo $this->__('No Users'); ?>
                <span class="count"><?php echo '(' . (count($role_data) - $count) . ')'; ?></span>

            </a>
            |
        </li>
        <li class="built-in">
            <a href="<?php echo $page_url . '&list=builtin'; ?>" class="<?php echo $list == 'builtin' ? 'current' : ''; ?>">
                <?php echo $this->__('Built-In'); ?>
                <span class="count"><?php echo '(' . $builtin_count . ')'; ?></span>
            </a>
            |
        </li>
        <li class="custom">
            <a href="<?php echo $page_url . '&list=custom'; ?>" class="<?php echo $list == 'custom' ? 'current' : ''; ?>">
                <?php echo $this->__('Custom'); ?>
                <span class="count"><?php echo '(' . $custom_count . ')'; ?></span>
            </a>
        </li>
    </ul>
    <?php
    switch ($list) {
        case 'all':
            break;
        case 'haveusers':
            foreach ($role_data as $key => $value) {
                if ($value['usercount'] == 0)
                    unset($role_data[$key]);
            }
            break;
        case 'nousers':
            foreach ($role_data as $key => $value) {
                if ($value['usercount'] > 0)
                    unset($role_data[$key]);
            }
            break;
        case 'builtin':
            foreach ($role_data as $key => $value) {
                if ($value['role_type'] != 'Built-In')
                    unset($role_data[$key]);
            }
            break;
        case 'custom':
            foreach ($role_data as $key => $value) {
                if ($value['role_type'] != 'Custom')
                    unset($role_data[$key]);
            }
            break;
        default :
            $list = 'all';
            break;
    }

    if ($search != '') {
        foreach ($role_data as $key => $value) {
            if (strpos(strtolower($value['display_name']), strtolower($search)) === FALSE)
                unset($role_data[$key]);
        }
    }
    ?>
    <form method = "post">
        <?php $this->create_nonce(); ?>
        <p class = "search-box">
            <label class = "screen-reader-text" for = "role-search-input"><?php echo $this->__('Search Roles') . ':'; ?></label>
            <input type="search" id="role-search-input" name="search" value="<?php echo $search; ?>">
            <input type="submit" name="search-submit" id="search-submit" class="button" value="<?php echo $this->__('Search Roles'); ?>">
        </p>
        <?php wpfront_user_role_editor_roles_table_bulk_actions($this, 'top', $role_data) ?>
        <table class="wp-list-table widefat fixed users">
            <thead>
                <?php wpfront_user_role_editor_roles_table_header($this); ?>
            </thead>
            <tfoot>
                <?php wpfront_user_role_editor_roles_table_header($this); ?>
            </tfoot>
            <tbody id="the-list">
                <?php
                $index = 0;
                $editable_roles = get_editable_roles();
                foreach ($role_data as $key => $value) {
                    $is_editable = array_key_exists($key, $editable_roles);
                    if ($is_editable)
                        $is_editable = $key != 'administrator';
                    ?>
                    <tr id="<?php echo $key; ?>" class="<?php echo $index % 2 == 0 ? 'alternate' : ''; ?>">
                        <th scope="row" class="check-column">
                            <label class="screen-reader-text" for="cb-select-<?php echo $key; ?>"><?php echo sprintf('Select %s', $value['display_name']) ?></label>
                            <input type="checkbox" name="cb-select-<?php echo $key; ?>" id="cb-select-<?php echo $key; ?>" />
                        </th>
                        <td class="displayname column-displayname">
                            <strong>
                                <?php if (current_user_can($this->get_capability_string('edit'))) { ?>
                                    <a href="<?php echo $page_url . '&edit_role=' . $key ?>"><?php echo $value['display_name']; ?></a>
                                    <?php
                                } else {
                                    echo $value['display_name'];
                                }
                                ?>
                            </strong>
                            <br />
                            <div class="row-actions">
                                <?php
                                $links = array();
                                if (current_user_can($this->get_capability_string('edit'))) {
                                    $links[] = '<span class="edit">
                                        <a href="' . $page_url . '&edit_role=' . $key . '">' . ($is_editable ? $this->__('Edit') : $this->__('View')) . '</a>
                                    </span>';
                                }

                                if ($is_editable && current_user_can($this->get_capability_string('delete'))) {
                                    $links[] = '<span class="delete">
                                        <a href="' . $page_url . '&delete_role=' . $key . '">' . $this->__('Delete') . '</a>
                                    </span>';
                                }

                                echo implode('|', $links);
                                ?>
                            </div>
                        </td>
                        <td class="rolename column-rolename">
                            <?php echo $key; ?>
                        </td>
                        <td class="roletype column-roletype">
                            <?php echo $this->__($value['role_type']); ?>
                        </td>
                        <td class="usercount column-usercount num">
                            <?php echo $value['usercount']; ?>
                        </td>
                        <td class="capscount column-capscount num">
                            <?php echo $value['capscount']; ?>
                        </td>
                    </tr>
                    <?php
                    $index++;
                }
                ?>
            </tbody>
        </table>
        <?php wpfront_user_role_editor_roles_table_bulk_actions($this, 'bottom', $role_data) ?>
    </form>
    <div class="footer"><a target="_blank" href="http://wpfront.com/contact/">Feedback</a> | <a target="_blank" href="http://wpfront.com/">wpfront.com</a></div>
</div>

<script type="text/javascript">
    (function($) {
        $("input.button.bulk.action").click(function() {
            if ($(this).prev().val() == -1)
                return false;
        });
    })(jQuery);
</script>