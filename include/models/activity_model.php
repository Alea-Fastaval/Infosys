<?php
    /**
     * Copyright (C) 2009  Peter Lind
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
     * @package    MVC
     * @subpackage Models
     * @author     Peter Lind <peter.e.lind@gmail.com>
     * @copyright  2009 Peter Lind
     * @license    http://www.gnu.org/licenses/gpl.html GPL 3
     * @link       http://www.github.com/Fake51/Infosys
     */

    /**
     * handles all data fetching for the activity MVC
     *
     * @package    MVC
     * @subpackage Models
     * @author     Peter Lind <peter.e.lind@gmail.com>
     */
class ActivityModel extends Model {

    /**
     * finds all Aktivitet entities
     *
     * @access public
     * @return bool|array
     */
    public function findAll()
    {
        $activities = array();
        foreach ($this->createEntity('Aktiviteter')->findAll() as $activity) {
            $activities[$activity->type][] = $activity;
        }

        foreach ($activities as $type => $bunch) {
            usort($activities[$type], function($a, $b) {return strcmp($a->navn, $b->navn);});
        }

        return $activities;
    }

    /**
     * returns list of times an activity is run, given the activity
     *
     * @param object $aktivitet - Aktiviteter entity
     * @access public
     * @return array
     */
    public function getAfviklingerForAktivitet($aktivitet)
    {
        if (!is_object($aktivitet) || !$aktivitet->isLoaded())
        {
            return array();
        }
        $select = $this->createEntity('Afviklinger')->getSelect();
        $select->setWhere('aktivitet_id','=',$aktivitet->id);
        return $this->createEntity('Afviklinger')->findBySelectMany($select);
    }

    /**
     * returns array of available activity types
     *
     * @access public
     * @return array
     */
    public function getActivityTypes()
    {
        return $this->createEntity('Aktiviteter')->getAvailableTypes();
    }


    /**
     * updates a given activity with data from POST
     *
     * @param object $activity - Aktiviteter entity
     * @param array $post - Aktivitet array from POST data
     * @access public
     * @return bool
     */
    public function updateActivity($activity, RequestVars $post)
    {
        if (!is_object($activity) || !$activity->isLoaded()) {
            return false;
        }

        foreach ($post->aktivitet as $field => $val) {
            $activity->$field = $val;
        }

        $activity->varighed_per_afvikling = str_replace(',', '.', $activity->varighed_per_afvikling);
        $activity->updated                = date('Y-m-d H:i:s');

        if (!$activity->update()) {
            return false;
        }

        if (empty($post->max_age)) {
            $activity->removeMaxAge();

        } else {
            $activity->setMaxAge($post->max_age);
        }

        if (empty($post->min_age)) {
            $activity->removeMinAge();

        } else {
            $activity->setMinAge($post->min_age);
        }

        return true;
    }

    /**
     * creates an activity with data from POST
     *
     * @param array $post - Aktivitet array from POST data
     * @access public
     * @return bool|object
     */
    public function opretAktivitet(RequestVars $post)
    {
        $activity = $this->createEntity('Aktiviteter');
        foreach ($post->aktivitet as $field => $detail) {
            $activity->$field = $detail;
        }

        $activity->min_deltagere_per_hold = intval($activity->min_deltagere_per_hold);
        $activity->max_deltagere_per_hold = intval($activity->max_deltagere_per_hold);
        $activity->spilledere_per_hold    = intval($activity->spilledere_per_hold);
        $activity->varighed_per_afvikling = floatval(str_replace(',', '.', $activity->varighed_per_afvikling));
        $activity->pris                   = intval($activity->pris);
        $activity->max_signups            = intval($activity->max_signups);

        $activity->wp_link   = $activity->wp_link ? $activity->wp_link : 0;
        $activity->teaser_dk = $activity->teaser_dk ? $activity->teaser_dk : '';
        $activity->teaser_en = $activity->teaser_en ? $activity->teaser_en : '';
        $activity->updated   = date('Y-m-d H:i:s');

        if (!$activity->insert()) {
            return false;
        }

        if (empty($post->max_age)) {
            $activity->removeMaxAge();

        } else {
            $activity->setMaxAge($post->max_age);
        }

        if (empty($post->min_age)) {
            $activity->removeMinAge();

        } else {
            $activity->setMinAge($post->min_age);
        }

        return $activity;
    }

    public function getActivityHeader(){
        $activity = $this->createEntity('Aktiviteter');
        $fields = $activity->getColumns();
        $fields[] = 'age_min';
        $fields[] = 'age_max';
        return $fields;
    }    

    public static function parseActivityData($data){
        $result = [];
        foreach ($data as $row) {
            $result[] = self::parseActivityRow($row);
        }
        return $result;
    }

    public static function parseActivityRow($row){
        for($i = 0; $i <= 26; $i++){
            $row[$i] = isset($row[$i]) ? $row[$i] : "";
        }

        $result_row = [];
        $result_row['id'] = is_numeric($row[26]) ? intval($row[26]) : null;
        $result_row['navn'] = $row[1];
        $result_row['kan_tilmeldes'] = preg_match("/nej/", strtolower($row[24])) == 1 ? 'nej' : 'ja';;
        $result_row['note'] = $row[22];
        $result_row['foromtale'] = $row[17];
        preg_match("/\d+/", $row[12], $matches);
        $result_row['varighed_per_afvikling'] = isset($matches[0]) ? $matches[0] : 0;
        preg_match("/\d+/", $row[6], $matches);
        $result_row['min_deltagere_per_hold'] = isset($matches[0]) ? $matches[0] : 0;
        preg_match("/\d+/", $row[7], $matches);
        $result_row['max_deltagere_per_hold'] = isset($matches[0]) ? $matches[0] : 0;
        preg_match("/\d+/", $row[8], $matches);
        $result_row['spilledere_per_hold'] = isset($matches[0]) ? $matches[0] : 0;
        preg_match("/\d+/", $row[9], $matches);
        $result_row['pris'] = isset($matches[0]) ? $matches[0] : 0;
        $result_row['lokale_eksklusiv'] = preg_match("/nej/", strtolower($row[19])) == 1 ? 'nej' : 'ja';
        $result_row['wp_link'] = is_numeric($row[26]) ? intval($row[26]) : 0;
        $result_row['teaser_dk'] = $row[13];
        $result_row['teaser_en'] = $row[14];
        $result_row['title_en'] = $row[2];;
        $result_row['description_en'] = $row[18];
        $result_row['author'] = $row[3];;
        // parsing type from input
        $type = strtolower($row[4]);
        if (preg_match("/junior/", $type)) {
            $type = 'junior';
        } elseif (preg_match("/live/", $type)) {
            $type = 'live';
        } elseif (preg_match("/brætspil|braet/", $type)) {
            $type = 'braet';
        } elseif(preg_match("/rolle(spil)?/", $type)) {
            $type = 'rolle';
        } elseif (preg_match("/figur/", $type)) {
            $type = 'figur';
        } elseif(preg_match("/magic/", $type)) {
            $type = 'magic';
        } elseif(preg_match("/workshop/", $type)) {
            $type = 'workshop';
        } elseif(preg_match("/system/", $type) || preg_match("/spillederbriefing/", strtolower($result_row['navn']))) {
            $type = 'system';
        } else {
            $type = 'ottoviteter';
        }
        $result_row['type'] = $type;

        $result_row['tids_eksklusiv'] = preg_match("/nej/", strtolower($row[20])) == 1 ? 'nej' : 'ja';
        
        $sprog = preg_match("/d/", strtolower($row[5])) == 1 ? 'dansk' : '';
        $engelsk = $sprog == '' ? 'engelsk' : '+engelsk';
        $sprog .= preg_match("/e/", strtolower($row[5])) == 1 ? $engelsk : '';
        $result_row['sprog'] = $sprog;

        $result_row['replayable'] = preg_match("/ja/", strtolower($row[15])) == 1 ? 'nej' : 'ja'; // If not one-time event
        $result_row['updated'] = self::parseDateTime($row[0]);
        $result_row['hidden'] = preg_match("/ja/", strtolower($row[21])) == 1 ? 'ja' : 'nej';
        preg_match("/\d/", $row[23], $matches);
        $result_row['karmatype'] = isset($matches[0]) ? $matches[0] : 0;
        preg_match("/\d+/", $row[16], $matches);
        $result_row['max_signups'] = isset($matches[0]) ? $matches[0] : 0;
        $result_row['age_min'] = $row[10];
        $result_row['age_max'] = $row[11];
        
        return $result_row;
    }

    /**
     * Simple method for parsing timestamp or just give current time
     * 
     *  @return parsed timestamp or if parsing failed 
     */
    private static function parseDateTime($dateTime){
        // parse time
        if (preg_match("/\b(\d{2})[:\.](\d{2})[:\.](\d{2})\b/",$dateTime, $matches)){
            $time = "$matches[1]:$matches[2]:$matches[3]";
        } else {
            $time = date("H:i:s");
        }

        // parse date
        if (preg_match("/(\d{4})[-\/](\d{2})[-\/](\d{2})/",$dateTime, $matches)){
            $date = "$matches[1]-$matches[2]-$matches[3]";
        } elseif (preg_match("/(\d{2})[-\/](\d{2})[-\/](\d{4})/",$dateTime, $matches)){
            $date = "$matches[3]-$matches[2]-$matches[1]";
        } else {
            $date = date("Y-m-d");
        }

        return "$date $time";
    }
	
	public function saveActivities($data, $replace = false) {
        if($replace === true) {
            // Check if we have any afviklinger before trying to replace activities
            if ($this->createEntity('Afviklinger')->findAll()
                    || $this->createEntity('AfviklingerMultiblok')->findAll())
            {
                throw new FrameworkException("Cannot replace activities after afviklinger has been created", 1);
            }

            $activity = $this->createEntity('Aktiviteter');            
            $activity->deleteAll();
        }

        $errors = 0;
        foreach($data as $row) {
            $activity = $this->createEntity('Aktiviteter');
            if (!$replace) {
                $exists = $activity->findById($row['id']);
            } else {
                $exists = false;
            }            

            foreach ($row as $key => $value){
                if ($key === 'age_min' || $key === 'age_max'){
                    continue;
                }

                $activity->$key = $value;            
            }

            if ($exists) {
                $activity->update();
            } else {
                $activity->insert();
            }
            
            // Values that have to be set after the activity is inserted
            // since they are in a different table with foreign key constraint
            if ($row['age_min'] !== '') {
                $activity->setMinAge($row['age_min']);
            }
            if ($row['age_max'] !== '') {
                $activity->setMaxAge($row['age_max']);
            }
        }
    }

    /**
     * Load all activities from database
     * for use with export function
     */
    public function loadActivities()
    {
        $activity = $this->createEntity('Aktiviteter');
        //$columns = $activity->getColumns();
        $activities = $activity->findAll();
        $columns = [
            'updated',                  //0
            'navn',                     //1
            'title_en',                 //2
            'author',                   //3
            'type',                     //4
            'sprog',                    //5
            'min_deltagere_per_hold',   //6
            'max_deltagere_per_hold',   //7
            'spilledere_per_hold',      //8
            'pris',                     //9
            'age_min',                  //10
            'age_max',                  //11
            'varighed_per_afvikling',   //12
            'teaser_dk',                //13
            'teaser_en',                //14
            'onetime',                  //15 inverse of replayable
            'max_signups',              //16
            'foromtale',                //17
            'description_en',           //18
            'lokale_eksklusiv',         //19
            'tids_eksklusiv',           //20
            'hidden',                   //21
            'note',                     //22
            'karmatype',                //23
            'kan_tilmældes',            //24
            'wp_link',                  //25
            'id',                       //26
        ];

        $list[] = $columns;
        foreach ($activities as $activity) {
            $fields = [];
            foreach ($columns as $column){
                switch($column) {
                    case 'age_min':
                        $age = $activity->getMinAge();
                        $fields[] = $age == false ? '' : $age;
                        break;
                    case 'age_max':
                        $age = $activity->getMaxAge();
                        $fields[] = $age == false ? '' : $age;
                        break;
                    case 'onetime':
                        $fields[] = $activity->replayable == 'nej' ? 'ja' : 'nej';
                        break;
                    default:
                        $fields[] = str_replace("\"","''",$activity->$column);
                }
            }
            $list[] = $fields;
        }

        //usort($activities[$type], function($a, $b) {return strcmp($a->navn, $b->navn);});
        return $list;
    }


    /**
     * creates a scheduling for an activity with data from POST
     *
     * @param object $activity - Aktiviteter entity
     * @param array $post - Aktivitet array from POST data
     * @access public
     * @return bool|object
     */
    public function opretAfvikling($activity, RequestVars $post)
    {
        if (!is_object($activity) || !$activity->isLoaded()) {
            return false;
        }

        if (!isset($post->start) || !isset($post->end)) {
            return false;
        }

        $start_timestamp = strtotime($post->start);
        $end_timestamp   = strtotime($post->end);

        if ($start_timestamp >= $end_timestamp) {
            return false;
        }

        $start = date('Y-m-d H:i:s', $start_timestamp);
        $end   = date('Y-m-d H:i:s', $end_timestamp);
        $afv   = $this->createEntity('Afviklinger');

        $select = $afv->getSelect()
            ->setWhere('start' , '=', $start)
            ->setWhere('slut','=',$end)
            ->setWhere('aktivitet_id','=',$activity->id);

        if ($afv->findBySelect($select)) {
            return false;
        }

        $afv->aktivitet_id = $activity->id;
        $afv->start        = $start;
        $afv->slut         = $end;
        $afv->lokale_id    = intval($post->lokale_id);
        $afv->note         = $post->note ? $post->note : '';

        if ($afv->insert()) {
            $activity->updated = date('Y-m-d H:i:s');
            $activity->update();

            return $afv;
        }

        return false;
    }


    /**
     * updates an activity schedule with data from POST
     *
     * @param object $afvikling - Afviklinger entity
     * @param array $post - Aktivitet array from POST data
     * @access public
     * @return bool|object
     */
    public function updateAfvikling($afvikling, RequestVars $post)
    {
        if (!is_object($afvikling) || !$afvikling->isLoaded()) {
            return false;
        }

        if (!isset($post->start) || !isset($post->end)) {
            return false;
        }

        $start_timestamp = strtotime($post->start);
        $end_timestamp   = strtotime($post->end);

        if ($start_timestamp >= $end_timestamp) {
            return false;
        }

        $start = date('Y-m-d H:i:s', $start_timestamp);
        $end   = date('Y-m-d H:i:s', $end_timestamp);
        $afv   = $this->createEntity('Afviklinger');

        $select = $afv->getSelect()
            ->setWhere('start','=',$start)
            ->setWhere('slut','=',$end)
            ->setWhere('aktivitet_id','=',$afvikling->aktivitet_id)
            ->setWhere('id','!=',$afvikling->id);

        if ($afv->findBySelect($select)) {
            return false;
        }

        $afvikling->start     = $start;
        $afvikling->slut      = $end;
        $afvikling->lokale_id = intval($post->lokale_id);
        $afvikling->note      = $post->note;

        if ($afvikling->update()) {
            $activity          = $this->createEntity('Aktiviteter')->findById($afvikling->aktivitet_id);
            $activity->updated = date('Y-m-d H:i:s');
            $activity->update();

            return true;
        }

        return false;
    }

    /**
     * deletes an activity
     *
     * @param object $aktivitet - Aktiviteter entity
     * @access public
     * @return bool
     */
    public function deleteActivity($aktivitet)
    {
        if (!is_object($aktivitet) || !$aktivitet->isLoaded())
        {
            return false;
        }
        return $aktivitet->delete();
    }

    /**
     * deletes an scheduling
     *
     * @param object $afvikling - Afviklinger entity
     * @access public
     * @return bool
     */
    public function deleteAfvikling($afvikling)
    {
        if (!is_object($afvikling) || !$afvikling->isLoaded())
        {
            return false;
        }
        return $afvikling->delete();
    }

    /**
     * wrapper for call to Afviklinger->getAllDates()
     *
     * @access public
     * @return array
     */
    public function getAllDates()
    {
        return $this->createEntity('Afviklinger')->getAllDates();
    }

    /**
     * returns all created rooms
     *
     * @access public
     * @return array
     */
    public function getAllRooms()
    {
        return (($result = $this->createEntity('Lokaler')->findAll()) ? $result : array());
    }

    /**
     * returns array with activities scheduled for the given dates
     *
     * @param array $dates - the dates to get scheduled activities for
     * @access public
     * @return array
     */
    public function getActivityForDates($dates)
    {
        if (!is_array($dates))
        {
            return array();
        }
        $result = array();
        foreach ($dates as $date)
        {
            $afviklinger = (($afv = $this->createEntity('Afviklinger')->getAfviklingerForDate($date)) ? $afv : array());
            if ($afviklinger)
            {
                foreach ($afviklinger as $afv)
                {
                    $result[$date][$afv->getAktivitet()->id][] = $afv;
                }
            }
        }
        return $result;
    }

    /**
     * returns the maximum hours of play time at the convention
     *
     * @access public
     * @return int - time in hours
     */
    public function getTotalPlaytime()
    {
        return $this->createEntity('Afviklinger')->getMaxPlaytime() / 3600;
    }

    /**
     * fetches details for activities starting at $time
     *
     * @param string $time
     *
     * @access public
     * @return array
     */
    public function getGameStartDetails($time)
    {
        if (false == strtotime($time)) {
            return array();
        }

        $af = $this->createEntity('Afviklinger');
        $select = $af->getSelect();
        $select->setWhere('start', '=', date('Y-m-d H:i:s', strtotime($time)));
        $afviklinger = $af->findBySelectMany($select);
        $result = array();

        foreach ($afviklinger as $afvikling) {
            $activity = $afvikling->getAktivitet();

            if (!in_array($activity->type, array('rolle', 'live', 'braet'))) {
                continue;
            }

            $result[] = array('run' => $afvikling, 'activity' => $activity);
        }

        usort($result, function ($a, $b) {
            return strcmp($a['activity']->navn, $b['activity']->navn);
        });

        return $result;
    }

    /**
     * returns the next available game start
     *
     * @access public
     * @return string
     */
    public function getNextGamestart()
    {
        $result = $this->db->query("SELECT `start` FROM afviklinger, aktiviteter WHERE `start` > NOW() AND aktiviteter.type IN ('rolle', 'live', 'braet') AND aktiviteter.id = afviklinger.aktivitet_id ORDER BY `start` ASC LIMIT 1");

        if ($result) {
            return $result[0]['start'];
        }

        return false;
    }

    /**
     * returns all available game starts
     *
     * @access public
     * @return array
     */
    public function getAllGamestarts()
    {
        $result = $this->db->query("SELECT distinct(`start`) AS start FROM afviklinger, aktiviteter WHERE aktiviteter.type IN ('rolle', 'live', 'braet') AND aktiviteter.id = afviklinger.aktivitet_id  ORDER BY `start` ASC");
        $return = array();

        if ($result) {
            foreach ($result as $r) {
                $return[] = $r['start'];
            }

        }

        return $return;
    }

    /**
     * checks if the logged in user is allowed to
     * run the requested gamestart
     *
     * @param string $time Time to check against
     *
     * @access public
     * @return bool
     */
    public function canRunGameStart($time)
    {
        return $this->inGameStartPeriod($time) && $this->getLoggedInUser()->hasRole('Infonaut');
    }

    /**
     * checks if the given time matches an active
     * game start period
     *
     * @param string $time Time to check against
     *
     * @access public
     * @return bool
     */
    public function inGameStartPeriod($time)
    {
        if (!is_string($time)) {
            return false;
        }

        $timestamp = strtotime($time);
        return true;

        return $timestamp >= (time() - 1800) && $timestamp <= (time() + 1800);
    }

    /**
     * returns a gamestart for the period
     * - empty if none has been created already
     *
     * @param string $time Time to get gamestart for
     *
     * @access public
     * @return GameStart
     */
    public function getGameStartByTime($time)
    {
        $gamestart = $this->createEntity('Gamestart');

        if ($existing = $gamestart->findByDatetime($time)) {
            return $existing;
        }

        $gamestart->datetime = $time;
        $gamestart->user_id  = $this->getLoggedInUser()->id;

        return $gamestart;
    }

    /**
     * returns a Gamestart by id if possible
     *
     * @param int $id Id of Gamestart to fetch
     *
     * @access public
     * @return Gamestart|null
     */
    public function getGameStart($id)
    {
        return $this->createEntity('Gamestart')->findById($id);
    }

    /**
     * fetch information about a gamestart
     *
     * @param DBObject $gamestart Gamestart to get information for
     *
     * @access public
     * @return array
     */
    public function getGameStartInformation(DBObject $gamestart, $last_update = null, $schedule_id = null)
    {
        $timestamp = 1;
        $output    = array();

        if ($last_update && (is_int($last_update) || ctype_digit($last_update))) {
            $timestamp = intval($last_update);
        }

        foreach ($gamestart->getGamestartSchedules() as $gamestart_schedule) {
            if (strtotime($gamestart_schedule->updated) < $timestamp) {
                continue;
            }

            if ($schedule_id && $schedule_id != $gamestart_schedule->schedule_id) {
                continue;
            }

            $minion = $this->createEntity('User')->findById($gamestart_schedule->user_id);

            $groups = $gamestart_schedule->getSchedule()->getAssignedByType('spiller');
            $assigned = array_sum(array_map(function($x) {return count($x);}, $groups));

            $output[$gamestart_schedule->schedule_id] = array(
                'status'            => $gamestart_schedule->status,
                'gamers_lacking'    => $assigned - $gamestart_schedule->gamers_present,
                'gms_present'       => $gamestart_schedule->getGMsPresent(),
                'reserves_offered'  => $gamestart_schedule->reserves_offered - $gamestart_schedule->reserves_accepted,
                'reserves_accepted' => $gamestart_schedule->reserves_accepted,
                'minion'            => array(
                    'id'   => $minion->id,
                    'user' => $minion->user,
                ),
            );
        }
        return $output;
    }

    /**
     * checks if the gamestart is owned by the current user
     *
     * @param Gamestart $gamestart Gamestart period to check for ownership
     *
     * @access public
     * @return bool
     */
    public function ownsGameStart(DBObject $gamestart)
    {
        return $this->getLoggedInUser()->id == $gamestart->user_id;
    }

    /**
     * tries to find and return a schedule
     *
     * @param int $id Id of schedule to return
     *
     * @access public
     * @return null|Afviklinger
     */
    public function getSchedule($id)
    {
        return $this->createEntity('Afviklinger')->findById($id);
    }

    /**
     * returns a gamestart for the period
     * - empty if none has been created already
     *
     * @param DBObject $schedule Schedule to get object for
     *
     * @throws FrameworkException
     * @access public
     * @return GameStartSchedule
     */
    public function getGameStartSchedule(DBObject $schedule)
    {
        if (!($gamestart = $this->createEntity('Gamestart')->findByDatetime($schedule->start))) {
            throw new FrameworkException('No gamestart exists for the schedule period: ' . $schedule->start);
        }

        $gamestart_schedule = $this->createEntity('GamestartSchedule');

        if ($existing = $gamestart_schedule->findByScheduleGamestart($schedule, $gamestart)) {
            return $existing;
        }

        $gamestart_schedule->gamestart_id      = $gamestart->id;
        $gamestart_schedule->schedule_id       = $schedule->id;
        $gamestart_schedule->user_id           = $this->getLoggedInUser()->id;
        $gamestart_schedule->gamers_present    = 0;
        $gamestart_schedule->gm_status         = '';
        $gamestart_schedule->status            = 0;
        $gamestart_schedule->reserves_offered  = 0;
        $gamestart_schedule->reserves_accepted = 0;
        $gamestart_schedule->updated           = date('Y-m-d H:i:s');

        return $gamestart_schedule;
    }

    /**
     * checks ownership of a gamestart schedule
     *
     * @param DBObject $gamestart_schedule Gamestart schedule to check ownership of
     *
     * @access public
     * @return bool
     */
    public function ownsGameStartSchedule(DBObject $gamestart_schedule)
    {
        return $this->getLoggedInUser()->id == $gamestart_schedule->user_id;
    }

    /**
     * updates gamestart schedule based on gamer changes
     * from a minion
     *
     * @param DBObject    $gamestart_schedule Schedule to update
     * @param RequestVars $post               Variables to use in update
     *
     * @throws FrameworkException
     * @access public
     * @return void
     */
    public function handleMinionGamerChange(DBObject $gamestart_schedule, RequestVars $post)
    {
        if (!$gamestart_schedule->isLoaded()) {
            throw new FrameworkException('Schedule is not loaded');
        }

        if (!isset($post->gamer_count)) {
            throw new FrameworkException('Lacking info in request: gamer_count');
        }

        $gamestart_schedule->gamers_present = intval($post->gamer_count);
        $gamestart_schedule->updated        = date('Y-m-d H:i:s');
        $gamestart_schedule->update();

        $this->log("Minion update gamestart detail: gamer count. For " . $gamestart_schedule->getActivity()->navn . ' at ' . $gamestart_schedule->getSchedule()->start, 'Spilstart', $this->getLoggedInUser());
    }

    /**
     * updates gamestart schedule based on gamer changes
     * from a minion - reserve specific update
     *
     * @param DBObject    $gamestart_schedule Schedule to update
     * @param RequestVars $post               Variables to use in update
     *
     * @throws FrameworkException
     * @access public
     * @return void
     */
    public function handleMinionReserveChange(DBObject $gamestart_schedule, RequestVars $post)
    {
        if (!$gamestart_schedule->isLoaded()) {
            throw new FrameworkException('Schedule is not loaded');
        }

        if (!isset($post->count)) {
            throw new FrameworkException('Lacking info in request: count');
        }

        if ($gamestart_schedule->reserves_accepted + $post->count > $gamestart_schedule->reserves_offered) {
            throw new FrameworkException('Not enough reserves available for request');
        }

        $gamestart_schedule->reserves_accepted += $post->count;
        $gamestart_schedule->updated        = date('Y-m-d H:i:s');
        $gamestart_schedule->update();

        $this->log("Minion update gamestart detail: accepted reserves. For " . $gamestart_schedule->getActivity()->navn . ' at ' . $gamestart_schedule->getSchedule()->start, 'Spilstart', $this->getLoggedInUser());
    }

    /**
     * updates gamestart schedule based on gamemaster changes
     * from a minion
     *
     * @param DBObject    $gamestart_schedule Schedule to update
     * @param RequestVars $post               Variables to use in update
     *
     * @throws FrameworkException
     * @access public
     * @return void
     */
    public function handleMinionGamemasterChange(DBObject $gamestart_schedule, RequestVars $post)
    {
        if (!$gamestart_schedule->isLoaded()) {
            throw new FrameworkException('Schedule is not loaded');
        }

        if (!isset($post->gamemaster_id) || !isset($post->state)) {
            throw new FrameworkException('Lacking info in request: gamemaster_id or state');
        }

        $original_status = $gamestart_schedule->getGMStatus();
        $update          = false;
        foreach ($original_status as $index => $gamemaster) {
            if ($gamemaster['gamemaster_id'] == $post->gamemaster_id) {
                $original_status[$index]['state'] = intval(!!$post->state);

                $update = true;
                break;
            }
        }

        if ($update) {
            $gamestart_schedule->updated = date('Y-m-d H:i:s');
            $gamestart_schedule->updateGMStatus($original_status);
        }

        $this->log("Minion update gamestart detail: gamemaster status. For " . $gamestart_schedule->getActivity()->navn . ' at ' . $gamestart_schedule->getSchedule()->start, 'Spilstart', $this->getLoggedInUser());
    }

    /**
     * handles changes from a gamestart, from the master
     *
     * @param int         $id   Id of gamestart to change details for
     * @param RequestVars $post Post vars
     *
     * @throws FrameworkException
     * @access public
     * @return void
     */
    public function handleMasterChange($id, RequestVars $post)
    {

        if (empty($post->type) || !in_array($post->type, array('add-reservist', 'remove-reservist', 'change-schedule-status'))) {
            throw new FrameworkException('Lacking type or wrong type of change for gamestart master changes');
        }

        if (!($gamestart = $this->getGameStart($id))) {
            throw new FrameworkException('No gamestart with that ID');
        }

        if (!($schedule = $this->getSchedule($post->schedule_id))) {
            throw new FrameworkException('No schedule with that ID');
        }

        if (!($gamestart_schedule = $this->getGameStartSchedule($schedule)) || !$gamestart_schedule->status) {
            throw new FrameworkException('Gamestart schedule is not active');
        }

        if ($post->type == 'add-reservist' || $post->type == 'remove-reservist') {
            $this->handleMasterReservistChange($gamestart_schedule, $post);

        } else {
            $this->handleMasterScheduleStatusChange($gamestart_schedule, $post);
        }
    }

    /**
     * handles changes from a master for schedule
     * status changes
     *
     * @param DBObject    $gamestart_schedule Schedule to change
     * @param RequestVars $post               Post data
     *
     * @throws FrameworkException
     * @access protected
     * @return void
     */
    protected function handleMasterScheduleStatusChange(DBObject $gamestart_schedule, RequestVars $post)
    {
        if (empty($post->status)) {
            throw new FrameworkException('Lacking status field in post data');
        }

        $gamestart_schedule->status  = intval($post->status);
        $gamestart_schedule->updated = date('Y-m-d H:i:s');
        $gamestart_schedule->update();
    }

    /**
     * handles changes from a master for reserves
     *
     * @param DBObject    $gamestart_schedule
     * @param RequestVars $post
     *
     * @throws FrameworkException
     * @access protected
     * @return void
     */
    protected function handleMasterReservistChange(DBObject $gamestart_schedule, RequestVars $post)
    {
        $amount = $post->type == 'remove-reservist' ? -1 : 1;

        $gamestart_schedule->reserves_offered += $amount;
        $gamestart_schedule->updated           = date('Y-m-d H:i:s');

        if ($gamestart_schedule->reserves_offered < 0) {
            $gamestart_schedule->reserves_offered = 0;
        }

        $gamestart_schedule->update();

        $this->log("Master update gamestart detail: reserves offered. For " . $gamestart_schedule->getActivity()->navn . ' at ' . $gamestart_schedule->getSchedule()->start, 'Spilstart', $this->getLoggedInUser());
    }

    public function fetchGameStartQueueData()
    {
        $query = '
SELECT
    id
FROM
    gamestarts
WHERE
    datetime BETWEEN NOW() - interval 30 MINUTE AND NOW() + interval 30 MINUTE
ORDER BY id DESC
LIMIT 1
';

        $result = $this->db->query($query);

        if (empty($result)) {
            return array();
        }

        $gamestart = $this->getGameStart($result[0]['id']);

        if ($gamestart->status != gamestart::OPEN) {
            return array();
        }

        $output = array();

        foreach ($gamestart->getGamestartSchedules() as $schedule) {
            $missing = $schedule->getMissingPlayers();

            if ($schedule->status != GameStartSchedule::OPEN || $missing === 0) {
                continue;
            }

            $activity = $schedule->getActivity();

            if ($missing == $schedule->getAssignedPlayers()) {
                $needed = '?';
            } else {
                $needed = $missing;
            }

            switch ($activity->type) {
            case 'rolle':
                $type = 'Scenarie / Scenario';
                break;

            case 'braet':
                $type = 'Brætspil / Boardgamer';
                break;

            case 'live':
                $type = 'Live / Larp';
                break;

            default:
                $type = '';
            }

            $output[] = array(
                'id' => $schedule->id,
                'titles' => array(
                    'da' => $activity->navn,
                    'en' => $activity->title_en,
                ),
                'author' => $activity->author,
                'type'   => $type,
                'gamers_needed' => $needed,
            );
        }

        return $output;
    }

    /**
     * returns stats on participants karma
     *
     * @access public
     * @return array
     */
    public function getKarmaStats()
    {
        $karma = $this->buildKarma();

        $stats = $karma->calculate($this->createEntity('Deltagere')->findAll());

        return $stats;
    }

    /**
     * returns double booked GMs for the activitys schedules
     *
     * @param \Aktiviteter $activity Activity to get double bookings for
     *
     * @access public
     * @return array
     */
    public function getDoubleBookings(\Aktiviteter $activity)
    {
		$query = '
SELECT
    p.deltager_id,
    p.afvikling_id
FROM
    deltagere_tilmeldinger AS p
    JOIN afviklinger AS pa ON pa.id = p.afvikling_id
    JOIN deltagere_tilmeldinger AS s ON s.deltager_id = p.deltager_id AND s.afvikling_id != p.afvikling_id
    JOIN afviklinger AS sa ON sa.id = s.afvikling_id
WHERE
    p.tilmeldingstype = "spilleder"
    AND pa.aktivitet_id = ?
    AND (
        (pa.start > sa.start AND pa.start < sa.slut)
        OR (pa.start <= sa.start AND pa.slut >= sa.slut)
        OR (pa.slut > sa.start AND pa.slut < sa.slut)
    )
';

        $result = [];

        foreach ($this->db->query($query, [$activity->id]) as $row) {
            $result[$row['deltager_id']][$row['afvikling_id']] = true;
        }

        return $result;
    }

    /**
     * returns true if votes were cast for any
     * of the schedules given
     *
     * @param array $gamestartdetails Schedules to check for votes cast
     *
     * @access public
     * @return bool
     */
    public function votesCast(array $gamestartdetails)
    {
        $mapper = function ($x) {
            return intval($x['run']->id);
        };

        $schedule_ids = array_map($mapper, $gamestartdetails);

        $query = '
SELECT
    COUNT(*) AS count
FROM
    schedules_votes
WHERE
    schedule_id IN (' . implode(', ', $schedule_ids) . ')
    AND cast_at > "0000-00-00 00:00:00"
';

        $result = $this->db->query($query);
        $row    = reset($result);

        return !!$row['count'];
    }

    /**
     * attempts to fetch a vote given a code
     *
     * @param string $code Code to search by
     *
     * @access public
     * @return string|false
     */
    public function fetchVoteCode($code)
    {
        $query = '
SELECT
    code
FROM
    schedules_votes
WHERE
    code = ?
';

        $result = $this->db->query($query, [mb_strtolower($code)]);

        if (empty($result)) {
            return false;
        }

        return $result[0]['code'];
    }

    /**
     * attempts to fetch a vote given a code
     *
     * @param string $code Code to search by
     *
     * @access public
     * @return string|false
     */
    public function fetchVote($code)
    {
        $query = '
SELECT
    id,
    schedule_id,
    cast_at
FROM
    schedules_votes
WHERE
    code = ?
';

        $result = $this->db->query($query, [$code]);

        if (empty($result)) {
            return false;
        }

        return $result[0];
    }

    /**
     * returns activity given schedule id
     *
     * @param int $schedule_id ID of schedule to load activity for
     *
     * @access public
     * @return DbObject
     */
    public function fetchVoteActivity($schedule_id)
    {
        $schedule = $this->createEntity('Afviklinger')->findById($schedule_id);

        if (!$schedule) {
            return false;
        }

        return $schedule->getActivity();
    }

    /**
     * marks a vote as cast
     *
     * @param  $vote_id ID of vote to mark cast
     *
     * @access public
     * @return bool
     */
    public function markVoteCast($vote_id)
    {
        $query = '
SELECT
    id
FROM
    schedules_votes
WHERE
    id = ?
';

        $result = $this->db->query($query, [$vote_id]);

        if (!$result) {
            return false;
        }

        $query = '
UPDATE schedules_votes SET cast_at = NOW() WHERE id = ?
';

        return $this->db->exec($query, [$vote_id]);
    }

    /**
     * returns voting stats
     *
     * @access public
     * @return array
     */
    public function collectVotingStats()
    {
        $query = '
SELECT
    ak.navn,
    sv1.votes_cast,
    sv2.votes_potential
FROM
    aktiviteter AS ak
    JOIN (
        SELECT
            af1.aktivitet_id,
            COUNT(*) AS votes_cast
        FROM
            afviklinger AS af1
            JOIN schedules_votes AS sv1 ON sv1.schedule_id = af1.id
        WHERE
            sv1.cast_at > "0000-00-00 00:00:00"
        GROUP BY
            af1.aktivitet_id
    ) AS sv1 ON sv1.aktivitet_id = ak.id
    JOIN (
        SELECT
            af1.aktivitet_id,
            COUNT(*) AS votes_potential
        FROM
            afviklinger AS af1
            JOIN schedules_votes AS sv1 ON sv1.schedule_id = af1.id
        GROUP BY
            af1.aktivitet_id
    ) AS sv2 ON sv2.aktivitet_id = ak.id
GROUP BY
    ak.navn
';

        $stats = [];

        foreach ($this->db->query($query) as $row) {
            $stats[$row['navn']] = [
                                    'cast'      => $row['votes_cast'],
                                    'potential' => $row['votes_potential'],
                                   ];

        }

        return $stats;
    }

    /**
     * creates an excel spreadsheet with gm data
     *
     * @access public
     * @return PHPExcel
     */
    public function getGmSpreadsheet()
    {
        $query = '
SELECT
    ak.navn AS titel,
    af.start,
    CONCAT(d.fornavn, " ", d.efternavn) AS navn,
    d.email,
    d.mobiltlf
FROM
    aktiviteter AS ak
    JOIN afviklinger AS af ON af.aktivitet_id = ak.id
    JOIN hold AS h on h.afvikling_id = af.id
    JOIN pladser AS p ON p.hold_id = h.id
    JOIN deltagere AS d ON d.id = p.deltager_id
WHERE
    p.type = "spilleder"
    AND ak.type NOT IN ("system", "workshop", "ottoviteter")
ORDER BY
    titel,
    start,
    navn
';

        $output = [
                   [
                    'Titel',
                    'Start',
                    'Navn',
                    'email',
                    'mobiltlf',
                   ],
                  ];

        foreach ($this->db->query($query) as $row) {
            $output[] = [
                         $row['titel'],
                         $row['start'],
                         $row['navn'],
                         $row['email'],
                         $row['mobiltlf'],
                        ];
        }

        include_once LIB_FOLDER . 'PHPExcel/Classes/PHPExcel.php';

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        foreach ($output as $row_index => $row) {
            foreach ($row as $column_index => $column) {
                $sheet->getCellByColumnAndRow($column_index, $row_index + 1)->setValue($column);
            }

        }

        foreach (range(0, 4) as $index) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }

        return new PHPExcel_Writer_Excel2007($excel);
    }

    /**
     * creates/refreshes Gm briefings
     *
     * @access public
     * @return ParticipantModel
     */
    public function createGmBriefings()
    {
        // 1. get all roleplay activities
        // 2. create or find related briefing activities
        $activity_data = $this->getBriefingActivities($this->getActivitiesByType('rolle'));

        // 3. create or find related schedules, 30 minutes prior
        $briefing_schedules = $this->getBriefingSchedules($activity_data);

        //4. create a group for each schedule
        $groups = $this->getBriefingGroups($briefing_schedules);

        //5. add all gamemasters from original schedules, to newly created group
        $this->addGmsToBriefingGroups($briefing_schedules, $groups);
    }

    /**
     * creates/refreshes Gm briefings
     *
     * @access public
     * @return ParticipantModel
     */
    public function createRuleBriefings()
    {
        // 1. get all boardgame activities
        // 2. create or find related briefing activities
        $activity_data = $this->getBriefingActivities($this->getActivitiesByType('braet'), 'braet');

        // 3. create or find related schedules, 15 minutes prior
        $briefing_schedules = $this->getBriefingSchedules($activity_data, 'braet');

        //4. create a group for each schedule
        $groups = $this->getBriefingGroups($briefing_schedules);

        //5. add all gamemasters from original schedules, to newly created group
        $this->addGmsToBriefingGroups($briefing_schedules, $groups);
    }

    /**
     * returns activities fetched by type
     *
     * @param string $type Type to search by
     *
     * @access public
     * @return array
     */
    public function getActivitiesByType($type, bool $with_master = true)
    {
        $select = $this->createEntity('Aktiviteter')->getSelect();

        $select->setWhere('type', '=', $type);
        if ($with_master) $select->setWhere('spilledere_per_hold', '>', 0);

        return $this->createEntity('Aktiviteter')->findBySelectMany($select);
    }

    /**
     * fetches or creates briefing activities
     *
     * @param array $activities Base set of activities
     *
     * @access public
     * @return array
     */
    public function getBriefingActivities(array $activities, $type = 'rolle')
    {

        $text = $type === 'braet' ? 'Rægelformidlerbriefing for ' : 'Spillederbriefing for ';
        $text_en = $type === 'braet' ? 'Rules explainer briefing for ' : 'Gamemaster briefing for ';

        $names = [];
        foreach($activities as $key => $a) {
            $names[$key] = $text.$a->navn;
        }
        // $mapper = function ($x) {
        //     return  $text.$x->navn;
        // };

        // $names = array_map($mapper, $activities);

        $originals = array_combine($names, $activities);

        $select = $this->createEntity('Aktiviteter')->getSelect();

        $select->setWhere('navn', 'IN', $names);

        $existing_briefings = $this->createEntity('Aktiviteter')->findBySelectMany($select);

        $name_mapper = function ($x) {
            return $x->navn;
        };

        $existing_names = array_unique(array_map($name_mapper, $existing_briefings));

        $existing_briefings = array_combine($existing_names, $existing_briefings);

        foreach (array_diff($names, $existing_names) as $name) {
            $activity = $this->createEntity('Aktiviteter');

            $activity->type                   = 'system';
            $activity->navn                   = $name;
            $activity->kan_tilmeldes          = 'nej';
            $activity->varighed_per_afvikling = $type === 'braet' ? 0.25 : 0.5;
            $activity->min_deltagere_per_hold = 0;
            $activity->max_deltagere_per_hold = 0;
            $activity->spilledere_per_hold    = 0;
            $activity->lokale_eksklusiv       = 'ja';
            $activity->title_en               = $text_en . $originals[$name]->title_en;
            $activity->tids_eksklusiv         = 'ja';
            $activity->sprog                  = $originals[$name]->sprog;
            $activity->hidden                 = 'nej';
            $activity->wp_link                = 0;
            $activity->pris                   = 0;
            $activity->teaser_dk              = $activity->teaser_en = $activity->description_en = '';
            $activity->updated                = date('Y-m-d H:i:s');
            $activity->insert();

            $existing_briefings[$name] = $activity;

        }

        $output = [];

        foreach ($names as $name) {
            $output[$name] = [
                              'original' => $originals[$name],
                              'briefing' => $existing_briefings[$name],
                             ];
        }

        return $output;
    }

    /**
     * fetches/creates schedules for the briefing activities
     *
     * @param array $activity_data Original and briefing activities
     *
     * @access public
     * @return array
     */
    public function getBriefingSchedules(array $activity_data, $type = 'rolle')
    {
        $original_id_mapper = function ($x) {
            return intval($x['original']->id);
        };

        $case_mapper = function ($x) {
            return 'WHEN a1.aktivitet_id = ' . intval($x['original']->id) . ' THEN ' . intval($x['briefing']->id);
        };

        $clause_mapper = function ($x) {
            return '(a1.aktivitet_id = ' . intval($x['original']->id) . ' AND a2.aktivitet_id = ' . intval($x['briefing']->id) . ')';
        };

        $ids = array_map($original_id_mapper, $activity_data);

        $time = $type === 'braet' ? 15 : 30;
        $query = '
SELECT
    a1.id AS original_id,
    a1.aktivitet_id,
    CASE ' . implode(' ', array_map($case_mapper, $activity_data)) . ' END AS briefing_id,
    a1.start - INTERVAL '.$time.' MINUTE AS start,
    a1.start AS slut,
    a1.lokale_id,
    a2.id
FROM
    afviklinger AS a1
    LEFT JOIN afviklinger AS a2 ON a2.start = a1.start - INTERVAL '.$time.' MINUTE AND a2.slut = a1.start AND (' . implode(' OR ', array_map($clause_mapper, $activity_data)) . ')
WHERE
    a1.aktivitet_id IN (' . implode(', ', $ids) . ')
';

        $schedules = $original_ids = $to_load = $schedule_map = [];

        foreach ($this->db->query($query) as $row) {
            $original_ids[] = $row['original_id'];

            if (!empty($row['id'])) {
                $schedule_map[$row['original_id']] = $row['id'];
                $to_load[] = $row['id'];
                continue;

            }

            $schedule               = $this->createEntity('Afviklinger');
            $schedule->aktivitet_id = $row['briefing_id'];
            $schedule->start        = $row['start'];
            $schedule->slut         = $row['slut'];
            $schedule->lokale_id    = $row['lokale_id'];
            $schedule->note         = '';
            $schedule->insert();

            $schedule_map[$row['original_id']] = $schedule->id;

            $schedules[] = $schedule;

        }

        if ($to_load) {
            $select = $this->createEntity('Afviklinger')->getSelect();

            $select->setWhere('id', 'IN', $to_load);

            $schedules = array_merge($schedules, $this->createEntity('Afviklinger')->findBySelectMany($select));

        }

        $select = $this->createEntity('Afviklinger')->getSelect();

        $select->setWhere('id', 'IN', $original_ids);

        $originals = $this->createEntity('Afviklinger')->findBySelectMany($select);

        return [
                'original_schedules' => $originals,
                'briefing_schedules' => $schedules,
                'schedule_id_map'    => $schedule_map,
               ];
    }

    /**
     * fetches/creates all briefing groups
     *
     * @param array $schedule_data Data on original and briefing schedules
     *
     * @access public
     * @return array
     */
    public function getBriefingGroups(array $schedule_data)
    {
        $groups = [];

        foreach ($schedule_data['briefing_schedules'] as $schedule) {
            if ($schedule_groups = $schedule->getHold()) {
                $groups[$schedule->id] = reset($schedule_groups);
                continue;
            }

            if (!$schedule->lokale_id) {
                continue;
            }

            $group = $this->createEntity('Hold');

            $group->afvikling_id = $schedule->id;
            $group->holdnummer   = 1;
            $group->lokale_id    = $schedule->lokale_id;
            $group->insert();

            $groups[$schedule->id] = $group;

        }

        return $groups;
    }

    /**
     * adds gms to briefing groups
     *
     * @param array $schedule_data Data about original and briefing schedules
     * @param array $group_data    Schedule indexed groups
     *
     * @access public
     * @return ActivityModel
     */
    public function addGmsToBriefingGroups(array $schedule_data, array $group_data)
    {
        $query = '
SELECT
    h.afvikling_id,
    p.deltager_id
FROM
    hold AS h
    JOIN pladser AS p ON p.hold_id = h.id
WHERE
    h.afvikling_id IN (' . implode(', ', array_keys($schedule_data['schedule_id_map'])) . ')
    AND p.type = "spilleder"
';

        foreach ($this->db->query($query) as $row) {
            $gms[$row['afvikling_id']][] = $row['deltager_id'];
        }

        $cases = [];

        foreach ($schedule_data['schedule_id_map'] as $original_id => $briefing_id) {
            $cases[] = 'WHEN h.afvikling_id = ' . intval($briefing_id) . ' THEN ' . intval($original_id);
        }

        $query = '
SELECT
    CASE ' . implode(' ', $cases) . ' END AS afvikling_id,
    p.deltager_id
FROM
    hold AS h
    JOIN pladser AS p ON p.hold_id = h.id
WHERE
    h.afvikling_id IN (' . implode(', ', $schedule_data['schedule_id_map']) . ')
';

        $assigned = [];

        foreach ($this->db->query($query) as $row) {
            $assigned[$row['afvikling_id']][] = $row['deltager_id'];
        }

        foreach ($gms as $schedule_id => $group) {
            if (!empty($assigned[$schedule_id])) {
                $gms[$schedule_id] = array_diff($group, $assigned[$schedule_id]);
            }

        }

        $participants = [];

        $inserts = [];

        foreach ($gms as $schedule_id => $group) {
            foreach ($group as $participant_id) {
                $group = $group_data[$schedule_data['schedule_id_map'][$schedule_id]];

                if (empty($place_numbers[$group->id])) {
                    $number = $place_numbers[$group->id] = $this->createEntity('Pladser')->getNextPladsnummer($group);

                } else {
                    $number = ++$place_numbers[$group->id];
                }

                $inserts[] = '(' . intval($group->id) . ', ' . intval($number) . ', "spiller", ' . intval($participant_id) . ')';

            }

        }

        if ($inserts) {
            $query = 'INSERT INTO pladser (hold_id, pladsnummer, type, deltager_id) VALUES ' . implode(', ', $inserts) . ' ON DUPLICATE KEY UPDATE deltager_id = VALUES(deltager_id)';

            $this->db->exec($query);

        }

        return $this;
    }

    /**
     * returns true if votes exist for this gamestart
     *
     * @param array $gamestart Array of gamestart details to check
     *
     * @access public
     * @return bool
     */
    public function haveVotesBeenPrinted(GameStart $gamestart)
    {
        $id_mapper = function ($x) {
            return intval($x->id);
        };

        $ids = array_map($id_mapper, $gamestart->getGamestartSchedules());

        if (!$ids) {
            return false;
        }

        $query = '
SELECT
    COUNT(*) AS count
FROM
    schedules_votes
WHERE
    schedule_id IN (' . implode(', ', $ids) . ')
';

        $result = $this->db->query($query);

        $row = reset($result);

        return !empty($row['count']);
    }

    /**
     * calculates priority statistics
     *
     * @access public
     * @return array
     */
    public function calculateSignupStatistics()
    {
        $query = '
            SELECT
                ak.id AS activity_id,
                ak.navn,
                ak.type,
                af.id AS schedule_id,
                COUNT(*) AS signups
            FROM
                aktiviteter AS ak
                JOIN afviklinger AS af ON af.aktivitet_id = ak.id
                JOIN deltagere_tilmeldinger AS dt ON dt.afvikling_id = af.id
            WHERE
                dt.prioritet = 1
                AND dt.tilmeldingstype = "spiller"
            GROUP BY
                ak.id,
                ak.navn,
                ak.type,
                af.id
            ORDER BY
                ak.type,
                ak.navn,
                af.start
            ';

        $data = [];
        foreach ($this->db->query($query) as $row) {
            if (!isset($data[$row['type']])) {
                $data[$row['type']] = [];
            }

            if (!isset($data[$row['type']][$row['activity_id']])) {
                $data[$row['type']][$row['activity_id']] = [
                    'name'     => $row['navn'],
                    'data'     => [],
                    'distinct' => 0,
                ];
            }

            $data[$row['type']][$row['activity_id']]['data'][$row['schedule_id']] = [
                'signed_up' => $row['signups'],
                'distinct'  => 0,
            ];
        }

        $query_data = [];
        foreach ($data as $type => $activities) {
            foreach ($activities as $id => $activity) {
                if (count($activity['data']) === 1) {
                    $key = key($activity['data']);
                    $data[$type][$id]['data'][$key]['distinct'] = $data[$type][$id]['distinct'] = $activity['data'][$key]['signed_up'];

                } else {
                    $ids = array_keys($activity['data']);

                    $query_data = array_merge($query_data, array_map(function ($item) use ($ids, $type, $id) {
                        return [
                                'type' => $type,
                                'activity_id' => $id,
                                'schedule_id' => $item,
                                'mask_ids'    => array_diff($ids, [$item]),
                               ];
                    }, $ids));
                }
            }
        }

        $clauses = $arguments = [];
        foreach ($query_data as $index => $row) {
            $clauses[] = str_replace('MASK', implode(', ', array_fill(0, count($row['mask_ids']), '?')), '
                SELECT
                    ? AS type,
                    ? AS activity_id,
                    dt.afvikling_id,
                    COUNT(*) AS signups
                FROM
                    deltagere_tilmeldinger AS dt
                WHERE
                    afvikling_id = ?
                    AND dt.prioritet = 1
                    AND dt.tilmeldingstype = "spiller"
                    AND dt.deltager_id NOT IN (
                        SELECT
                            dt.deltager_id
                        FROM
                            deltagere_tilmeldinger AS dt
                        WHERE
                            dt.afvikling_id IN (MASK)
                            AND dt.prioritet = 1
                            AND dt.tilmeldingstype = "spiller"
                    )
                ');
            $arguments = array_merge($arguments, [$row['type'], $row['activity_id'], $row['schedule_id']], $row['mask_ids']);
        }

        $query = implode("\nUNION\n", $clauses);
        foreach ($this->db->query($query, $arguments) as $row) {
            $data[$row['type']][$row['activity_id']]['data'][$row['afvikling_id']]['distinct'] = $row['signups'];
        }

        $query = '
            SELECT
                t.activity_id,
                t.type,
                COUNT(*) AS count
            FROM (
                SELECT
                    DISTINCT
                    ak.id AS activity_id,
                    ak.type,
                    dt.deltager_id
                FROM
                    aktiviteter AS ak
                    JOIN afviklinger AS af ON af.aktivitet_id = ak.id
                    JOIN deltagere_tilmeldinger AS dt ON dt.afvikling_id = af.id
                WHERE
                    dt.prioritet = 1
                    AND dt.tilmeldingstype = "spiller"
            ) AS t
            GROUP BY
                t.activity_id,
                t.type
            ';

        foreach ($this->db->query($query) as $row) {
            $data[$row['type']][$row['activity_id']]['distinct'] = $row['count'];
        }

        return $data;
    }

    /**
     * calculates desired activity stats
     *
     * @access public
     * @return array
     */
    public function getDesiredActivityStats(Aktiviteter $activity)
    {
        $query = '
SELECT
    d.id,
    CAST(d.desired_activities AS signed) - COUNT(p.deltager_id) AS activities
FROM
    deltagere AS d
    JOIN deltagere_tilmeldinger AS dt ON dt.deltager_id = d.id
    JOIN afviklinger AS dt_a ON dt_a.id = dt.afvikling_id
    LEFT JOIN pladser AS p ON p.deltager_id = d.id AND p.type = "spiller"
    LEFT JOIN hold as h ON h.id = p.hold_id
    LEFT JOIN afviklinger AS a ON a.id = h.afvikling_id
WHERE
    dt_a.aktivitet_id = ?
    AND d.desired_activities > 0
GROUP BY
    d.id
';

        $reducer = function ($agg, $next) {
            $agg[$next['id']] = $next['activities'];

            return $agg;
        };

        return array_reduce($this->db->query($query, [$activity->id]), $reducer, []);
    }

    public function getAutoRegisterCategories() {
        return $this->createEntity('Aktiviteter')->getValidColumnValues('auto_signup_category')['values'];
    }

    public function setupVotes(array $categories, Page $page) {
        $return_list = [];
        foreach($categories as $category) {
            $activities = $this->getActivitiesByType($category, false);
            foreach($activities as $activity) {
                $schedules = $activity->getafviklinger();
                $return_list[$category][$activity->id] = [
                    'name_da' => $activity->navn,
                    'name_en' => $activity->title_en,
                    'schedules' => [],
                ];
    
                foreach($schedules as $schedule) {
                    $votes = $schedule->createVotes($page);
                    $return_list[$category][$activity->id]['schedules'][$schedule->start] = $votes;
                }
            }
        }

        return $return_list;
    }

    public function calcBoardRanking() {
        //---------------------------------------
        // Raw voting data
        //---------------------------------------
        $query = 
        "SELECT ak.navn, ak.id, bg.ranking, COUNT(*) as 'count'
            FROM aktiviteter ak
            LEFT JOIN boardgame_rankings bg ON bg.boardgame_id = ak.id
        WHERE ak.type = 'braet'
        GROUP BY ak.id, bg.ranking";

        // Order rankings per game
        $games = [];
        $result = $this->db->query($query);
        foreach($result as $rank_count) {
            if ($rank_count['ranking'] == null) {
                $games[$rank_count['id']] = [
                    'name' => $rank_count['navn'],
                    'rankings' => [],
                ];                
            }
            if (!isset($games[$rank_count['id']])) {
                $games[$rank_count['id']] = [
                    'name' => $rank_count['navn'],
                    'rankings' => [
                        $rank_count['ranking'] => $rank_count['count'],
                    ],
                ];
            } else {
                $games[$rank_count['id']]['rankings'][$rank_count['ranking']] = $rank_count['count'];
            }
        }

        //---------------------------------------
        // Rank choice elimination
        //---------------------------------------
        $query = "SELECT * FROM boardgame_rankings ORDER BY participant_id, ranking";
        $result = $this->db->query($query);

        // Collect voters
        $voters = [];
        foreach ($result as $row) {
            $rank = intval($row['ranking']) - 1;
            if (!isset($voters[$row['participant_id']])) {
                $voters[$row['participant_id']] = [
                    $rank => $row['boardgame_id'],
                ];
            } else {
                $voters[$row['participant_id']][$rank] = $row['boardgame_id'];
            }
        }

        // Prepare list of games
        $contestants = [];
        foreach($games as $game_id => $game) {
            $contestants[$game_id] = [];
        }

        // Assign voters to each game
        foreach ($voters as $voter_id => $voter) {
            $game_id = array_shift($voter);
            $contestants[$game_id][$voter_id] = $voter;
        }

        // Elimination start
        $max_round = count($contestants);
        $round = 1;
        $eliminated = [];
        while (count($contestants) > 0 && $round  <= $max_round) {
            // Find least votes
            $least_votes = 1000000;
            $least_games = [];
            foreach ($contestants as $game_id => $game_voters) {
                $vote_count = count($game_voters);
                if ( $vote_count < $least_votes) {
                    $least_votes = $vote_count;
                    $least_games = [];
                }
                if ($vote_count <= $least_votes) {
                    $least_games[] = $game_id;
                }
            }

            // One elimination candidate
            if (count($least_games) == 1) {
                $this->eliminateGame($least_games[0], $contestants, $eliminated, $round);
                $round++;
                continue;
            }

            // Tie breaker by most high ranked votes
            for ($rank = 1; $rank <= 5; $rank++) {
                $leaving_games = [];
                $least_rankings = 100000;

                // Find least high rank votes
                foreach ($least_games as $game_id) {
                    $rank_count = $games[$game_id]['rankings'][$rank] ?? 0;
                    if ( $rank_count < $least_rankings) {
                        $least_rankings = $rank_count;
                        $leaving_games = [];
                    }
                    if ($rank_count <= $least_rankings) {
                        $leaving_games[] = $game_id;
                    }
                }

                // Only one with lowest vote of this rank
                if (count($leaving_games) == 1) {
                    $this->eliminateGame($leaving_games[0], $contestants, $eliminated, $round);
                    $round++;
                    continue 2;
                } else {
                    // Continue to next rank with current lowest votes
                    $least_games = [];
                    foreach($leaving_games as $game_id) {
                        $least_games[] = $game_id;
                    }
                }
            }

            // Couldn't break the tie so all lowest score goes
            $round+= count($least_games) - 1;
            foreach($least_games as $game_id) {
                $this->eliminateGame($game_id, $contestants, $eliminated, $round);
            }
            $round++;
        }
        
        arsort($eliminated);
        return [
            'list' => $eliminated,
            'game_votes' => $games,
        ];
    }

    private function eliminateGame($game_id, &$contestants, &$eliminated, $round) {
        $eliminated[$game_id] = $round;
        foreach($contestants[$game_id] as $voter_id => $voter) {
            if (count($voter) == 0) continue; //Voter has no more game rankings
            do {
                $next_game = array_shift($voter);
            } while(isset($eliminated[$next_game]) && count($voter) > 0);
            if (isset($eliminated[$next_game])) continue;
            $contestants[$next_game][$voter_id] = $voter;
        }
        unset($contestants[$game_id]);
    }
}
