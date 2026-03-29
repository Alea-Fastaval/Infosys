<?php

/**
 * Copyright (C) 2009-2012  Peter Lind
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/gpl.html>.
 *
 * PHP version 5.3+
 *
 * @category  Infosys
 * @package   Controllers 
 * @author    Peter Lind <peter.e.lind@gmail.com>
 * @copyright 2009-2012 Peter Lind
 * @license   http://www.gnu.org/licenses/gpl.html GPL 3
 * @link      http://www.github.com/Fake51/Infosys
 */

/**
 * handles users, roles and such
 *
 * @category Infosys
 * @package  Controllers 
 * @author   Peter Lind <peter.e.lind@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl.html GPL 3
 * @link     http://www.github.com/Fake51/Infosys
 */
class AdminController extends Controller
{
  protected $prerun_hooks = array(
    array('method' => 'checkUser', 'exclusive' => true),
  );

  /**
   * displays main options for admin
   *
   * @access public
   * @return void
   */
  public function main() {}

  /**
   * displays all users and a form for creating new ones
   * also displays ajax interface for handling users
   *
   * @access public
   * @return void
   */
  public function handleUsers()
  {
    $this->page->users      = $this->model->getAllUsers();
    $this->page->all_roles  = $this->model->getAllRoles();
    $this->page->model      = $this->model;
  }

  /**
   * displays all roles and a form for creating new ones
   * also displays ajax interface for handling roles
   *
   * @access public
   * @return void
   */
  public function handleRoles()
  {
    $this->page->roles      = $this->model->getAllRoles();
    $this->page->privileges = $this->model->getAllPrivileges();
  }

  /**
   * displays all privileges and a form for creating new ones
   * also displays ajax interface for handling privileges
   *
   * @access public
   * @return void
   */
  public function handlePrivileges()
  {
    $this->page->privileges = $this->model->getAllPrivileges();
  }

  /**
   * shows a form to reset signups
   *
   * @access public
   * @return void
   */
  public function showConfirmReset() {}

  /**
   * shows a simple preview page for generating participant name badges
   *
   * @access public
   * @return void
   */
  public function generateBadges()
  {
    // Enable error display for debugging this page
    @ini_set('display_errors', 1);
    @ini_set('display_startup_errors', 1);
    // Show runtime errors but hide deprecation notices introduced by newer PHP versions
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

    // Instantiate participant model directly (DI does not register ParticipantModel by name)
    $participantModel = new ParticipantModel($this->dic->get('DB'), $this->config, $this->dic);
    $participants = $participantModel->findAll();

    // follow the same flow as ParticipantController::nameTagList
    $participantModel->generateParticipantBarcodes($participants);

    $photos = [];
    foreach ($participants as $participant) {
      $photos[$participant->id] = $participantModel->fetchCroppedPhoto($participant);
    }

    $this->page->participants = $participants;
    $this->page->photos = $photos;

    // Default template path (instruct to upload PNG to this location)
    $this->page->template_path = '/uploads/id_template.png';

    // Provide work area id => name mapping for templates (both languages)
    $work_areas = [];
    foreach ($participantModel->getWorkAreas() as $area) {
      $work_areas[$area['id']] = [
        'da' => $area['name_da'] ?? $area['name_en'] ?? '',
        'en' => $area['name_en'] ?? $area['name_da'] ?? '',
      ];
    }
    $this->page->work_areas = $work_areas;

    $this->page->setTitle('Generer deltager navneskilte');
  }

  /**
   * generates a multi-page PDF of participant badges (one badge per page, 91x60mm)
   *
   * @access public
   * @return void
   */
  public function generateBadgesPDF()
  {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

    $participantModel = new ParticipantModel($this->dic->get('DB'), $this->config, $this->dic);
    $participants     = $participantModel->findAll();
    if ($limit > 0) {
      $participants = array_slice($participants, 0, $limit);
    }

    if (empty($participants)) {
      exit('No participants found');
    }

    $participantModel->generateParticipantBarcodes($participants);

    $photos = [];
    foreach ($participants as $participant) {
      $photos[$participant->id] = $participantModel->fetchCroppedPhoto($participant);
    }

    $work_areas = [];
    foreach ($participantModel->getWorkAreas() as $area) {
      $work_areas[$area['id']] = [
        'da' => $area['name_da'] ?? $area['name_en'] ?? '',
        'en' => $area['name_en'] ?? $area['name_da'] ?? '',
      ];
    }

    // 91mm x 60mm page = 85mm badge + 3mm padding on every side (matches .print-wrapper)
    $font_path = PUBLIC_PATH . 'fonts/montserrat/';
    $mpdf = new \Mpdf\Mpdf([
      'format'        => [91, 60],
      'margin_left'   => 0,
      'margin_right'  => 0,
      'margin_top'    => 0,
      'margin_bottom' => 0,
      'fontDir'       => [$font_path],
      'fontdata'      => [
        'montserrat' => [
          'R'  => 'Montserrat-Regular.ttf',
          'B'  => 'Montserrat-Bold.ttf',
          'I'  => 'Montserrat-Regular.ttf',
          'BI' => 'Montserrat-Bold.ttf',
        ],
      ],
      'default_font' => 'montserrat',
    ]);

    // Write one badge per page using AddPage() so each WriteHTML() call is small and
    // never exceeds mPDF's pcre.backtrack_limit (passing all badges in one giant string
    // causes that limit to be hit for large participant lists).
    // Design values are kept in sync with generatebadges.phtml:
    //   colours: .red=#9b1919  .blue=#046AA8
    //   badge 85x54mm, header 14mm (.badge-header), details 36mm (.details)
    //   header font: 22px bold, name/pronouns: 24px bold (.name/.pronouns), role: 15px (.role)
    //   details uses justify-content:space-between → 3 equal rows of 12mm each
    $first = true;
    foreach ($participants as $participant) {
      $photo_url = isset($photos[$participant->id]) ? $photos[$participant->id] : '';
      $photo_src = '';
      if ($photo_url) {
        $fs_path = PUBLIC_PATH . ltrim($photo_url, '/');
        if (file_exists($fs_path)) {
          $photo_src = $fs_path;
        }
      }

      $is_arrangoer = method_exists($participant, 'isArrangoer') && $participant->isArrangoer();
      $lang         = ($participant->main_lang ?? 'da') === 'en' ? 'en' : 'da';
      $bg           = $is_arrangoer ? '#9b1919' : '#046AA8';
      $type_label   = $is_arrangoer
        ? ($lang === 'en' ? 'ORGANIZER' : 'ARRANGØR')
        : ($lang === 'en' ? 'PARTICIPANT' : 'DELTAGER');
      $id           = htmlentities($participant->id);
      $name         = htmlentities($participant->getName());

      $pronouns = '';
      if (method_exists($participant, 'getPronoun')) {
        $pronouns = htmlentities($participant->getPronoun());
      } elseif (!empty($participant->pronouns)) {
        $pronouns = htmlentities($participant->pronouns);
      }

      $wa_data = $work_areas[$participant->work_area] ?? null;
      $wa      = htmlentities($wa_data ? $wa_data[$lang] : '');

      if ($photo_src) {
        $img_html = '<img src="' . $photo_src . '" style="height:20mm;width:14.44mm;display:block;">';
      } else {
        $img_html = '<img src="' . PUBLIC_PATH . 'img/logo2026.png" style="height:12mm;width:12mm;display:block;margin-right:3mm;margin-left:auto;">';
      }

      if (!$first) {
        $mpdf->AddPage();
      }
      $first = false;

      $badge = '
        <div style="padding:3mm;width:91mm;height:60mm;background:white;">
          <table cellpadding="0" cellspacing="0" style="width:85mm;height:20mm;background:' . $bg . ';color:white;text-transform:uppercase;border-collapse:collapse;font-family:montserrat;">
            <tr>
              <td style="vertical-align:middle;text-align:center;font-weight:bold;font-size:30px;line-height:30px;padding:0 2mm;">
                ' . $type_label . '<br>' . $id . '
              </td>
              <td style="width:17mm;height:20mm;vertical-align:middle;text-align:right;padding:0 2mm;">
                ' . $img_html . '
              </td>
            </tr>
          </table>
          <table cellpadding="0" cellspacing="0" style="width:85mm;height:34mm;background:white;text-transform:uppercase;border-collapse:collapse;font-family:montserrat;">
            <tr>
              <td style="height:20mm;vertical-align:middle;padding:2mm 2mm 0;font-weight:bold;font-size:24px;">
                ' . $name . '
              </td>
              <td rowspan="3" style="width:8mm;rotate:-90;text-align:left;text-transform:none;font-size:8px;font-weight:bold;color:#cccccc;letter-spacing:1px;font-family:montserrat;">
                Fastaval 2026
              </td>
            </tr>
            <tr>
              <td style="height:7mm;vertical-align:bottom;padding:0 2mm 2mm;font-weight:bold;font-size:24px;">
                ' . $pronouns . '
              </td>
            </tr>
            <tr>
              <td style="height:6mm;vertical-align:bottom;padding:0 2mm 2mm;font-size:15px;line-height:15px;">
                ' . $wa . '
              </td>
            </tr>
          </table>
        </div>
      ';

      $mpdf->WriteHTML($badge, \Mpdf\HTMLParserMode::HTML_BODY);
    }

    $mpdf->OutputHttpDownload('Deltager-navneskilte-' . date('Y-m-d-Hi') . '.pdf');
    exit();
  }

  /**
   * resets signups
   *
   * @access public
   * @return void
   */
  public function resetSignups()
  {
    if (!$this->page->request->isPost() || empty($this->page->request->post->confirmReset)) {
      $this->errorMessage('Not resetting as you did not confirm');
    } else {
      $this->model->resetSignups();
      $this->successMessage('Signups reset');
    }

    $this->hardRedirect($this->url('home'));
  }

  //{{{ ajax functions be here

  /**
   * ajax function, updates password for a user
   *
   * @access public
   * @return void
   */
  public function ajaxChangePass()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || !($user = $this->model->findEntity('User', $this->vars['id'])) || !$this->page->request->isPost() || empty($this->page->request->post->pass)) {
      echo "validation failed";
      exit;
    }

    $user->pass = md5($this->page->request->post->pass);
    if ($user->update()) {
      $this->log("User #{$user->id} ({$user->user}) fik skiftet password af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "update failed";
    }

    exit;
  }

  /**
   * ajax function, updates label for a user
   *
   * @access public
   * @return void
   */
  public function ajaxChangeLabel()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || !($user = $this->model->findEntity('User', $this->vars['id'])) || !$this->page->request->isPost() || empty($this->page->request->post->label)) {
      echo "validation failed";
      exit;
    }

    $user->label = trim($this->page->request->post->label);
    if ($user->update()) {
      $this->log("User #{$user->id} ({$user->user}) fik skiftet label af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "update failed";
    }

    exit;
  }

  /**
   * ajax function, removes a role from a user
   *
   * @access public
   * @return void
   */
  public function ajaxRemoveRole()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || empty($this->vars['role_id']) || !($user = $this->model->findEntity('User', $this->vars['id'])) || !($role = $this->model->findEntity('Role', $this->vars['role_id']))) {
      echo "failed - vars";
      exit;
    }

    if ($user->removeRole($role)) {
      $this->log("User #{$user->id} ({$user->user}) fik frataget sin {$role->name}-rolle af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "failed - action";
    }

    exit;
  }

  /**
   * ajax function, adds a role to a user
   *
   * @access public
   * @return void
   */
  public function ajaxAddRole()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || empty($this->vars['role_id']) || !($user = $this->model->findEntity('User', $this->vars['id'])) || !($role = $this->model->findEntity('Role', $this->vars['role_id']))) {
      echo "failed - vars";
      exit;
    }

    if ($user->addRole($role)) {
      $this->log("User #{$user->id} ({$user->user}) fik tilføjet {$role->name}-rollen af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "failed - action";
    }

    exit;
  }

  /**
   * ajax function, disables a user
   *
   * @access public
   * @return void
   */
  public function ajaxDisableUser()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || !($user = $this->model->findEntity('User', $this->vars['id']))) {
      echo "failed";
      exit;
    }

    if ($user->disable()) {
      $this->log("User #{$user->id} ({$user->user}) blev disabled af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "failed";
    }

    exit;
  }

  /**
   * ajax function, enables a user
   *
   * @access public
   * @return void
   */
  public function ajaxEnableUser()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || !($user = $this->model->findEntity('User', $this->vars['id']))) {
      echo "failed";
      exit;
    }

    if ($user->enable()) {
      $this->log("User #{$user->id} ({$user->user}) blev enabled af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "failed";
    }

    exit;
  }

  /**
   * ajax function, deletes a user
   *
   * @access public
   * @return void
   */
  public function ajaxDeleteUser()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || !($user = $this->model->findEntity('User', $this->vars['id']))) {
      echo "failed";
      exit;
    }

    foreach ($user->getRoles() as $role) {
      $user->removeRole($role);
    }

    $name = $user->user;
    $id = $user->id;
    if ($user->delete()) {
      $this->log("User #{$id} ({$name}) blev slettet af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "failed";
    }

    exit;
  }

  /**
   * ajax function, creates a user
   *
   * @access public
   * @return void
   */
  public function ajaxCreateUser()
  {
    $this->ajaxHeader();
    if (!$this->page->request->isPost() || empty($this->page->request->post->user) || empty($this->page->request->post->pass)) {
      echo "failed";
      exit;
    }

    $post = $this->page->request->post;
    if ($user = $this->model->createUser($post)) {
      $this->log("User #{$user->id} ({$user->user}) blev oprettet af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
      if (!empty($post->role) && ($role = $this->model->findEntity('Role', $post->role))) {
        if ($user->addRole($role)) {
          $this->log("User #{$user->id} ({$user->user}) fik tilføjet {$role->name}-rollen af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
        } else {
          $this->log("User #{$user->id} ({$user->user}) blev slettet af {$this->model->getLoggedInUser()->user}", 'Users', $this->model->getLoggedInUser());
          $user->delete();

          header('HTTP 500 Failed');
          exit;
        }
      }
      echo json_encode(array('user' => $user->user, 'id' => $user->id));
    } else {
      header('HTTP 500 Failed');
    }

    exit;
  }

  /**
   * creates a privilege
   *
   * @access public
   * @return void
   */
  public function ajaxCreatePrivilege()
  {
    $this->ajaxHeader();
    if (!$this->page->request->isPost()) {
      echo 'failed';
      exit;
    }

    $post = $this->page->request->post;
    if (empty($post->controller) || empty($post->method)) {
      echo 'failed';
      exit;
    }

    if ($priv = $this->model->createPrivilege($post)) {
      $this->log("Privilege #{$priv->id} blev oprettet af {$this->model->getLoggedInUser()->user}", 'Privilege', $this->model->getLoggedInUser());
      echo json_encode(array('id' => $priv->id));
    } else {
      echo "failed";
    }

    exit;
  }

  /**
   * deletes a privilege
   *
   * @access public
   * @return void
   */
  public function ajaxDeletePrivilege()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || !($priv = $this->model->findEntity('Privilege', $this->vars['id']))) {
      echo "failed";
      exit;
    }

    $id = $priv->id;
    if ($priv->delete()) {
      $this->log("Privilege #{$id} blev slettet af {$this->model->getLoggedInUser()->user}", 'Privilege', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "failed";
    }

    exit;
  }

  /**
   * deletes a privilege
   *
   * @access public
   * @return void
   */
  public function ajaxDeleteRole()
  {
    $this->ajaxHeader();
    if (empty($this->vars['id']) || !($role = $this->model->findEntity('Role', $this->vars['id']))) {
      echo "failed";
      exit;
    }

    $id = $role->id;
    $name = $role->name;
    if ($role->delete()) {
      $this->log("Role #{$role->id} ({$name}) blev slettet af {$this->model->getLoggedInUser()->user}", 'Roles', $this->model->getLoggedInUser());
      echo "worked";
    } else {
      echo "failed";
    }

    exit;
  }

  /**
   * creates a role
   *
   * @access public
   * @return void
   */
  public function ajaxCreateRole()
  {
    $this->ajaxHeader();
    if (!$this->page->request->isPost()) {
      echo "failed";
      exit;
    }

    try {
      if (!($role = $this->model->createRole($this->page->request->post))) {
        throw new FrameworkException('Could not create role');
      }

      $obj     = new StdClass();
      $obj->id = $role->id;

      $this->log("Role #{$role->id} ({$role->name}) blev oprettet af {$this->model->getLoggedInUser()->user}", 'Roles', $this->model->getLoggedInUser());
      echo json_encode($obj);
    } catch (Exception $e) {
      echo "failed - " . $e->getMessage();
    }

    exit;
  }

  /**
   * adds a privilege to a role
   *
   * @access public
   * @return void
   */
  public function ajaxAddPrivilege()
  {
    $this->ajaxHeader();
    if (empty($this->vars['role_id']) || empty($this->vars['privilege_id'])) {
      echo "failed";
      exit;
    }

    try {
      list($role, $privilege) = $this->model->addPrivilege($this->vars['role_id'], $this->vars['privilege_id']);

      $this->log("Role #{$role->id} ({$role->name}) fik tilføjet privilegiet (" . $this->db->sanitize($privilege->controller) . ':' . $this->db->sanitize($privilege->method) . ") af {$this->model->getLoggedInUser()->user}", 'Roles', $this->model->getLoggedInUser());
      echo "worked";
    } catch (Exception $e) {
      echo "failed";
    }

    exit;
  }

  /**
   * removes a privilege from a role
   *
   * @access public
   * @return void
   */
  public function ajaxRemovePrivilege()
  {
    $this->ajaxHeader();
    if (empty($this->vars['role_id']) || empty($this->vars['privilege_id'])) {
      echo "failed";
      exit;
    }

    try {
      list($role, $privilege) = $this->model->removePrivilege($this->vars['role_id'], $this->vars['privilege_id']);

      $this->log("Role #{$role->id} ({$role->name}) fik fjernet privilegiet (" . $this->db->sanitize($privilege->controller) . ':' . $this->db->sanitize($privilege->method) . ") af {$this->model->getLoggedInUser()->user}", 'Roles', $this->model->getLoggedInUser());
      echo "worked";
    } catch (Exception $e) {
      echo "failed";
    }

    exit;
  }

  public function ajaxUsers()
  {
    $id = $this->vars['id'] ?? '*';

    if ($this->page->request->isPost()) {
      $this->jsonOutput(
        [
          'status' => 'error',
          'code' => '405',
          'message' => "Can't POST user information"
        ],
        405
      );
    } else {
      // GET User(s) info

      // Check for correct ID
      if (!is_int($id) && $id != '*') {
        $this->jsonOutput(
          [
            'status' => 'error',
            'code' => '400',
            'message' => "Can't find user with ID:$id, ID has to be integer or '*' for all users"
          ],
          400
        );
      }


      if (is_int($id)) {
        $users = $this->model->getUserByID($id);
      } else {
        $users = $this->model->getAllUsers();
      }

      if (empty($users)) {
        $this->jsonOutput(
          [
            'status' => 'error',
            'code' => '404',
            'message' => "No users with ID:$id found"
          ],
          404
        );
      }

      $output = [];
      foreach ($users as $user) {
        $output[$user->id] = [
          'id' => $user->id,
          'email' => $user->user,
          'name' => $user->user,
          'description' => $user->label,
        ];
      }

      $this->jsonOutput(
        [
          'status' => 'success',
          'code' => '200',
          'users' => $output,
        ],
      );
    }
  }

  //}}}
}
