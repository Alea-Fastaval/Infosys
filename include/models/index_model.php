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
 * handles all data fetching for the index controller
 *
 * @category Infosys
 * @package  Models
 * @author   Peter Lind <peter.e.lind@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl.html GPL 3
 * @link     http://www.github.com/Fake51/Infosys
 */
class IndexModel extends Model
{
    /**
     * performs a wildcard search across deltagere, aktiviteter and lokaler
     *
     * @param string $searchterm - wildcard term to search for
     * @access public
     * @return array
     */
    public function wildcardSearch($searchterm)
    {
        $results   = array();
        $deltagere = $this->createEntity('Deltagere')->wildcardSearch($searchterm);
        if (!empty($deltagere)) {
            $results['Deltagere']        = $deltagere;
            $results['deltagere_fields'] = $this->createEntity('Deltagere')->getColumns();
        }

        $aktiviteter = $this->createEntity('Aktiviteter')->wildcardSearch($searchterm);
        if (!empty($aktiviteter)) {
            $results['Aktiviteter']        = $aktiviteter;
            $results['aktiviteter_fields'] = $this->createEntity('Aktiviteter')->getColumns();
        }

        return $results;
    }

    /**
     * generates statistics about participants
     *
     * @access public
     * @return array
     */
    public function generateParticipantStats()
    {
        $stats = array();
        $query = 'SELECT COUNT(*) AS count FROM deltagere WHERE signed_up > "0000-00-00" AND annulled = "nej"';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            $stats['overall_signups'] = $result[0]['count'];
        } else {
            $stats['overall_signups'] = '';
        }

        $query = 'SELECT COUNT(*) AS count FROM deltagere WHERE checkin_time > "0000-00-00"';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            $stats['overall_checkins'] = $result[0]['count'];
        } else {
            $stats['overall_checkins'] = '';
        }

        $query = 'SELECT COUNT(*) AS count FROM deltagere WHERE signed_up > NOW() - INTERVAL 24 hour AND annulled = "nej"';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            $stats['24h_signups'] = $result[0]['count'];
        } else {
            $stats['24h_signups'] = '';
        }

        $query = 'SELECT COUNT(*) AS count FROM deltagere WHERE checkin_time > NOW() - INTERVAL 24 hour AND annulled ="nej"';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            $stats['24h_checkins'] = $result[0]['count'];
        } else {
            $stats['24h_checkins'] = '';
        }

        $query = 'SELECT COUNT(*) AS count FROM deltagere WHERE signed_up > NOW() - INTERVAL 7 day AND annulled ="nej"';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            $stats['7d_signups'] = $result[0]['count'];
        } else {
            $stats['7d_signups'] = '';
        }

        $query = 'SELECT COUNT(*) AS count FROM deltagere WHERE checkin_time > NOW() - INTERVAL 3 day AND annulled = "nej"';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            $stats['3d_checkins'] = $result[0]['count'];
        } else {
            $stats['3d_checkins'] = '';
        }

        $query = 'SELECT COUNT(*) AS count FROM deltagere WHERE udeblevet = "ja"';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            $stats['no_shows'] = $result[0]['count'];
        } else {
            $stats['no_shows'] = '';
        }

        $query = 'SELECT bk.navn, COUNT(*) AS count FROM deltagere AS d JOIN brugerkategorier AS bk ON bk.id = d.brugerkategori_id WHERE signed_up > "0000-00-00" AND annulled = "nej" GROUP BY bk.navn ORDER BY bk.navn';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            foreach ($result as $row) {
                $stats['kategori'][$row['navn']] = $row['count'];
            }
        }

        $query = '
SELECT
    CASE
        WHEN birthdate > NOW() - INTERVAL 10 YEAR THEN "0-10"
        WHEN birthdate > NOW() - INTERVAL 20 YEAR THEN "10-20"
        WHEN birthdate > NOW() - INTERVAL 30 YEAR THEN "20-30"
        WHEN birthdate > NOW() - INTERVAL 40 YEAR THEN "30-40"
        WHEN birthdate > NOW() - INTERVAL 50 YEAR THEN "40-50"
        ELSE "50+"
        END AS age_group,
    COUNT(*) AS count
FROM
    deltagere AS d
WHERE
    signed_up > "0000-00-00"
    AND annulled = "nej"
GROUP BY
    age_group
ORDER BY
    age_group
';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            foreach ($result as $row) {
                $stats['age_group'][$row['age_group']] = $row['count'];
            }
        }

        $query = "SELECT AVG(TIMESTAMPDIFF(year, birthdate, NOW())) AS average_age FROM deltagere";
        if (($result = $this->db->query($query)) && !empty($result[0])) {
            $stats['average_age'] = $result[0]['average_age'];
        }

        $page_file = SIGNUP_FOLDER."pages/practical.json";
        if(is_file($page_file)) {
            $page = json_decode(file_get_contents($page_file));
            if (isset($page->sections)) foreach($page->sections as $section) {
                if (isset($section->items)) foreach($section->items as $item) {
                    if (isset($item->infosys_id) && $item->infosys_id === 'knows_fastaval_from') {
                        $cat_select = $item;
                        break 2;
                    }
                }
            }

            if (isset($cat_select)) {
                $cat = [];
                foreach ($cat_select->options as $option) {
                    $cat[$option->value] = $option->text->da;
                } 
            }
        }

        $query = "SELECT knows_fastaval_from, COUNT(*) as count FROM deltagere GROUP BY knows_fastaval_from";
        if (($result = $this->db->query($query)) && !empty($result[0])) {
            foreach ($result as $row) {
                $category = $cat[$row['knows_fastaval_from']] ?? $row['knows_fastaval_from'];
                $stats['knows_fastaval_from'][$category] = $row['count'];
            }
        }

        return $stats;
    }

    /**
     * generates statistics about wear
     *
     * @access public
     * @return array
     */
    public function generateWearStats()
    {
        $stats = array();

        $query = '
SELECT
    w.navn,
    dw.received,
    SUM(dw.antal) AS count
FROM
    wear AS w
    JOIN wearpriser AS wp ON wp.wear_id = w.id
    JOIN deltagere_wear_order AS dw ON wp.id = dw.wearpris_id
    JOIN deltagere AS d ON d.id = dw.deltager_id
WHERE
    d.annulled = "nej"
GROUP BY
    w.navn,
    dw.received
ORDER BY
    w.navn
';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            foreach ($result as $row) {
                if (!isset($stats['types'][$row['navn']])) {
                    $stats['types'][$row['navn']] = 0;
                }

                if (!isset($stats['received'][$row['navn']])) {
                    $stats['received'][$row['navn']] = 0;
                }

                $stats['types'][$row['navn']] += $row['count'];

                if ($row['received'] == 't') {
                    $stats['received'][$row['navn']] += $row['count'];
                }
            }
        }

        return $stats;
    }

    /**
     * generates statistics about food
     *
     * @access public
     * @return array
     */
    public function generateFoodStats()
    {
        $stats = array();

        $query = '
SELECT
    m.kategori AS navn,
    dm.received,
    COUNT(*) AS count
FROM
    mad AS m
    JOIN madtider AS mt ON mt.mad_id = m.id
    JOIN deltagere_madtider AS dm ON mt.id = dm.madtid_id
    JOIN deltagere AS d ON d.id = dm.deltager_id
WHERE
    d.annulled = "nej"
GROUP BY
    m.kategori,
    dm.received
ORDER BY
    m.kategori
';

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            foreach ($result as $row) {
                if (!isset($stats['types'][$row['navn']])) {
                    $stats['types'][$row['navn']] = 0;
                }

                if (!isset($stats['received'][$row['navn']])) {
                    $stats['received'][$row['navn']] = 0;
                }

                $stats['types'][$row['navn']] += $row['count'];

                if (!empty($row['received'])) {
                    $stats['received'][$row['navn']] += $row['count'];
                }
            }
        }

        return $stats;
    }

    /**
     * generates statistics about entrance
     *
     * @access public
     * @return array
     */
    public function generateEntranceStats()
    {
        $stats = array();

        $query = '
SELECT
    i.type AS navn,
    COUNT(*) AS count
FROM
    indgang AS i
    JOIN deltagere_indgang AS di ON i.id = di.indgang_id
    JOIN deltagere AS d ON d.id = di.deltager_id
WHERE
    d.annulled = "nej"
GROUP BY
    i.type
ORDER BY
    i.type
';

        foreach ($this->createEntity('Indgang')->findAll() as $entrance) {
            $stats['types'][$entrance->type] = 0;
        }

        if (($result = $this->db->query($query)) && !empty($result[0])) {
            foreach ($result as $row) {
                $stats['types'][$row['navn']] += $row['count'];
            }
        }

        ksort($stats['types']);

        return $stats;
    }

    /**
     * sends out messages automatically to notify about
     * activities and diy services
     *
     * @access public
     * @return void
     */
    public function sendAutomaticMessages()
    {
        $time   = date('Y-m-d H:i:s');
        $log    = $this->dic->get('Log');

        $firebase = new Firebase($this->config);

        $start = new DateTime($this->config->get('con.start'));
        $end   = new DateTime($this->config->get('con.end'));

        if (time() < $start->getTimestamp() || time() > $end->getTimestamp()) {
            $this->fileLog("Running auto message script outside con times");
            $log->logToDB("Kan ikke sende automatiske beskeder - udenfor tidsperioden", 'Messages', 0);
            return;
        }

        $activities = $this->getActivitiesForAutoSend($time);
        $count = $error = 0;
        foreach ($activities as $activity) {
            $deltager = $this->createEntity('Deltagere')->findById($activity['deltager_id']);
            $hold     = $this->createEntity('Hold')->findById($activity['hold_id']);

            if (empty($deltager->gcm_id) || !$hold) {
                continue;
            }

            $aktivitet = $hold->getAktivitet();
            $afvikling = $hold->getAfvikling();

            $firstname = $deltager->getShortName();
            $tid       = date('H:i', strtotime($afvikling->start));

            $message_da = "Hej {$firstname}. Om lidt kl {$tid} skal du være med til {$aktivitet->navn}";
            $message_en = "Hello {$firstname}. Soon at {$tid} you're supposed to join {$aktivitet->title_en}";

            if ($afvikling->lokale_id && ($lokale = $this->createEntity('Lokaler')->findById($afvikling->lokale_id))) {
                $message_da .= " i lokale/lokation {$lokale->beskrivelse}";
                $message_en .= " in room/at location {$lokale->beskrivelse}";
            }
            
            $query = "INSERT INTO messages (text_da, text_en, send_time) VALUES (?,?, NOW())";
            $args = [$message_da, $message_en];
            $message_id = $this->db->exec($query, $args);

            // $recipient = $this->createEntity('Deltagere')->findById(1);
            $recipient = $deltager;
            if ($this->sendMessage($recipient, $deltager->speaksDanish() ? $message_da : $message_en ,$message_id, $firebase)) {
                $count++;
            } else {
                $error++;
            }
        }
        $log->logToDB("InfoSys har sendt beskeder om spilstart. {$count} lykkedes, {$error} fejlede", 'Firebase', 1);

        $diy   = $this->getDIYForAutoSend($time);
        $count = $error = 0;
        foreach ($diy as $res) {
            $deltager = $this->createEntity('Deltagere')->findById($res['deltager_id']);
            $vagt     = $this->createEntity('GDSVagter')->findById($res['gdsvagt_id']);

            if (empty($deltager->gcm_id) || !$vagt) {
                continue;
            }

            $gds = $vagt->getGDS();

            $firstname = $deltager->getShortName();
            $tid       = date('H:i', strtotime($vagt->start));

            $message_da   = "Hej {$firstname}. Din {$gds->navn} helte-tjans starter om lidt - kl.{$tid} :-) Masser af tak og kram fra Fastaval";
            $message_en   = "Hello {$firstname}. Your {$gds->title_en} hero task starts soon - {$tid} :-) Many thanks and hugs from Fastaval";
            
            $query = "INSERT INTO messages (text_da, text_en, send_time) VALUES (?,?, NOW())";
            $args = [$message_da, $message_en];
            $message_id = $this->db->exec($query, $args);

            // $recipient = $this->createEntity('Deltagere')->findById(1);
            $recipient = $deltager;
            if ($this->sendMessage($recipient, $deltager->speaksDanish() ? $message_da : $message_en ,$message_id, $firebase, ['window' => 'gds'])) {
                $count++;
            } else {
                $error++;
            }
        }
        $log->logToDB("InfoSys har sendt beskeder til helte. {$count} lykkedes, {$error} fejlede", 'Firebase', 1);

        $diy   = $this->getTomorrowsDiyForAutoSend($time);
        $count = $error = 0;
        foreach ($diy as $res) {
            $deltager = $this->createEntity('Deltagere')->findById($res['deltager_id']);
            $vagt     = $this->createEntity('GDSVagter')->findById($res['gdsvagt_id']);

            if (empty($deltager->gcm_id) || !$vagt) {
                continue;
            }

            $gds = $vagt->getGDS();

            $firstname = $deltager->getShortName();
            $tid       = date('H:i', strtotime($vagt->start));

            $message_da   = "Hej {$firstname}. Din {$gds->navn} helte-tjans starter om lidt - kl.{$tid} :-) Masser af tak og kram fra Fastaval";
            $message_en   = "Hello {$firstname}. Your {$gds->title_en} hero task starts soon - {$tid} :-) Many thanks and hugs from Fastaval";
            
            $query = "INSERT INTO messages (text_da, text_en, send_time) VALUES (?,?, NOW())";
            $args = [$message_da, $message_en];
            $message_id = $this->db->exec($query, $args);

            // $recipient = $this->createEntity('Deltagere')->findById(1);
            $recipient = $deltager;
            if ($this->sendMessage($recipient, $deltager->speaksDanish() ? $message_da : $message_en ,$message_id, $firebase)) {
                $count++;
            } else {
                $error++;
            }
        }
        $log->logToDB("InfoSys har sendt beskeder til mogendagens helte. {$count} lykkedes, {$error} fejlede", 'Firebase', 1);
    }

    function sendMessage($receiver, $message, $message_id, $firebase, $data = null) {
        try {
            $query = "INSERT INTO participant_messages (message_id, participant_id) VALUES (?,?)";
            $args = [$message_id, $receiver->id];
            $this->db->exec($query, $args);

            if ($receiver->gcm_id) { // Sending via Firebase
                if (!$firebase->sendMessage($message, $receiver->gcm_id, $data)) {
                    $error = $firebase->getError();

                    switch($error['code']) {
                        case 'UNKNOWN':
                            $this->fileLog("Unknown error sending firebase message: ".$firebase->getResponse());
                            break;

                        case "UNREGISTERED":
                            $receiver->gcm_id = '';
                            $receiver->update();
                            $this->log("Deltager #$receiver->id Firebase token er udløbet og token er derfor blevet slettet", 'Deltager', null);
                            break;
                    }

                    $result = "Failed";
                } else {
                    $result = "Success";
                }
                $this->log("Sent Firebase notification to participant #$receiver->id Result: $result", 'Besked', $this->getLoggedInUser());
            }
        } catch (Exception $e) {
            $this->fileLog($e->getMessage());
            $this->log('Failed notification to participant #' . $receiver->id . '. Error: ' . explode("\n",$e->getMessage())[0] , 'Besked', $this->getLoggedInUser());
            $result = "Failed";
        }

        return $result;
    }

    /**
     * checks if the recipient is valid for sending sms messages to
     *
     * @param Deltagere $participant Participant to send to
     *
     * @access public
     * @return bool
     */
    public function canSendAutoSmsToParticipant($participant)
    {
        return $participant && ( $participant->medbringer_mobil === 'ja' || !empty($participant->gcm_id) || !empty($participant->apple_id));
    }

    /**
     * sends out messages for upcoming activities
     *
     * @param array $activities Activities to send out messages for
     *
     * @access public
     * @return int
     */
    public function sendActivityMessages($activities, SMSSending $sender)
    {
        $count = 0;

        foreach ($activities as $activity) {
            $deltager = $this->createEntity('Deltagere')->findById($activity['deltager_id']);
            $hold     = $this->createEntity('Hold')->findById($activity['hold_id']);

            if (!$this->canSendAutoSmsToParticipant($deltager) || !$hold) {
                continue;
            }

            $aktivitet = $hold->getAktivitet();
            $afvikling = $hold->getAfvikling();

            $firstname = $deltager->getShortName();
            $title     = $aktivitet->navn;

            if ($afvikling->lokale_id && ($lokale = $this->createEntity('Lokaler')->findById($afvikling->lokale_id))) {
                $room = " i lokale {$lokale->beskrivelse}";
            } else {
                $room = "";
            }

            if ($activity['type'] == 'spilleder') {
                $message = "Hej {$firstname}. Om lidt skal du være spilleder til {$title}{$room} - mvh. Fastaval";
            } else {
                $message = "Hej {$firstname}. Om lidt skal du spille {$title}{$room} - mvh. Fastaval";
            }

            if ($deltager->sendSMS($sender, $message)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * returns activities
     *
     * @param string $datetime Time to calculate for
     *
     * @access public
     * @return void
     */
    public function getActivitiesForAutoSend($datetime)
    {
        // grab activities
        $query = <<<SQL
SELECT
    pladser.*
FROM
    pladser,
    hold
WHERE
    pladser.hold_id = hold.id
    AND hold.afvikling_id IN (
        SELECT af.id
        FROM afviklinger AS af
        JOIN aktiviteter AS ak ON ak.id = af.aktivitet_id
        WHERE start >= ? + interval 15 minute
        AND start < ? + interval 30 minute
        AND ak.hidden = 'nej'
        UNION
        SELECT afm.afvikling_id
        FROM afviklinger_multiblok AS afm
        JOIN afviklinger AS af ON af.id = afm.afvikling_id
        JOIN aktiviteter AS ak ON ak.id = af.aktivitet_id
        WHERE afm.start >= ? + interval 15 minute
        AND afm.start < ? + interval 30 minute
        AND ak.hidden = 'nej'
    )
SQL;

        return $this->db->query($query, array($datetime, $datetime, $datetime, $datetime));
    }

    /**
     * sends DIY message reminders to participants
     *
     * @param array $diy_activities DIY activities to send messages for
     *
     * @access public
     * @return int
     */
    public function sendDIYMessages($diy_activities, $sender)
    {
        $count = 0;
        foreach ($diy_activities as $res) {
            $deltager = $this->createEntity('Deltagere')->findById($res['deltager_id']);
            $vagt     = $this->createEntity('GDSVagter')->findById($res['gdsvagt_id']);

            if (!$this->canSendAutoSmsToParticipant($deltager) || !$vagt) {
                continue;
            }

            $gds = $vagt->getGDS();

            $firstname = $deltager->getShortName();
            $title     = $gds->navn;
            $tid       = date('H:i', strtotime($vagt->start));
            $message   = "Hej {$firstname}. Din {$title} helte-tjans starter om lidt - kl.{$tid} :-) Masser af tak og kram fra Fastaval";
            $count++;

            $deltager->sendSMS($sender, $message);
        }

        return $count;
    }

    /**
     * returns diy activities
     *
     * @param string $datetime Time to calculate for
     *
     * @access public
     * @return void
     */
    public function getDIYForAutoSend($datetime)
    {
        // grab diy
    $query = <<<SQL
SELECT
    deltagere_gdsvagter.*
FROM
    deltagere_gdsvagter,
    gdsvagter
WHERE
    deltagere_gdsvagter.gdsvagt_id = gdsvagter.id
    AND gdsvagter.start >= ? + interval 15 minute
    AND gdsvagter.start < ? + interval 30 minute
SQL;

        return $this->db->query($query, array($datetime, $datetime));
    }

    /**
     * sends DIY message reminders to participants
     *
     * @param array $diy_activities DIY activities to send messages for
     *
     * @access public
     * @return int
     */
    public function sendTomorrowsDiyMessages($diy_activities, $sender)
    {
        $count = 0;

        foreach ($diy_activities as $res) {
            $deltager = $this->createEntity('Deltagere')->findById($res['deltager_id']);
            $vagt     = $this->createEntity('GDSVagter')->findById($res['gdsvagt_id']);

            if (!$this->canSendAutoSmsToParticipant($deltager) || !$vagt) {
                continue;
            }

            $gds = $vagt->getGDS();

            $firstname = $deltager->getShortName();
            $title     = $gds->navn;
            $tid       = date('H:i', strtotime($vagt->start));
            $message   = "Hej {$firstname}. Obs! Husk du har en {$title} GDS-tjans i morgen kl.{$tid} :-) Masser af tak og kram fra Fastaval";
            $count++;

            $deltager->sendSMS($sender, $message);
        }

        return $count;
    }

    /**
     * returns diy activities
     *
     * @param string $datetime Time to calculate for
     *
     * @access public
     * @return void
     */
    public function getTomorrowsDiyForAutoSend($datetime)
    {
        $timestamp = strtotime($datetime);

        if (strtotime(date('Y-m-d') . ' 20:30:00') < $timestamp && $timestamp < strtotime(date('Y-m-d') . ' 20:40:00')) {

            $start = date('Y-m-d', strtotime('tomorrow')) . ' 06:30:00';
            $end   = date('Y-m-d', strtotime('tomorrow')) . ' 10:30:00';

            // grab diy
        $query = <<<SQL
    SELECT
        deltagere_gdsvagter.*
    FROM
        deltagere_gdsvagter,
        gdsvagter
    WHERE
        deltagere_gdsvagter.gdsvagt_id = gdsvagter.id
        AND gdsvagter.start >= ?
        AND gdsvagter.start <= ?
SQL;

            return $this->db->query($query, array($start, $end));
        }

        return [];
    }

    /**
     * attempts to send a password reset email to the provided address
     *
     * @param string $email_address Address to send to
     * @param Page   $page          Page object for rendering email
     *
     * @access public
     * @return bool
     */
    public function sendPasswordResetEmail($email_address, Page $page)
    {
        if (!filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $user = $this->createEntity('User')->findByEmail($email_address);

        if (!$user || $user->isDisabled()) {
            return false;
        }

        $page->setTemplate('index/passwordresetemail');

        $user->password_reset_hash = md5(password_hash($user->pass, PASSWORD_DEFAULT));
        $user->password_reset_time = date('Y-m-d H:i:s');
        $user->update();

        $page->link = $this->url('reset_pass', array('hash' => $user->password_reset_hash));
        $page->site = $this->config->get('app.sitename');

        // send email
        $mail = new Mail($this->config);

        $mail->setFrom($this->config->get('app.email_address'), $this->config->get('app.email_alias'))
            ->setRecipient($user->user)
            ->setSubject($this->config->get('app.sitename') . ': Password reset request received')
            ->setBodyFromPage($page);

        return $mail->send();
    }

    /**
     * attempts to locate a user for password reset, using a hash and checking
     * for proper time
     *
     * @param string $hash Hash to locate user by
     *
     * @access public
     * @return User|false
     */
    public function getUserForPasswordReset($hash)
    {
        $user = $this->createEntity('User');
        $select = $user->getSelect()
            ->setWhere('password_reset_hash', '=', $hash)
            ->setWhere('password_reset_time', '>=', date('Y-m-d H:i:s', time() - 20 * 60));

        return $user->findBySelect($select);

    }
}
