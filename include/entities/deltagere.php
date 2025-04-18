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
     * @subpackage Entities
     * @author     Peter Lind <peter.e.lind@gmail.com>
     * @copyright  2009 Peter Lind
     * @license    http://www.gnu.org/licenses/gpl.html GPL 3
     * @link       http://www.github.com/Fake51/Infosys
     */

    /**
     * handles the deltagere table
     *
     * @package MVC
     * @subpackage Entities
     */
class Deltagere extends DBObject implements AgeFulfilment
{

    const RICH_BASTARD_DONATION = 300;
    const SCENARIO_COMPETITION = 20;
    const DANCING_CLANS_COST = 20;

    /**
     * contains field names that make more sense
     *
     * @var array
     */
    protected $human_readable_fieldnames = array(
        'id'                            => 'Id',
        'fornavn'                       => 'Fornavn',
        'efternavn'                     => 'Efternavn',
        'alder'                         => 'Alder',
        'email'                         => 'E-mail',
//        'tlf'                           => 'Alt. telefon',
        'mobiltlf'                      => 'Mobilnr.',
        'adresse1'                      => 'Adresse',
        'postnummer'                    => 'Postnummer',
        'by'                            => 'By',
        'land'                          => 'Land',
        'medbringer_mobil'              => 'Medbringer mobil',
        'sprog'                         => 'Sprog til aktiviteter',
        'brugerkategori_id'             => 'Brugerkategori',
        'forfatter'                     => 'Forfatter/Designer',
//        'international'                 => 'International',
//        'arrangoer_naeste_aar'          => 'Arrangør igen',
        'betalt_beloeb'                 => 'Betalt beløb',
        'admin_note'                    => 'Admin note',
        'beskeder'                      => 'Besked fra FV',
        'created'                       => 'Oprettet',
//        'flere_gdsvagter'               => 'Flere GDS-vagter',
//        'supergds'                      => 'SuperGDS',
//        'rig_onkel'                     => 'Rig onkel',
        'work_area'                     => 'Arbejdsområde',
        'game_id'                       => 'Scenarie/Brætspil',
        'udeblevet'                     => 'Udeblevet',
        'sovesal'                       => 'Arrangør sovesal',
        'ungdomsskole'                  => 'Ungdomsskole/klub',
//        'hemmelig_onkel'                => 'Hemmelig onkel',
        'ready_mandag'                  => 'Mandags-hjælp - opsætning',
        'ready_tirsdag'                 => 'Tirsdags-hjælp - opsætning',
//        'may_contact'                   => 'Må kontaktes',
//        'original_price'                => 'Oprindelig pris',
        'paid_note'                     => 'Betalingsnote',
        'checkin_time'                  => 'Checkin',
        'desired_activities'            => 'Ønskede aktiviteter',
        'birthdate'                     => 'Fødselsdato',
//        'medical_note'                  => 'Helbreds note',
//        'interpreter'                   => 'Simultantolk',
//        'gcm_id'                        => 'GCM ID',
//        'skills'                        => 'Skills',
        'updated'                       => 'Opdateret',
        'signed_up'                     => 'Tilmeldt',
        'annulled'                      => 'Annulleret',
        'desired_diy_shifts'            => 'Helteopgaver, ønskede',
        'nickname'                      => 'Kaldenavn',
        'financial_struggle'            => 'Økonomisk støtte',
        'main_lang'                     => 'Foretrukket sprog',
        'country'                       => 'Land',
        'supergm'                       => 'Super Spilleder',
        'gm_max'                        => 'Max Spilleder',
        'super_rules_guide'             => 'Super Regelformidler',
        'rules_guide_max'               => 'Max Regelformidler',
        'pronoun'                       => 'Pronomen',
        'author'                        => 'Forfatter/Designer type',
        'sleeping_area'                 => 'Ønskede soveområder',
        'sober_sleeping'                => 'Ædru og stille sovesal',
    );

    static protected $special_columns = [
        'assigned_sleeping'             => 'Tildelt sovelokale(r)',
        'has_hero_signup'               => 'Heltestyrken, tilmeldt',
        'hero_task_count'               => 'Helteopgaver, tildelte',
    ];

    /**
     * Contains display names for different notes
     *
     * @var array
     */
    static protected $note_names = [
        'da' => [
            'comment' => 'Andre kommentarer',
            'gds' => 'Kommentarer til Heltestyrken',
            'junior_ward' => 'Kontakperson',
            'junior_comment' => 'Kommentar til Fastaval Junior',
            'sleeping' => 'Bemærkninger til soveplads',
        ],
        'en' => [
            'comment' => 'Other comments',
            'gds' => 'Comments regarding Hero tasks',
            'junior_ward' => 'Contact',
            'junior_comment' => 'Fastaval Junior Comment',
            'sleeping' => 'Comments about sleeping area',
        ]
    ];

    static protected $foreign_key_fields = [
        'arbejdsomraade' => [
            'key_field' => 'work_area',
            'table' => 'organizer_categories',
            'name' => 'name_da',
            'key' => 'id' ,
        ],
        'land' => [
            'key_field' => 'country',
            'table' => 'countries',
            'name' => 'name_da',
            'key' => 'code' ,
        ],
        'scenarie' => [
            'key_field' => 'game_id',
            'table' => 'aktiviteter',
            'name' => 'navn',
            'key' => 'id' ,
        ],
        'brugerkategori' => [
            'key_field' => 'brugerkategori_id',
            'table' => 'brugerkategorier',
            'name' => 'navn',
            'key' => 'id'
        ]
    ];

    static protected $pronouns = [
        'en' => [
            'he' => 'He/Him',
            'her' => 'She/Her',
            'they' => 'They/Them',
        ],
        'da' => [
            'he' => 'Han/Ham',
            'her' => 'Hun/Hende',
            'they' => 'De/Dem',
        ],
    ];

    /**
     * Name of database table
     *
     * @var string
     */
    protected $tablename = "deltagere";

    protected $calculated_age = '';

    /**
     * returns value from $this->model_vars if they're set
     * overrides the parent __get function in order to also return
     * values from child tables
     *
     * @param string $var - name of variable to set
     * @access public
     * @return mixed - the value asked for or a big fat error object
     */
    public function __get($var)
    {
        if ($var == 'alder') {
            if (!empty($this->calculated_age)) {
                return $this->calculated_age;
            }

            $from = new DateTime();
            $from->setTimestamp(strtotime($this->birthdate));
            $to = new DateTime();
            $diff = $to->diff($from);

            return $this->calculated_age = $diff->format('%y');

        } elseif($var == 'note') {
            if (!isset($this->note_obj)) {
                $this->note_obj = self::parseNote($this->deltager_note);
            }
            return $this->note_obj;

        } elseif(in_array($var, array_keys(self::$foreign_key_fields))) {
            $field_def = self::$foreign_key_fields[$var];
            $storage_field = $field_def['key_field']."_text";

            if (!isset($this->$storage_field)) {
                if ($this->storage[$field_def['key_field']] == NULL) return "";
                $result = $this->db->query("SELECT $field_def[name] FROM $field_def[table] WHERE $field_def[key] = ?", [$this->storage[$field_def['key_field']]]);
                $this->$storage_field = count($result) > 0 ? $result[0][$field_def['name']] : "";
            }
            return $this->$storage_field;

        } elseif (array_key_exists($var, $this->storage)) {
            return parent::__get($var);

        } else {
             return null;

        }
    }

    public function __set($varname, $value) {
        switch ($varname) {
            case 'arbejdsomraade':
                if (!$value || $value == 'null') {
                    $this->storage['work_area'] = null;
                    break;
                }
                $result = $this->db->query('SELECT id FROM organizer_categories WHERE name_da = ?', [$value]);
                if (count($result) == 1) {
                    $this->storage['work_area'] = $result[0]['id'];
                } else {
                    throw new FrameworkException('Work area name not recognized');
                }
                break;

            case 'land':
                $result = $this->db->query('SELECT code FROM countries WHERE name_da = ?', [$value]);
                if (count($result) == 1) {
                    $this->storage['country'] = $result[0]['code'];
                } else {
                    throw new FrameworkException('Country name not recognized');
                }
                break;

            case 'scenarie':
                if (!$value || $value == 'null') {
                    $this->storage['game_id'] = null;
                    break;
                }
                $result = $this->db->query('SELECT id FROM aktiviteter WHERE navn = ?', [$value]);
                if (count($result) == 1) {
                    $this->storage['game_id'] = $result[0]['id'];
                } else {
                    throw new FrameworkException('Game name not recognized');
                }
                break;

            default:
                parent::__set($varname, $value);

        }
    }

    public function __isset($var) {
        if ($var == 'alder') {
            return true;
        } elseif($var == 'note') {
            if (!isset($this->note_obj)) {
                $this->note_obj = self::parseNote($this->deltager_note);
            }
            return isset($this->note_obj);

        } else {
            return array_key_exists($var, $this->storage);
        }
    }

    /**
     * returns first and last name concatenated
     *
     * @access public
     * @return string
     */
    public function getName()
    {
        if (isset($this->nickname) && $this->nickname != ''){
            return $this->nickname;
        }
        return trim("{$this->fornavn} {$this->efternavn}");
    }

    /**
     * performs a wildcard search across the tables fields
     *
     * @param string|int @input - term to look for
     * @access public
     * @return array
     */
    public function wildcardSearch($input) {
        $objects = array();
        $fields = $this->getColumns();
        $select = $this->getSelect();
        foreach (explode(' ', $input) as $bit) {
            foreach ($fields as $field)
            {
                switch ($field)
                {
                    case 'id':
                        if (intval($bit)) {
                            $temp = EANToNumber($bit) ? EANToNumber($bit) : intval($bit);
                            $select->setWhereOr($field, '=', $temp);
                        }
                    case 'alder':
                        if (is_numeric($bit)) {
                            $select->setWhereOr($field, '=', $bit);
                        }
                        break;
                    case 'tlf':
                    case 'mobiltlf':
                    case 'postnummer':
                        if (is_numeric($bit))
                        {
                            $select->setWhereOr($field, 'like', "%{$bit}%");
                        }
                        break;
                    case "created":
                        if (strtotime($bit) > 0)
                        {
                            $select->setWhereOr($field, '=', "{$bit}");
                        }
                        break;
                    case "updated":
                        if (strtotime($bit) > 0) {
                            $select->setWhereOr($field, '=', "{$bit}");
                        }

                        break;
                    case 'fornavn':
                    case 'efternavn':
                    case 'nickname':
                    case 'adresse1':
                    case 'adresse2':
                    case 'by':
                    case 'land':
                    case 'email':
                    case 'arbejdsomraade':
                    case 'ungdomsklub':
                    case 'scenarie':
                    case 'skills':
                    case 'deltager_note':
                        $select->setWhereOr($field, 'like', "%{$bit}%");
                        break;
                    default:
                        break;
                }
            }
        }
        $select->setOrder('id', 'asc');
        $results = $this->findBySelectMany($select);
        if (!empty($results)) {
            $objects = $results;
        }
        return $objects;
    }


//{{{ get info functions

    /**
     * returns an array detailing which values are in the set
     *
     * @access public
     * @return array
     */
    public function getCollection(string $column){
        if (!$this->isLoaded() || !is_string($this->$column)) return [];

        $string = str_replace("'","", $this->$column);
        return $string == '' ? [] : explode(',', $string);
    }

    /**
     * returns aktiviteter the deltager has been put on
     *
     * @access public
     * @return array
     */
    public function getPladser()
    {
        if (!$this->isLoaded())
        {
            return array();
        }

        $spots = $this->createEntity('Pladser')->getDeltagerPladser($this);
        
        if (!$this->isDesigner()) return $spots;

        // Add runs of their board game for designers
        $duties = $this->getDesignerDuties();
        foreach ($duties as $duty) {
            $spots[] = $this->entity_factory->create('SpecialSpot', $duty['id'], $this, 'designer');
        }

        // Add special events for the participant type
        $autos = $this->getAutoSpots();
        foreach ($autos as $auto) {
            $spots[] = $this->entity_factory->create('SpecialSpot', $auto[0], $this, 'spiller');
        }

        uasort($spots, function($a, $b) {
            $atime = strtotime($a->getAfvikling()->start);
            $btime = strtotime($b->getAfvikling()->start);
            return $atime - $btime;
        });

        return $spots;
    }

    /**
     * returns wear the deltager has ordered
     *
     * @access public
     * @return array
     */
    public function getWear()
    {
        if (!$this->isLoaded())
        {
            return array();
        }
        return $this->createEntity('DeltagereWear')->getDeltagerWearBestillinger($this);
    }

    /**
     * returns GDS Vagter the deltager has been assigned
     *
     * @access public
     * @return array
     */
    public function getGDSVagter()
    {

        if (!$this->isLoaded()) {
            return array();
        }

        return $this->createEntity('DeltagereGDSVagter')->getDeltagerVagter($this);
    }

    /**
     * returns food time links the deltager has purchased when signing up
     *
     * @access public
     * @return array
     */
    public function getFoodOrderLinks()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        if (empty($this->madtider_links)) {
            $this->madtider_links = $this->createEntity('DeltagereMadtider')->getForParticipant($this);
        }

        return $this->madtider_links;
    }

    /**
     * returns food the deltager has purchased when signing up
     *
     * @access public
     * @return array
     */
    public function getMadtider()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        if (empty($this->madtider)) {
            $this->madtider = $this->createEntity('DeltagereMadtider')->getDeltagerMadtider($this);
        }

        return $this->madtider;
    }

    /**
     * returns what entry options the deltager selected on signup
     *
     * @access public
     * @return array
     */
    public function getIndgang()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        if (!$this->indgang_cached) {
            $this->indgang_cached = $this->createEntity('DeltagereIndgang')->getDeltagerIndgang($this);
        }

        return $this->indgang_cached;
    }

    /**
     * returns the users participant category
     *
     * @access public
     * @return bool|object
     */
    public function getBrugerKategori()
    {
        if (!$this->isLoaded()) {
            return false;
        }

        return $this->createEntity('BrugerKategorier')->getDeltagerCategory($this);
    }

    /**
     * wrapper for getBrugerKategori
     *
     * @access public
     * @return BrugerKategorier|false
     */
    public function getUserCategory()
    {
        return $this->getBrugerKategori();
    }

    /**
     * returns afviklinger the user has signed up for
     *
     * @access public
     * @return array
     */
    public function getTilmeldinger()
    {
        return $this->createEntity('DeltagereTilmeldinger')->getDeltagerTilmeldinger($this);
    }

    /**
     * returns gds vagter the user has signed up for
     *
     * @access public
     * @return array
     */
    public function getGDSTilmeldinger()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        return $this->createEntity('DeltagereGDSTilmeldinger')->getDeltagerTilmeldinger($this);
    }
//}}}

// {{{ set info methods

    /**
     * sets the value of a field with type set
     *
     * @param array $values - array of values to be set, each must be a valid option
     * @access public
     * @return bool
     */
    public function setCollection(string $column, array $values) {
        $accepted = $this->getAvailableValues($column);
        foreach ($values as &$value) {
            $value = strtolower($value);
            if (!in_array($value, $accepted)) return [
                'result' => 'error',
                'value' => $value,
            ];
        }
        $this->$column = implode(',', $values);
        return 'success';
    }

    /**
     * signs the user up for a gds vagt
     *
     * @param GDSVagter $gdsvagt
     *
     * @access public
     * @return bool
     */
    public function setGDSTilmelding(?GDSCategory $gdscategory, $period) {
        if (($gdscategory != null && !$gdscategory->isLoaded()) || !$this->isLoaded()) {
            return false;
        }

        $gdstilmelding = $this->createEntity('DeltagereGDSTilmeldinger');
        $gdstilmelding->deltager_id = $this->id;
        $gdstilmelding->category_id = $gdscategory == null ? 0 : $gdscategory->id;
        $gdstilmelding->period      = $period;
        return $gdstilmelding->insert();
    }

    /**
     * signs the user up for a gds vagt
     *
     * @param GDSVagter $gdsvagt
     *
     * @access public
     * @return bool
     */
    public function setGDSVagt(GDSVagter $gdsvagt) {
        if (!$gdsvagt->isLoaded() || !$this->isLoaded()) {
            return false;
        }

        $vagt = $this->createEntity('DeltagereGDSVagter');
        $vagt->deltager_id = $this->id;
        $vagt->gdsvagt_id = $gdsvagt->id;
        return $vagt->insert();
    }


    /**
     * sets a wear order for a specific wearprice,
     * amount and size
     *
     * @param DBObject $wearprice Wearprice to order
     *
     * @access public
     * @return $this
     */
    public function setWearOrder(DBObject $wearprice, $amount, ?array $attributes)
    {
        $this->createEntity('DeltagereWear')->setOrderDirect($this, $wearprice, $amount, $attributes);

        return $this;
    }

    /**
     * signs the user up for some wear, with a given amount and size
     *
     * @param object $wearpris - WearPriser entity
     * @param int $antal - number of the wear to purchase
     * @param string $size - size indicator (S -> 6XL)
     * @access public
     * @return bool
     */
    public function setWearBestilling($wear, $antal, $size, BrugerKategorier $kategori = null)
    {
        if (!is_object($wear) || !$wear->isLoaded() || !$this->isLoaded())
        {
            return false;
        }

        return $this->createEntity('DeltagereWear')->setBestilling($this, $wear, $antal, $size, $kategori);
    }

    public function setInfonautShirt($size)
    {
        if (!$this->isLoaded())
        {
            return false;
        }
        $wear = $this->createEntity('Wear')->findByName('T-Shirt Herre');
        return $this->createEntity('DeltagereWear')->setInfonautBestilling($this, $wear, 1, $size);
    }

    /**
     * signs the user up for a food option
     *
     * @param object $madtid - food option
     * @access public
     * @return bool
     */
    public function setMad($madtid)
    {
        if (!is_object($madtid) || !$madtid->isLoaded() || !$this->isLoaded())
        {
            return false;
        }
        $madlink = $this->createEntity('DeltagereMadtider');
        $madlink->deltager_id = $this->id;
        $madlink->madtid_id = $madtid->id;
        return $madlink->insert();
    }

    /**
     * signs the user up for a given afvikling, using given priority and gamer-type
     *
     * @param object $afvikling - afvikling entity
     * @param int $prioritet - the priority of this event to the user
     * @param string $tilmeldingstype - 'spiller' or 'spilleder'
     * @access public
     * @return bool
     */
    public function setAktivitetTilmelding($afvikling, $prioritet, $tilmeldingstype)
    {
        if (!is_object($afvikling) || !$afvikling->isLoaded() || !$this->isLoaded()) {
            return false;
        }

        return $this->createEntity('DeltagereTilmeldinger')->setTilmelding($this, $afvikling, $prioritet, $tilmeldingstype);
    }

    /**
     * signs the user up for a given entry type
     *
     * @param object $indgang - Indgang entity
     * @access public
     * @return bool
     */
    public function setIndgang($indgang)
    {
        if (!is_object($indgang) || !$indgang->isLoaded() || !$this->isLoaded())
        {
            return false;
        }
        $indgang_link = $this->createEntity('DeltagereIndgang');
        $indgang_link->deltager_id = $this->id;
        $indgang_link->indgang_id = $indgang->id;
        return $indgang_link->insert();
    }

    /**
     * puts a deltager in a group for an activitity
     *
     * @param object $hold - Hold entity
     * @param string $type - spiller|spilleder
     * @param int $pladsnummer - the number in the group
     * @access public
     * @return bool
     */
    public function setPlads($hold, $type, $pladsnummer = false)
    {
        return $this->createEntity('Pladser')->setDeltagerPlads($this, $hold, $type, $pladsnummer);
    }

// }}}

//{{{ calc price methods

    /**
     * calculates what the participant should be paying for entry
     *
     * @access public
     * @return int
     */
    public function calcEntry()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if (!$this->entryCost) {
            $this->entryCost = $this->calcEntranceTickets() + $this->calcSleepTickets();
        }

        return $this->entryCost;
    }

    public function calcEntranceTickets()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if (!$this->entranceTicketCost) {
            $result  = 0;

            foreach ($this->getIndgang() as $i) {
                if ($i->isEntrance()) {
                    $result += $i->pris;
                }
            }

            $this->entranceTicketCost = $result;
        }

        return $this->entranceTicketCost;
    }

    public function calcSleepTickets()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if (!$this->sleepTicketCost) {
            $result   = 0;
            foreach ($this->getIndgang() as $i) {
                if ($i->isSleepTicket()) {
                    $result += $i->pris;

                }
            }

            $this->sleepTicketCost = $result;
        }

        return $this->sleepTicketCost;
    }

    /**
     * returns true if the participant sleeps at fastaval,
     * i.e. has bought sleep access
     *
     * @access public
     * @return bool
     */
    public function sleepsOnPremises()
    {
        $sleep_filter = function ($x) {
            return $x->isSleepTicket();
        };

        return count(array_filter($this->getIndgang(), $sleep_filter)) > 0;
    }

    /**
     * calculates what the participant should be paying for wear
     *
     * @access public
     * @return int
     */
    public function calcFood()
    {
        if (!$this->isLoaded())
        {
            return 0;
        }
        if (!$this->foodCost)
        {
            $result = 0;

            $food_credits = [
                'breakfast' => 0,
                'dinner' => 0,
            ];

            if ($this->financial_struggle == 'ja') {
                $food_credits['breakfast'] = 2;
                $food_credits['dinner'] = 2;
            }

            foreach ($this->getMadtider() as $food) {
                $foodprice = $food->getMad()->pris;
                if ($foodprice <= 0) continue;

                if ($food->isBreakfast() && $food_credits['breakfast'] > 0) {
                    $food_credits['breakfast']--;
                    continue;
                } elseif ($food->isDinner() && $food_credits['dinner'] > 0) {
                    $food_credits['dinner']--;
                    continue;
                }
                $result += $foodprice;
            }
            $this->foodCost = $result;
        }
        return $this->foodCost;
    }

    /**
     * calculates what the participant should be paying for wear
     *
     * @access public
     * @return int
     */
    public function calcWear()
    {
        if (!$this->isLoaded())
        {
            return 0;
        }
        if (!$this->wearCost)
        {
            $result = 0;
            foreach ($this->getWear() as $w)
            {
                $result += $w->getWearpris()->pris * $w->antal;
            }
            $this->wearCost = $result;
        }
        return $this->wearCost;
    }

    /**
     * calculates what the participant should be paying for wear
     *
     * @access public
     * @return int
     */
    public function calcActivities($signups = false)
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if($signups == true) {
            if (isset($this->gameSignupCost)) return $this->gameSignupCost;
        } else {
            if (isset($this->gameCost)) return $this->gameCost;
        }

        $result  = 0;
        $pladser = $signups ? $this->getTilmeldinger() : $this->getPladser();

        foreach ($pladser as $g) {
            $result += ($g->type == 'spiller' || $g->tilmeldingstype == 'spiller') ? $g->getAktivitet()->pris : 0;
        }

        if($signups == true) {
            $this->gameSignupCost = $result;
        } else {
            $this->gamesCost = $result;
        }

        return $result;
    }

    /**
     * returns the amount the participant should pay for ALEA
     * membership
     *
     * @access public
     * @return int
     */
    public function calcAlea()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if (!$this->aleaCost) {
            $result   = 0;
            foreach ($this->getIndgang() as $i) {
                if ($i->isAleaMembership()) {
                    $result += $i->pris;
                }
            }

            $this->aleaCost = $result;
        }

        return $this->aleaCost;
    }

    /**
     * calculates other costs, such as banquet
     *
     * @access public
     * @return int
     */
    public function calcOtherStuff() {
        if (!$this->isLoaded())
        {
            return 0;
        }
        if (empty($this->otherCosts)) {
            $result = 0;
            foreach($this->getIndgang() as $i) {
                if (!$i->isEntrance() && !$i->isSleepticket() && !$i->isAleaMembership()) {
                    $result += $i->pris;
                }
            }
            $this->otherCosts = $result;
        }
        return $this->otherCosts;
    }

    public function calcRichBastard()
    {
        $amount = 0;
        if ($this->rig_onkel == 'ja') {
            $amount += self::RICH_BASTARD_DONATION;
        }

        if ($this->hemmelig_onkel == 'ja') {
            $amount += self::RICH_BASTARD_DONATION;
        }

        return $amount;
    }

    /**
     * calculates what the participant should actually pay
     *
     * @access public
     * @return int
     */
    public function calcRealTotal()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if (!$this->realCost) {
            $this->realCost = $this->calcTotal(false);
        }

        return $this->realCost;
    }

    /**
     * calculates what the participant should pay based on signup choices
     *
     * @access public
     * @return int
     */
    public function calcSignupTotal()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if (!$this->signupCost) {
            $this->signupCost = $this->calcTotal(true);
        }

        return $this->signupCost;
    }

    /**
     * Calculate total price for participant,
     * 
     * @param $signup:
     *      - false: use only assigned activities
     *      - true: use all activities signed up for
     */
    private function calcTotal($signup) {
        $result = 0;
        $result += $this->calcEntry() + $this->calcFood() + $this->calcActivities($signup);
        $result += $this->calcWear() + $this->calcRichBastard() + $this->calcAlea() + $this->calcOtherStuff();
        return $result;
    }

//}}}

    /**
     * returns whether the deltager is an arrangoer or not
     *
     * @return bool
     * @access public
     */
    public function isArrangoer()
    {
        if (!$this->isLoaded()) {
            return false;
        }
        return $this->createEntity('BrugerKategorier')->findById($this->brugerkategori_id)->isArrangoer();
    }

    /**
     * returns an array of values that people can select from
     * these are also the strings that will be accepted in the array for setCollection
     *
     * @access public
     * @return array
     */
    public function getAvailableValues($column)
    {
        $cinfo = $this->getColumnInfo();
        $values = $cinfo[$column];
        $accepted = [];
        if (preg_match('/^set\((.*)\)$/i', $values, $matches)) {
            $matches[1] = str_replace("'", "", $matches[1]);
            $accepted = explode(',',$matches[1]);
        }
        return $accepted;
    }

    /**
     * returns true if the participant signed up for the shift
     *
     * @param object $vagt GDSVagter entity
     *
     * @access public
     * @return bool
     */
    public function signedUpForGDSShift(GDSVagter $vagt)
    {
        if (!$this->isLoaded() || !$vagt->isLoaded()) {
            return false;
        }

        return $this->createEntity('DeltagereGDSTilmeldinger')->participantSignedUpForShift($this, $vagt);
    }

    /**
     * returns true if the participant has reached the limit of shifts
     * normally 1, 2 if you have selected flere_gdsvagter
     *
     * @access public
     * @return bool
     */
    public function hasMaxShifts()
    {
        if (!$this->isLoaded())
        {
            return false;
        }
        if (!($shifts = $this->createEntity('DeltagereGDSVagter')->countDeltagerVagter($this)))
        {
            return false;
        }
        if ($this->supergds == 'ja')
        {
            return false;
        }
        if ($this->desired_diy_shifts == 0 || $shifts < $this->desired_diy_shifts)
        {
            return false;
        }
        return true;
    }
    /**
     * returns true if the deltager is slated for an activity that either starts
     * or ends in the given period
     *
     * @param string $start  Start time in 'yyyy-mm-dd hh:mm[:ss]' format
     * @param string $end    End time in 'yyyy-mm-dd hh:mm[:ss]' format
     * @param bool   $no_gds Don't bother checking gds shifts
     *
     * @access public
     * @return bool
     */
    public function isBusyBetween($start, $end, $role = null)
    {
        $start = strtotime($start);
        $slut  = strtotime($end);

        if (!$this->isLoaded()) {
            return false;
        }

        if (!$this->busy_cache) {
            $busy_cache = array();

            foreach ($this->getPladser() as $plads) {
                if ($role && $role != $plads->type) {
                    continue;
                }

                $afv = $plads->getAfvikling();

                if ($afv->getAktivitet()->tids_eksklusiv == 'nej') {
                    continue;
                }

                $busy_cache[] = array('start' => strtotime($afv->start), 'slut' => strtotime($afv->slut));

                if ($afv->hasMultiBlok()) {
                    foreach ($afv->getMultiBlok() as $multiblok) {
                        $busy_cache[] = array('start' => strtotime($multiblok->start), 'slut' => strtotime($multiblok->slut));
                    }
                }
            }

            foreach ($this->getGDSVagter() as $vagt) {
                $busy_cache[] = array('start' => strtotime($vagt->start), 'slut' => strtotime($vagt->slut));
            }

            $this->busy_cache = $busy_cache;
        }

        foreach ($this->busy_cache as $busy) {
            if (($start >= $busy['start'] && $start < $busy['slut']) || ($slut > $busy['start'] && $slut <= $busy['slut']) || ($start < $busy['start'] && $slut > $busy['slut'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * returns highest number of seconds the player can possibly
     * play, based on signup choices
     *
     * @access public
     * @return int
     */
    public function getPossiblePlaytime()
    {
        if (!$this->isLoaded())
        {
            return 0;
        }
        $ids = array();
        foreach ($this->getTilmeldinger() as $til)
        {
            if ($til->tilmeldingstype == 'spilleder' || $til->getActivity()->type === 'system')
            {
                continue;
            }
            $ids[] = $til->afvikling_id;
        }
        if (!empty($ids))
        {
            $select = $this->createEntity('Afviklinger')->getSelect();
            $select->setWhere('id', 'in',$ids);
            $afviklinger = $this->createEntity('Afviklinger')->findBySelectMany($select);
            $select = $this->createEntity('AfviklingerMultiblok')->getSelect();
            $select->setWhere('afvikling_id', 'in',$ids);
            $afviklinger_multi = $this->createEntity('AfviklingerMultiblok')->findBySelectMany($select);
        }
        if (empty($afviklinger))
        {
            return 0;
        }
        $strategy = new Strategy;
        $afviklinger = $strategy->sortSchedules(array_merge($afviklinger, $afviklinger_multi));

        $answer = $strategy->getMaxTimeForSchedules($afviklinger);

        return $answer;
    }

    /**
     * creates a new password for a participant
     * and stores a cleartext version of it while the object lives
     *
     * @access public
     * @return void
     */
    public function createPass()
    {
        $this->password = sprintf('%06d', mt_rand(0, 999999));
    }

    /**
     * returns bool on whether the participant registered
     * late or in time
     *
     * @access public
     * @return bool
     */
    public function hasRegisteredLate()
    {
        if (!$this->isLoaded()) return false;
        return strtotime($this->signed_up) > strtotime(SIGNUPEND);
    }

    /**
     * returns the next participant, i.e. the next available
     * id used to create a participant
     *
     * @access public
     * @return Deltagere
     */
    public function getNext()
    {
        if (!$this->isLoaded())
        {
            return false;
        }
        if (!$this->next_participant)
        {
            $select = $this->getSelect();
            $select->setWhere('id', '>', $this->id);
            $select->setLimit(1);
            $select->setOrder('id', 'ASC');
            $this->next_participant = $this->createEntity('Deltagere')->findBySelect($select);
        }
        return $this->next_participant;
    }

    /**
     * returns the previous participant, i.e. the previous
     * id used to create a participant
     *
     * @access public
     * @return Deltagere
     */
    public function getPrevious()
    {
        if (!$this->isLoaded())
        {
            return false;
        }
        if (!$this->prev_participant)
        {
            $select = $this->getSelect();
            $select->setWhere('id', '<', $this->id);
            $select->setLimit(1);
            $select->setOrder('id', 'DESC');
            $this->prev_participant = $this->createEntity('Deltagere')->findBySelect($select);
        }
        return $this->prev_participant;
    }

    /**
     * returns a string describing the place the participant will sleep
     *
     * @access public
     * @return Lokaler|false
     */
    public function getSoveplads()
    {
        if (!$this->isLoaded() || !$this->sovelokale_id || !($lokale = $this->createEntity('Lokaler')->findById($this->sovelokale_id))) {
            return false;
        }

        return $lokale;
    }

    public function hasReceivedFood(Madtider $madtid) {
        if (!$this->isLoaded() || !$madtid->isLoaded()) {
            return false;
        }
        $select = $this->createEntity('DeltagereMadtider')->getSelect()
            ->setWhere('deltager_id', '=', $this->id)
            ->setWhere('madtid_id', '=', $madtid->id)
            ->setWhere('received', '=', 1);
        return !!$this->createEntity('DeltagereMadtider')->findBySelectMany($select);
    }

    public function markFoodReceived(Madtider $madtid) {
        $participant_fooditem = $this->getFoodItemLink($madtid);
        $participant_fooditem->received = 1;
        return $participant_fooditem->update();
    }

    public function markFoodNotReceived(Madtider $madtid) {
        $participant_fooditem = $this->getFoodItemLink($madtid);
        $participant_fooditem->received = 0;
        return $participant_fooditem->update();
    }

    public function getFoodItemLink($madtid) {
        if (!$this->isLoaded() || !$madtid->isLoaded()) {
            throw new FrameworkException("Bad input");
        }
        $select = $this->createEntity('DeltagereMadtider')->getSelect()
            ->setWhere('deltager_id', '=', $this->id)
            ->setWhere('madtid_id', '=', $madtid->id);
        if (!($dm = $this->createEntity('DeltagereMadtider')->findBySelect($select))) {
            throw new FrameworkException("Participant has not signed up for food-period");
        }
        return $dm;
    }

    protected function getNextInRange(array $range, array $excepted = array()) {
        if (!isset($range[0]) || !isset($range[1])) {
            throw new Exception('No range');
        }

        $query = '
SELECT
    id
FROM
    ' . $this->tablename . '
WHERE
    id BETWEEN ? AND ?
ORDER BY
    id
';

        $ids = $this->db->query($query, $range);

        $range_ids = array_flip(range($range[0], $range[1]));

        foreach ($ids as $id) {
            unset($range_ids[$id['id']]);
        }

        $id = array_shift(array_flip($range_ids));

        return $id;
    }

    protected function getNextId(array $excepted = array()) {
        $exceptions = '';

        $id_range = range(1, 5000);

        $ranges = array();

        foreach ($excepted as $range) {
            if (isset($range[0]) && isset($range[1])) {
                $exceptions .= 'AND id + 1 NOT BETWEEN ' . intval($range[0]) . ' AND ' . intval($range[1]) . ' AND id != ' . intval($range[0]) . ' ';

                $id_range = array_diff($id_range, range($range[0], $range[1]));
            }
        }

        $query = '
SELECT
    id
FROM
    ' . $this->tablename . '
WHERE
    id IN (' . implode(', ', $id_range) . ')
';

        try {
            $ids = array_map(function ($x) {return $x['id'];}, $this->db->query($query));

        } catch (Exception $e) {
            $ids = array();
        }

        $usable_ids = array_diff($id_range, $ids);

        $id = array_shift($usable_ids);

        return $id;
    }

    protected function idAvailable($id) {
        $query = '
SELECT
    id
FROM
    ' . $this->tablename . '
WHERE
    id = ?
';

        $ids = $this->db->query($query, array($id));

        return count($ids) === 0;
    }

    protected function setInsertId() {
        if ($this->email == '1@makey.biz' && $this->idAvailable(911)) {
            $this->id = 911;

        } elseif ($this->brugerkategori_id == 3) {
            $this->id = $this->getNextInRange(array(900, 929), array(911));

        } else {
            $this->id = $this->getNextId(array(array(900, 929)));
        }
    }

    /**
     * overrides DBObject::insert() in order
     * to normalize phone and email
     *
     * @access public
     * @return bool|int
     */
    public function insert() {
        $this->normalizePhone();
        $this->normalizeEmail();

        $this->setInsertId();

        $this->created = date('Y-m-d H:i:s');
        $this->updated = date('Y-m-d H:i:s');

        return parent::insert();
    }

    /**
     * overrides DBObject::insert() in order
     * to normalize phone and email
     *
     * @access public
     * @return bool|int
     */
    public function update() {
        $this->normalizePhone();
        $this->normalizeEmail();

        $this->updated = date('Y-m-d H:i:s');

        return parent::update();
    }

    /**
     * for Danish numbers, removes parts not
     * needed, like +45 and spaces
     *
     * @access protected
     * @return $this
     */
    protected function normalizePhone() {
        foreach (array('tlf' => $this->tlf, 'mobiltlf' => $this->mobiltlf) as $field => $value) {
            $this->$field = preg_replace('/(\\+dk|0045)(\\d{8})/', '$2', trim($value));
        }

        return $this;
    }

    /**
     * trims the email of the participant
     *
     * @access protected
     * @return $this
     */
    protected function normalizeEmail() {
        $this->email = trim($this->email);
        return $this;
    }

    /**
     * returns fieldnames that make sense
     *
     * @access public
     * @return array
     */
    public function getHumanReadableFieldNames()
    {
        return $this->human_readable_fieldnames;
    }

    public function getNoteNames(String $lang = null) {
        $lang = $lang ?? 'da';
        return self::$note_names[$lang];
    }

    /**
     * overrides parent method to delete all
     * related information first
     *
     * @access public
     * @return bool
     */
    public function delete()
    {
        $this->removeAllWear()
            ->removeActivitySignups()
            ->removeEntrance()
            ->removeDiySignup()
            ->removeFood();

        foreach ($this->getPladser() as $play) {
            $play->delete();
        }

        foreach ($this->getGDSVagter() as $shift) {
            $shift->delete();
        }

        return parent::delete();
    }

    /**
     * removes all assigned food for the participant
     *
     * @access public
     * @return $this
     */
    public function removeFood()
    {
        foreach ($this->createEntity('DeltagereMadtider')->getForParticipant($this) as $food) {
            $food->delete();
        }

        return $this;
    }

    /**
     * removes all food ordered through the signup for the participant
     *
     * @access public
     * @return $this
     */
    public function removeOrderedFood()
    {
        $participant_food_entity = $this->createEntity('DeltagereMadtider');
        $select = $participant_food_entity->getSelect();
        $select->setLeftJoin('madtider', 'madtid_id', 'madtider.id');
        $select->setLeftJoin('mad', 'mad_id', 'mad.id');
        $select->setWhere('hidden', '=', 'false');
        $select->setWhere('deltager_id', '=', $this->id);

        foreach ($participant_food_entity->findBySelectMany($select) as $food) {
            $food->delete();
        }

        return $this;
    }

    /**
     * removes Diy signups
     *
     * @access public
     * @return $this
     */
    public function removeDiySignup()
    {
        foreach ($this->getGDSTilmeldinger() as $diy_signup) {
            $diy_signup->delete();
        }

        return $this;
    }

    /**
     * removes all entrance relationships
     *
     * @access public
     * @return $this
     */
    public function removeEntrance()
    {
        foreach ($this->createEntity('DeltagereIndgang')->getForParticipant($this) as $entrance) {
            $entrance->delete();
        }

        return $this;
    }

    /**
     * Removes all entrance relationships except the given ids
     *
     * @access public
     * @return $this
     */
    public function removeEntranceExcept(Array $ids) {
        foreach ($this->createEntity('DeltagereIndgang')->getForParticipant($this) as $entrance) {
            if (in_array($entrance->indgang_id, $ids)) continue;
            $entrance->delete();
        }

        return $this;
    }

    /**
     * removes all activity signups
     *
     * @access public
     * @return $this
     */
    public function removeActivitySignups()
    {
        foreach ($this->getTilmeldinger() as $signup) {
            $signup->delete();
        }

        return $this;
    }

    /**
     * removes all wear for the participant
     *
     * @access public
     * @return $this
     */
    public function removeAllWear()
    {
        foreach ($this->getWear() as $wear) {
            $wear->delete();
        }

        return $this;
    }

    /**
     * sends the message to the participant
     *
     * @param SMSSending $sender  SMS sender help class
     * @param string     $message Message to send
     *
     * @access public
     * @return bool
     */
    public function sendSMS(SMSSending $sender, $message)
    {
        if ($this->medbringer_mobil !== 'ja' || $this->udeblevet !== 'nej' || !$sender->checkNumber($this->mobiltlf) || !$sender->safetyCheck() || $this->checkin_time == '0000-00-00 00:00:00') {
            return false;
        }

        return $sender->sendMessage($this, $this->mobiltlf, $message);
    }

    public function speaksDanish()
    {
        return $this->main_lang == 'da';
    }

    public function getEan8Number(String $year) {
        return numberToEAN8($year . str_pad($this->id, 5, '0', STR_PAD_LEFT));
    }

    /**
     * sends a message to an iOS device
     *
     * @access public
     * @return result
     */
    public function sendIosMessage($certificate_path, $message, $title)
    {
        if (empty($this->apple_id)) {
            throw new Exception('No Apple ID set for participant - cannot send message');
        }

        $ios_message = new IosPushMessage($certificate_path, $this->apple_id);

        return $ios_message->send($title, $message);
    }

    /**
     * sends a message to a firebase device
     *
     * @access public
     * @return result
     */
    public function sendFirebaseMessage($server_key, $message, $title)
    {
        if (empty($this->gcm_id)) {
            throw new Exception('No device ID set for participant - cannot send message');
        }

        $firebase_message = new FirebaseMessage($server_key, $this->gcm_id);

        return $firebase_message->send($title, $message);
    }

    public function sendGcmMessage($server_api_token, $message, $title)
    {
        if (empty($this->gcm_id)) {
            throw new Exception('No GCM ID set for participant - cannot send message');
        }

        $gcm = new GcmPushMessage($server_api_token);
        $gcm->setDevices(array($this->gcm_id));

        return $gcm->send($message, array('title' => $title));
    }

    /**
     * returns age as a float
     *
     * @access public
     * @return float
     */
    public function getAge(DateTime $at_time = null)
    {
        if (!$this->birthdate) {
            return -1;
        }

        $now = $at_time ? $at_time : new DateTime();

        $diff = $now->diff(new DateTime($this->birthdate));

        return floor($diff->y);
    }

    /**
     * returns true if the instance is younger than the
     * provided limit
     *
     * @param int      $limit   Age limit to check against
     * @param DateTime $at_time Optional time for comparison
     *
     * @access public
     * @return bool
     */
    public function isYoungerThan($limit, DateTime $at_time = null)
    {
        return $this->getAge($at_time) < intval($limit);
    }

    /**
     * returns true if the instance is older than or
     * the same age as the provided limit
     *
     * @param int      $limit   Age limit to check against
     * @param DateTime $at_time Optional time for comparison
     *
     * @access public
     * @return bool
     */
    public function isOlderThan($limit, DateTime $at_time = null)
    {
        return !$this->isYoungerThan($limit, $at_time);
    }

    /**
     * returns path to participants uploaded photo, if available
     *
     * @access public
     * @return string|false
     */
    public function getCroppedPhotoPath()
    {
        $query = '
SELECT
    identifier
FROM
    participantphotoidentifiers
WHERE
    participant_id = ?
';

        $row = $this->db->query($query, [$this->id]);

        if (!$row) {
            return false;
        }

        $results = glob(PUBLIC_PATH . 'uploads/photo-cropped-' . mb_strtolower($row[0]['identifier']) . '*');

        if (!$results) {
            return false;
        }

        return $results[0];
    }

    /**
     * returns true if the participant takes a turn as a gamemaster
     *
     * @access public
     * @return bool
     */
    public function isGamemaster()
    {
        if (!$this->id) {
            return false;
        }

        $query = '
SELECT
    COUNT(*) AS turns
FROM
    pladser AS p
WHERE
    p.deltager_id = ?
    AND p.type = "spilleder"
';

        $result = $this->db->query($query, [$this->id]);

        return intval($result[0]['turns']) > 0;
    }

    /**
     * returns true if the participant only has entrance
     * to the convention for a single day
     *
     * @access public
     * @return bool
     */
    public function isSingleDayParticipant()
    {
        return false;
        return count(array_filter($this->getIndgang(), function ($x) {
            return $x->isDayTicket() && $x->isEntrance();
        })) <= 1 && count(array_filter($this->getIndgang(), function ($x) {
            return $x->isEntrance() && !$x->isDayTicket();
        })) === 0;
    }

    public function setNote($name, $content) {
        if (!empty($this->deltager_note)) {
            $note = json_decode($this->deltager_note);
        } else {
            $note = new stdClass();
        }
        
        $note->$name = $content;
        // Decode HTML special chars and save to storage
        parent::__set('deltager_note', json_encode($note));

        // Update note object
        $this->note_obj = self::parseNote($this->deltager_note);
    }

    public static function parseNote($note){
        if ($jsnon_note = json_decode($note)) {
            $note_obj = new stdClass();
            foreach($jsnon_note as $key => $value) {
                $note_obj->$key = new stdClass();
                $note_obj->$key->content = $value;
                $note_obj->$key->name = isset(self::$note_names['da'][$key]) ? self::$note_names['da'][$key] : $key;
                $note_obj->$key->name_en = isset(self::$note_names['en'][$key]) ? self::$note_names['en'][$key] : $key;
            }
            return $note_obj;
        }

        return null;
    }

    public function getCountry($lang) {
        $result = $this->db->query("SELECT * FROM countries WHERE code = ?", [$this->storage['country']]);
        return count($result) === 1 ? $result[0]["name_$lang"] : "";
    }

    public function getGame($lang) {
        $result = $this->db->query("SELECT * FROM aktiviteter WHERE id = ?", [$this->storage['game_id']]);
        if (count($result) === 0) return "";
        
        return $lang == 'da' ? $result[0]["navn"] : $result[0]["title_en"];
    }

    public function getForeignKeyFields() {
        return self::$foreign_key_fields;
    }

    public function getPronoun(String $lang = null) {
        $lang = $lang ?? $this->main_lang;
        return self::$pronouns[$lang][$this->pronoun];
    }

    public static function getSpecialColumns() {
        return self::$special_columns;
    }

    public function getContryISO() {
        $result = $this->db->query("SELECT * FROM countries WHERE code = ?", [$this->storage['country']]);
        return count($result) === 1 ? $result[0]["iso"] : "";
    }

    public function getNickname() {
        if (!empty($this->storage['nickname'])) {
            return $this->storage['nickname'];
        } else {
            return explode(' ', $this->storage['fornavn'])[0];
        } 
    }

    public function getShortName() {
        if (!empty($this->storage['nickname'])) {
            return explode(' ', $this->storage['nickname'])[0];
        } else {
            return explode(' ', $this->storage['fornavn'])[0];
        } 
    }

    /**
     * Check if participant is a board game designer
     */
    public function isDesigner() {
        return $this->storage['work_area'] == 2;
    }

    /**
     * Get all runs where the activity is the designer's board game
     */
    public function getDesignerDuties() {
        if (!$this->isDesigner()) return [];

        if (!isset($this->storage['game_id'])) return [];

        $result = $this->db->query(
            "SELECT * FROM afviklinger WHERE aktivitet_id = ?",
            [$this->storage['game_id']],
        );
        return $result;
    }

    /**
     * Get special activities for designers and authors
     */
    public function getAutoSpots() {
        $area = "";
        if (in_array($this->storage['work_area'], [1, 54, 55, 56])) {
            $area = "author";
        } elseif (in_array($this->storage['work_area'], [2, 17, 18, 19])) {
            $area = "designer";
        } else {
            return [];
        }

        $result = $this->db->query(
            "SELECT * FROM afviklinger af JOIN aktiviteter ak ON af.aktivitet_id = ak.id WHERE ak.auto_signup_category = ?",
            [$area],
        );
        return $result;
    }
}
