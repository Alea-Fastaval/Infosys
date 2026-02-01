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
 * @package   Models
 * @author    Peter Lind <peter.e.lind@gmail.com>
 * @copyright 2009-2012 Peter Lind
 * @license   http://www.gnu.org/licenses/gpl.html GPL 3
 * @link      http://www.github.com/Fake51/Infosys
 */

/** 
 * handles all data fetching for the economy MVC
 *
 * @category Infosys
 * @package  Models
 * @author   Peter Lind <peter.e.lind@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl.html GPL 3
 * @link     http://www.github.com/Fake51/Infosys
 */
class EconomyModel extends Model
{

  /**
   * returns detailed numbers for
   * the conventions budget, grouped by participants
   *
   * @access public
   * @return array
   */
  public function computeDetailedBudget()
  {
    $query = "
SELECT
  d.id,
  CONCAT(d.fornavn, ' ', d.efternavn) AS name,
  bk.navn AS category,
  d.betalt_beloeb AS paid_amount,
  d.udeblevet,
  IFNULL(e.entrance_cost, 0) AS entrance_cost,
  IFNULL(s.sleep_cost, 0) AS sleep_cost,
  IFNULL(w.wear_cost, 0) AS wear_cost,
  IFNULL(f.food_cost, 0) AS food_cost,
  IFNULL(a.activity_cost, 0) AS activity_cost,
  CASE WHEN d.hemmelig_onkel = 'ja' AND d.rig_onkel = 'ja' THEN 600 WHEN d.hemmelig_onkel = 'ja' OR d.rig_onkel = 'ja' THEN 300 ELSE 0 END as uncle_cost
FROM
  deltagere AS d
  JOIN brugerkategorier AS bk ON bk.id = d.brugerkategori_id

  LEFT JOIN (SELECT
    deltager_id,
    SUM(i.pris) AS entrance_cost
  FROM
    deltagere_indgang AS di
    JOIN indgang AS i ON i.id = di.indgang_id
  WHERE
    i.type NOT LIKE '%OVERNATNING%'
  GROUP BY
    deltager_id
  ) AS e ON e.deltager_id = d.id

  LEFT JOIN (SELECT
    deltager_id,
    SUM(i.pris) AS sleep_cost
  FROM
    deltagere_indgang AS di
    JOIN indgang AS i ON i.id = di.indgang_id
  WHERE
    i.type LIKE '%OVERNATNING%'
  GROUP BY
    deltager_id
  ) AS s ON s.deltager_id = d.id

  LEFT JOIN (SELECT
    deltager_id,
    SUM(wp.pris) AS wear_cost
  FROM
    deltagere_wear_order AS dwp
    JOIN wearpriser AS wp ON wp.id = dwp.wearpris_id
  GROUP BY
    deltager_id
  ) AS w ON w.deltager_id = d.id
  
  LEFT JOIN (SELECT
    dm.deltager_id,
    SUM(f.pris) AS food_cost
  FROM
    deltagere_madtider AS dm
    JOIN madtider AS m ON m.id = dm.madtid_id
    JOIN mad AS f ON f.id = m.mad_id
  GROUP BY
    deltager_id
  ) AS f ON f.deltager_id = d.id

  LEFT JOIN (SELECT
    p.deltager_id,
    SUM(ak.pris) AS activity_cost
  FROM
    pladser AS p
    JOIN hold AS h ON h.id = p.hold_id
    JOIN afviklinger AS af ON af.id = h.afvikling_id
    JOIN aktiviteter AS ak ON ak.id = af.aktivitet_id
  GROUP BY
    deltager_id
  ) AS a ON a.deltager_id = d.id
ORDER BY
  d.id;
";
    return $this->db->query($query);
  }

  /**
   * returns overview of consumables ordered
   * at the convention
   *
   * @access public
   * @return array
   */
  public function computeAccountingData()
  {
    return array(
      'Entrance'   => $this->calculateEntranceDetails(),
      'Activities' => $this->calculateActivityDetails(),
      'Food'       => $this->calculateFoodDetails(),
      'Support'    => $this->calculateSupportDetails(),
      'Wear'       => $this->calculateWearDetails(),
      'Sponsors'   => $this->calculateSponsorDetails(),
    );
  }

  /**
   * returns economy details for entrance
   *
   * @access protected
   * @return array
   */
  protected function calculateEntranceDetails()
  {
    $query = "
SELECT
  i.type AS name,
  MIN(i.pris) AS price,
  SUM(i.pris) AS cost,
  COUNT(*) AS amount
FROM
  deltagere_indgang AS di
  JOIN indgang AS i ON i.id = di.indgang_id
GROUP BY
  i.type
ORDER BY
  i.type
";

    return $this->db->query($query);
  }

  /**
   * returns economy details about activities
   *
   * @access protected
   * @return array
   */
  protected function calculateActivityDetails()
  {
    $query = "
SELECT
  ak.navn AS name,
  ak.pris AS price,
  SUM(ak.pris) AS cost,
  COUNT(*) AS amount
FROM
  deltagere_tilmeldinger AS dt
  JOIN afviklinger AS af ON af.id = dt.afvikling_id
  JOIN aktiviteter AS ak ON ak.id = af.aktivitet_id
GROUP BY
  ak.navn,
  ak.pris
HAVING
  price > 0
ORDER BY
  ak.navn
";

    return $this->db->query($query);
  }

  /**
   * returns economy details about sponsors
   *
   * @access protected
   * @return array
   */
  protected function calculateSponsorDetails()
  {
    $query = "
SELECT
  'Rige onkler' AS name,
  300 AS price,
  COUNT(*) * 300 AS cost,
  COUNT(*) AS amount
FROM
  deltagere AS d
WHERE
  d.rig_onkel = 'ja'
GROUP BY
  name
UNION
SELECT
  'Hemmelige onkler' AS name,
  300 AS price,
  COUNT(*) * 300 AS cost,
  COUNT(*) AS amount
FROM
  deltagere AS d
WHERE
  d.hemmelig_onkel = 'ja'
GROUP BY
  name
";

    return $this->db->query($query);
  }

  /**
   * returns economy details for food
   *
   * @access protected
   * @return array
   */
  protected function calculateFoodDetails()
  {
    $query = "
SELECT
  m.kategori AS name,
  MIN(m.pris) AS price,
  SUM(m.pris) AS cost,
  COUNT(*) AS amount
FROM
  deltagere_madtider AS dm
  JOIN madtider AS mt ON mt.id = dm.madtid_id
  JOIN mad AS m ON m.id = mt.mad_id
GROUP BY
  m.kategori
HAVING
  price > 0
ORDER BY
  m.kategori
";

    return $this->db->query($query);
  }

  protected function calculateSupportDetails() {
    $food_query = "SELECT id, SUBSTRING(kategori, 1, 9) AS meal, pris FROM mad";
    $meals = [];
    foreach($this->db->query($food_query) as $food_category) {
      if ($food_category['pris'] == 0) continue;

      if (isset($meals[$food_category['meal']])) {
        $meals[$food_category['meal']]['ids'][] = $food_category['id'];
      } else {
        $meals[$food_category['meal']] = [
          'ids' => [$food_category['id']],
          'price' => $food_category['pris'],
        ];
      }
    }

    $count_query = 
      "SELECT COUNT(*) AS participants, IFNULL(LEAST(fc.food_count, 2), 0) AS `food count` 
      FROM deltagere d 
      LEFT JOIN (
        SELECT deltager_id, COUNT(*) AS food_count 
        FROM deltagere_madtider dm 
        JOIN madtider mt ON dm.madtid_id = mt.id 
        WHERE mt.mad_id IN ({args})
        GROUP BY deltager_id
      ) AS fc ON fc.deltager_id = d.id
      WHERE d.financial_struggle = 'ja'
      GROUP BY `food count`
      HAVING `food count` > 0";

    $categories = [];
    foreach($meals as $name => $cat) {
      $count = 0;
      $qmarks = "?" . str_repeat(", ?", count($cat['ids']) - 1);
      foreach ($this->db->query(str_replace("{args}", $qmarks, $count_query), $cat['ids']) as $support_count) {
        $count += $support_count['participants'] * $support_count['food count'];
      }
      if ($count == 0) continue;

      $categories[] = [
        'name' => $name,
        'price' => -$cat['price'],
        'cost' => -$count * $cat['price'],
        'amount' => $count,
      ];
    }

    return $categories;
  }

  /**
   * returns economy details for wear
   *
   * @param variable variable Description
   *
   * @access public
   * @return array
   */
  public function calculateWearDetails()
  {
    $query = "
SELECT
  CONCAT(w.navn, ' - ', bk.navn) AS name,
  MIN(wp.pris) AS price,
  SUM(wp.pris) AS cost,
  COUNT(*) AS amount
FROM
  deltagere_wear_order AS dw
  JOIN wearpriser AS wp ON wp.id = dw.wearpris_id
  JOIN brugerkategorier AS bk ON bk.id = wp.brugerkategori_id
  JOIN wear AS w ON w.id = wp.wear_id
GROUP BY
  name
ORDER BY
  name
";

    return $this->db->query($query);
  }

  public function computeParticipantData() {
    $participant = $this->createEntity('Deltagere');
    $select = $participant->getSelect()->setWhere('annulled','=','nej');
    $participants = $participant->findBySelectMany($select);

    $result = [];
    foreach ($participants as $participant) {
      $result[] = [
        'id' => $participant->id,
        'name' => $participant->getName(),
        'area' => $participant->arbejdsomraade,
        'entry' => $participant->calcEntry(),
        'food' => $participant->calcFood(),
        'wear' => $participant->calcWear(),
        'activities' => $participant->calcActivities(),
        'other' => $participant->calcOtherStuff(),
        'alea'=> $participant->calcAlea(),
        'total' => $participant->betalt_beloeb,
      ];
    }
    
    return $result;
  }

  public function loadPayments() {
    $query = 
      "SELECT pu.participant_id, pu.payment_display_id, pr.id, pr.amount, pr.created, pr.fl_id, pr.completed,
        pr.confirmed, pr.status, d.betalt_beloeb, d.original_price 
      FROM payment_users AS pu
      JOIN payment_registrations AS pr ON pu.payment_user_id = pr.payment_user_id
      JOIN deltagere AS d ON pu.participant_id = d.id
      ORDER BY pr.status, pr.created";

    $result = $this->db->query($query);

    foreach ($result as $index => $row) {
      if ($row['status'] == 'pending') {
        $participant = $this->createEntity('Deltagere')->findById($row['participant_id']);
        $result[$index]['current_price'] = $participant->calcSignupTotal();
      }
    }

    return $result;
  }

  public function parsePaymentSheet($file){
    $csv = file_get_contents($file['tmp_name']);
    $lines = explode("\n", $csv);
    $data = new stdClass();
    
    $data->header_text= [];
    foreach(explode(";", $lines[0]) as $index => $value){
      $data->header_text[$index] = $value;
    }
    unset($lines[0]);
    
    $data->rows = [];
    foreach ($lines as $line) {
      if ($line === '') continue;
      $row = [];
      foreach(explode(";", $line) as $index => $value) {
        $row[$index] = $value;
      }
      $data->rows[] = $row;
    }
    return $data;
  }

  public function matchPayments(&$sheet_data, &$registered) {
    foreach ($sheet_data->rows as $row_id => $row) {
      $paricipant_id = substr($row[1], 18);
      $typecheck = $row[4] == 'Fastaval 2026 tilmelding';
      $amount = str_ireplace(['.',',00'], '', $row[8]);

      $matches = [];
      foreach ($registered as $key => $payment) {
        $matching = [];
        if ($payment['participant_id'] == $paricipant_id) $matching['participant'] = true;
        if ($payment['payment_display_id'] == $row[0]) $matching['display_id'] = true;
        if ($payment['fl_id'] == $row[3]) $matching['payment_id'] = true;
        if ($payment['amount'] == $amount) $matching['amount'] = true;
        $matching['text'] = $typecheck;

        if (count($matching) == 5) {
          $matches[] = $matching;
          $registered[$key]['match'] = $matching;
          $sheet_data->rows[$row_id]['group'] = 'full';
          $sheet_data->rows[$row_id]['match'] = $matching;
          $sheet_data->rows[$row_id]['match_id'] = $payment['id'];

          if ($payment['status'] == 'confirmed') $sheet_data->rows[$row_id]['group'] = 'processed';
          continue 2;
        }

        if (isset($matching['participant'])) {
          if ($matching['amount']) {
            $matches[] = $matching;
            $registered[$key]['match'] = $matching;
            $sheet_data->rows[$row_id]['group'] = 'essential';
            $sheet_data->rows[$row_id]['match'] = $matching;
            $sheet_data->rows[$row_id]['match_id'] = $payment['id'];
          } else if (!isset($sheet_data->rows[$row_id]['match'])) {
            $matches[] = $matching;
            $registered[$key]['match'] = $matching;
            $sheet_data->rows[$row_id]['group'] = 'paticipant';
            $sheet_data->rows[$row_id]['match'] = $matching;
          }
        }
      }
      
      if (count($matches) == 0) {
        $sheet_data->rows[$row_id]['group'] = 'none';
      }
    }

    uasort($sheet_data->rows, function($a, $b) {
      $list = [
        'full' => 1,
        'essential' => 2,
        'participant' => 3,
        'none' => 4,
        'processed' => 5,
      ];

      return $list[$a['group']] - $list[$b['group']];
    });
  }

  public function confirmSinglePayment($participant_id, $sheet_data, $payment_id = null) {
    $participant = $this->createEntity('Deltagere')->findById($participant_id);

    // Check if we already processed this payment to avoid double processing
    if(str_contains($participant->paid_note ,"Betaling ID:$payment_id")) {
      return [
        'status' => 'success',
        'message' => 'already processed',
      ];    
    }
  
    $completed = DateTimeImmutable::createFromFormat("Y-m-d", $sheet_data[5])->getTimestamp();
    $confirmed = time();

    $amount = str_ireplace(['.',',00'], '', $sheet_data[8]);

    if (isset($payment_id)) {
      $query = "UPDATE payment_registrations SET fl_id=?, completed=?, confirmed=?, amount=?, status='confirmed' WHERE id=?";
      $this->db->exec($query, [$sheet_data[3], $completed, $confirmed, $amount, $payment_id]);
    } else {
      $query = "INSERT INTO payment_registrations (payment_user_id, amount, created, fl_id, completed, confirmed, status) (?,?,?,?,?,'confirmed')";
      $payment_id = $this->db->exec($query, [$sheet_data[0], $amount, $confirmed, $sheet_data[3], $completed, $confirmed]);
    }

    $participant->betalt_beloeb += intval($amount);

    // Update note with payment info
    $note = "Betalt via Bifrost"
        .PHP_EOL."Betaling ID:$payment_id Dato:$sheet_data[5] Beløb:$amount";
    $participant->paid_note ? $participant->paid_note .= PHP_EOL.$note : $participant->paid_note = $note;
    
    // Update participant
    if ($participant->update()) {
      $this->log(
          "Deltager $participant_id fik bogført on-line betaling $payment_id via Bifrost af {$this->getLoggedInUser()->user}",
          'Betaling',
          $this->getLoggedInUser()
      );
      return [
        'status' => 'success',
      ];
    }
    return [
      'status' => 'error',
      'message' => "Kunne ikke opdatere deltager $participant_id"
    ];
  }

  public function cancelPendingPayment($payment_id) {
    $query = "SELECT * FROM payment_registrations WHERE id = ?";
    $result = $this->db->query($query, [$payment_id]);
    if (empty($result)) {
      return [
        'status' => 'error',
        'message' => 'Payment ID not found',
      ];
    }

    if ($result[0]['status'] !== 'pending') {
      return [
        'status' => 'error',
        'message' => 'Payment status is not pending',
      ];
    }

    $query = "UPDATE payment_registrations SET status = 'cancelled' WHERE id = ?";
    $this->db->exec($query, [$payment_id]);

    return [
      'status' => 'success',
      'message' => 'Payment has been cancelled',
    ];
  }
}
