<?php
/**
 * Copyright (C) 2009-2012 Peter Lind
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
 * PHP version 5
 *
 * @category  Infosys
 * @package   Controllers
 * @author    Peter Lind <peter.e.lind@gmail.com>
 * @copyright 2009-2012 Peter Lind
 * @license   http://www.gnu.org/licenses/gpl.html GPL 3
 * @link      http://www.github.com/Fake51/Infosys
 */

/**
 * handles economy related pages
 *
 * @category Infosys
 * @package  Controllers
 * @author   Peter Lind <peter.e.lind@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl.html GPL 3
 * @link     http://www.github.com/Fake51/Infosys
 */
class EconomyController extends Controller
{
  /**
   * pre run hooks
   * format of array is: an array of method (string), exclusive (bool), methodlist (array of strings) per hook
   * - method is method to run
   * - exclusive determines whether the next array consists of methods to be excluded or included in the prerun hook
   * - methodlist is the array of methods for which the prerun hook will either be run (inclusive) or not be run (exclusive)
   *
   * @var array
   */
  protected $prerun_hooks = array(
    array('method' => 'checkUser','exclusive' => true, 'methodlist' => array()),
  );

  /**
   * displays a table with detailed budget info
   *
   * @access public
   * @return void
   */
  public function detailedBudget() {
    $this->page->budget_details = $this->model->computeDetailedBudget();
  }

  /**
   * returns accounting overview of the convention
   *
   * @access public
   * @return void
   */
  public function accountingOverview()
  {
    $this->page->accounting_data = $this->model->computeAccountingData();
  }

  /**
   * Shows table of participant economy
   *
   * @access public
   * @return void
   */
  public function participantOverview()
  {
    $columns = [
      'id' => [
        'header' => 'ID',
      ],
      'name' => [
        'header' => 'Navn',
      ],
      'area' => [
        'header' => 'Område',
      ],
      'entry' => [
        'header' => 'Ingang/overnatning',
        'align' => 'right'
      ],
      'food' => [
        'header' => 'Mad',
        'align' => 'right'
      ],
      'wear' => [
        'header' => 'Wear',
        'align' => 'right'
      ],
      'activities' => [
        'header' => 'Aktiviteter',
        'align' => 'right'
      ],
      'alea'=> [
        'header' => 'Alea',
        'align' => 'right'
      ],
      'other' => [
        'header' => 'Andet',
        'align' => 'right'
      ],
      'total'=> [
        'header' => 'I alt',
        'align' => 'right'
      ],
    ];
    $data = $this->model->computeParticipantData();
    
    if ($this->page->request->isPost()){
      $post = $this->page->request->post;
    }

    if (isset($post->download) && $post->download == 'csv') {
      $csv_data = [];
      $csv_row = [];
      
      foreach ($columns as $column) { 
        $csv_row[] = $column['header'];
      }
      $csv_data[] = $csv_row;

      foreach ($data as $data_row) {
        $csv_row = [];
        foreach ($columns as $key => $column) { 
          $csv_row[] = $data_row[$key];
        }
        $csv_data[] = $csv_row;
      }      

      $this->returnCSV($csv_data, "participant-economy");
      exit;
    }


    $this->page->columns = $columns;
    $this->page->data = $data;
  }

  /**
   * Page for listing and confirming on-line payments done via ForeningLet
   *
   * @access public
   * @return void
   */
  public function registerPayments() {
    if (!$this->model->getLoggedInUser()->hasRole('Admin')) {
      $this->errorMessage('Kun admin kan lave batch registrering af betalinger');
      $this->hardRedirect($this->url('home'));
    }

    // if it's not a post request, don't do anything
    $post = (object) [];
    if ($this->page->request->isPost()){
      $post = $this->page->request->post;
    }
    
    $session = $this->dic->get('Session');
    $registered_payments = $this->model->loadPayments();
    
    if (isset($post->importpayments)) {
      // Did the user submit a file
      $file = isset($_FILES['payments']) ? $_FILES['payments'] : null;
      if($file == null || $file['error'] == UPLOAD_ERR_NO_FILE) {
        $this->errorMessage('Ingen fil valgt.');
        return;
      }

      $sheet_data = $this->model->parsePaymentSheet($file);
      $this->model->matchPayments($sheet_data, $registered_payments);
      $session->sheet_data = $sheet_data;
    }

   
    $this->page->registered_payments = $registered_payments;
    $this->page->sheet_data = $session->sheet_data;

    $this->page->registerEarlyLoadJS('register_payments.js');
  }

  /**
   * Ajax function for confirming on-line payments done via ForeningLet
   *
   * @access public
   * @return void
   */
  public function confirmPayments() {
    if (!$this->model->getLoggedInUser()->hasRole('Admin')) {
      $this->jsonOutput([
        'status' => 'error',
        'message' => 'Admin only',
      ], 401);
    }

    // if it's not a post request, don't do anything
    if (!$this->page->request->isPost()){
      $this->jsonOutput([
        'status' => 'error',
        'message' => 'not a POST request',
      ], 400);
    }

    $post = $this->page->request->post;
    if (!isset($post->list)){
      $this->jsonOutput([
        'status' => 'error',
        'message' => 'missing list of payments',
      ], 400);
    }

    $session = $this->dic->get('Session');
    $sheet_data = $session->sheet_data;

    $result_list = [];
    foreach ($post->list as $payment) {
      $result = $this->model->confirmSinglePayment($payment['participant'], $sheet_data->rows[$payment['sheet_row']], $payment['payment']);
      $result['sheet_row'] = $payment['sheet_row'];

      $$result_list[] = $result;
    }

    $this->jsonOutput([
      'status' => 'success',
      'message' => 'payments confirmed',
      'result' => $$result_list,
    ]);

    //$this->fileLog("confirmPayments request:\n".print_r($post,true));
  }
}



