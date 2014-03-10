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
 * Template for WPFront User Role Editor Delete Roles
 *
 * @author Syam Mohan <syam@wpfront.com>
 * @copyright 2014 WPFront.com
 */
?>

<?php
$this->verify_nonce();
$url = admin_url('admin.php');
$page_url = $url . '?page=' . self::PLUGIN_SLUG . '-all-roles';

$delete_roles = $this->delete_roles;

if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    if (!empty($_POST['roles'])) {
        $delete_roles = $_POST['roles'];
    }
}

$status_messages = array();

$editable_roles = get_editable_roles();
global $user_ID;
$user = new WP_User($user_ID);
$current_roles = $user->roles;

foreach ($delete_roles as $role) {
    if (!array_key_exists($role, $editable_roles)) {
        $status_messages[$role] = $this->__('This role cannot be deleted: Permission denied.');
    } else if ($role == 'administrator') {
        $status_messages[$role] = $this->__('\'administrator\' role cannot be deleted.');
    } else if (in_array($role, $current_roles)) {
        $status_messages[$role] = $this->__('Current user\'s role cannot be deleted.');
    }
}

if (!empty($_POST['confirm-delete'])) {
    foreach ($delete_roles as $role) {
        if (!array_key_exists($role, $status_messages)) {
            remove_role($role);
        }
    }
    echo '<script>document.location = "' . $page_url . '";</script>';
    exit();
    return;
}
?>

<div class="wrap delete-roles">
    <form method="post">
        <?php $this->create_nonce(); ?>
        <h2><?php echo $this->__('Delete Roles'); ?></h2>
        <p><?php echo $this->__('You have specified these roles for deletion'); ?>:</p>
        <ul>
            <?php
            global $wp_roles;
            foreach ($delete_roles as $role) {
                ?>
                <li>
                    <?php echo $this->__('Role') . ': <strong>' . $role . '</strong> [<strong>' . $wp_roles->role_names[$role] . '</strong>] '; ?>
                    <?php if (array_key_exists($role, $status_messages)) { ?>
                        <strong> - <?php echo $status_messages[$role]; ?></strong>
                    <?php } ?>
                    <input type="hidden" name="roles[]" value="<?php echo $role; ?>" />
                </li>
                <?php
            }
            ?>
        </ul>
        <p class="submit">
            <input type="submit" name="confirm-delete" id="submit" class="button" value="Confirm Deletion" <?php echo count($delete_roles) == count($status_messages) ? 'disabled' : ''; ?>>
        </p>
    </form>
</div>

