<?php

namespace Cygnus\DrushExport;

use DateTimeZone;
use DateTime;

/**
 * Provides support for exporting from Drupal 6 for Nashville Post
 */
class ExportNVP extends ExportD6
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'nashvillepost'      => [
            'Taxonomy'  => [
                'Companies'         => 'Taxonomy\\Organization', // no company, use organization or override?
                'County'            => 'Taxonomy\\Location',  // Location data but much more specific than locations data - use in region or put into Location with other data?
                'Locations'         => 'Taxonomy\\Location',
                'Main'              => 'Taxonomy\\Category', // category or topic?
                'People'            => 'Taxonomy\\Person',
                'Subjects'          => 'Taxonomy\\Tag',  // topic or tag?
                'Tags'              => 'Taxonomy\\Tag'
            ],
            'Content'   => [
                'article'       => 'Website\\Content\\Article',
                'blog'          => 'Website\\Content\\Blog',
                'gallery'       => 'Website\\Content\\MediaGallery',
                'need_to_know'  => 'Website\\Content\\Article',
            ],
            'Section'   => [
                'callout'          => 'Website\\Section',
            ],
            'database'          => 'import_nvp',
            'host'              => 'nashvillepost.com'
        ]

    ];

    // City Paper has no issues, creating single issue within the publication to map all legacy content to using this month/year
    public $cityPaperMonth = "06";
    public $cityPaperYear = "2015";

    /**
     * Main export function.
     */
    public function execute()
    {
        $this->writeln(sprintf('Starting import for %s', $this->key), true, true);

        $this->importUsers();
        $this->importGroups();
        $this->importOrders();
        $this->importTaxonomies();
        $this->importNodes();

        $this->writeln('Import complete.', true, true);
    }

    protected function importMagazineIssueNodes()
    {
        $collection = $this->database->selectCollection('Issue');
        $magazines = $this->getMagazineData();

        // need to manually 'add' a default issue for all 'city paper' data per jw
        $cityPaperIssue = new \stdClass;
        $cityPaperIssue->id = 0;
        $cityPaperIssue->month = (string) $this->cityPaperMonth;
        $cityPaperIssue->year = (string) $this->cityPaperYear;
        $cityPaperIssue->image = 'citypaper.png';
        $magazines[] = $cityPaperIssue;

        $count = $total = count($magazines) +1;

        $this->writeln(sprintf('Nodes: Importing %s Magazine Issues.', $count));

        $tz = new DateTimeZone('America/Chicago');

        $formatted = [];
        foreach ($magazines as $magazine) {

            $amonth = (strlen($magazine->month) == 1) ? '0'.$magazine->month : $magazine->month;
            $dateStr = $magazine->year.$amonth.'01';
            $mailDate = new DateTime(date('c', strtotime($dateStr)), $tz);

            $title = $mailDate->format('M').' '.$mailDate->format('y');
            $shortName = strtolower($mailDate->format('My'));

            // making up issueIds because not nodes (custom drupal module) - adding publication suffix to keep them unique for later legacy lookup / references
            if ($magazine->id != 0) {
                $publication = "Nashville Post Magazine";
                $_id = $dateStr.'np';
            } else {
                $publication = "City Paper";
                $_id = $dateStr.'cp';
            }

            $kv = [
                '_id'               => $_id,
                'name'              => $title,
                'publication'       => $publication,
                'mailDate'          => $mailDate->format('c'),
                'status'            => 1,
                'legacy'            => [
                    'shortName'     => $shortName,
                    'date'          => $dateStr,
                    'legacyCover'   => $magazine->image // issue type supports coverImage but reference needing image content already imported - only 27 covers, worth scripting it?
                ]
            ];

            $formatted[] = $kv;
        }

        if (!empty($formatted)) {
            $this->writeln(sprintf('Nodes: Inserting %s Magazine Issues.', count($formatted)));
            $collection->batchInsert($formatted);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getMagazineData()
    {

        $query = "SELECT id, month, year, image FROM magazine_covers ORDER BY year,month asc";
        $resource = db_query($query);
        $magazineData = $this->getRaw($resource);
        return $magazineData;
    }

    protected function convertScheduling(&$node)
    {
        // check for print taxonomy
        foreach ($node->taxonomy AS $taxonomy) {
            if ($taxonomy['id'] == 180) {
                // taxonomy = tid 180 (vid:6) is 'Print Edition' = citypaper
                $publication = "City Paper";
                // citypaper is an issue-less publication - assigning all legacy content to single bucket one we created
                $issueId = $this->cityPaperYear.$this->cityPaperMonth.'01';
                // add publication suffix
                $issueId = $issueId.'cp';
                $this->addMagazineSchedule($node,$publication, $issueId);
            } else if ($taxonomy['id'] == 56) {
                // taxonomy = tid 56 (vid:6) is 'Nashville Post magazine'
                $publication = "Nashville Post magazine";
                // magazine issue is set by node.created being within range
                $issueId = $this->getMagazineIssue($node);
                // add publicaton suffix
                $issueId = $issueId.'np';
                $this->addMagazineSchedule($node,$publication, $issueId);
            }
        }
    }

    protected function addMagazineSchedule($node, $publication, $issueId=null, $section=null)
    {
        $collection = $this->database->selectCollection('ScheduleMagazine');
        $kv['content']['$id'] = (int) $node->nid;
        $kv['publication'] = $publication;
        $kv['issue']  = $issueId;
        $kv['section'] = $section;
        $collection->insert($kv);
    }

    public function getMagazineIssue($node)
    {
        // need to get the last issue prior to this node's created date
        $nodeYear = date('Y',$node->created);
        $nodeMonth = date('m',$node->created);
        $query = sprintf("SELECT month, year FROM magazine_covers WHERE year <= %s AND month <= %s ORDER BY year,month DESC LIMIT 1",$nodeYear,$nodeMonth);
        $resource = db_query($query);
        $issueData = $this->getRaw($resource);
        foreach ($issueData AS $issue) {
            $amonth = (strlen($issue->month) == 1) ? '0'.$issue->month : $issue->month;
            $issueId = $issue->year.$amonth.'01';
        }
        return $issueId;
    }

    /**
     * Iterates over users and exports them.
     */
    protected function importUsers()
    {
        $this->writeln('Importing Users.', false, true);
        $users = $this->loadUsers();

        $internal_roles = array('administrator','editor','archive editor','module administrator');

        $formatted = [];
        foreach ($users as $user) {

            if ((int) $user->uid === 0) {
                continue;
            }
            $type = "Customer";

            $formatted_user = null;
            $formatted_user = [
                '_id'       => (int) $user->uid,
                //'type'      => 'Customer',
                'username'  => $user->name,
                'password'  => $user->pass,
                'email'     => $user->mail,
            ];

            if (!empty($user->profile_subscription)) {
                $formatted_user['subscription']['status'] = $user->profile_subscription;
            }

            if (!empty($user->profile_authorize_id)) {
                $formatted_user['subscription']['auth'] = $user->profile_authorize_id;
            }

            if (!empty($user->profile_expirestimestamp)) {
                $formatted_user['subscription']['expires'] = new \MongoDate($user->profile_expirestimestamp);
            }

            if (!empty($user->profile_fname)) $formatted_user['firstName'] = $user->profile_fname;
            if (!empty($user->profile_lname)) $formatted_user['lastName'] = $user->profile_lname;
            if (!empty($user->profile_address1)) $formatted_user['address1'] = $user->profile_address1;
            if (!empty($user->profile_address2)) $formatted_user['address2'] = $user->profile_address2;
            if (!empty($user->profile_city)) $formatted_user['city'] = $user->profile_city;
            if (!empty($user->profile_state)) $formatted_user['state'] = $user->profile_state;
            if (!empty($user->profile_zipcode)) $formatted_user['postalCode'] = $user->profile_zipcode;
            if (!empty($user->profile_phone)) $formatted_user['phone'] = $user->profile_phone;
            if (!empty($user->profile_fax)) $formatted_user['fax'] = $user->profile_fax;

            if (!empty($user->created)) $formatted_user['created'] = new \MongoDate($user->created);
            if (!empty($user->login)) $formatted_user['lastLogin'] = new \MongoDate($user->login);
            if (!empty($user->access)) $formatted_user['lastSeen'] = new \MongoDate($user->access);

            if (!empty($user->roles)) {
                foreach ($user->roles AS $roleId => $role) {
                    $formatted_user['roles'][] = $role;
                    if (in_array($role,$internal_roles)) $type = 'User';
                }
            } else {
                $formatted_user['roles'][] = 'registered';
            }
            if ($user->profile_subscription == '1') {
                $formatted_user['roles'][] = 'paid';
            }


            if (!empty($user->profile_sitemasonid)) $formatted_user['leagacy']['sitemasonid'] = $user->profile_sitemasonid;

            if (empty($formatted_user['email'])) $formatted_user['email'] = $formatted_user['firstName'].'_'.$formatted_user['lastName'].'@fakedata.com';

            $formatted[$type][] = $formatted_user;
        }

        // @jp - jw said to disable / skip creating users here
        if (!empty($formatted['User'])) {
            $this->writeln(sprintf('Users: Inserting %s users.', count($formatted['User'])));
            $collection = $this->database->selectCollection('User');
            $collection->batchInsert($formatted['User']);
        }

        if (!empty($formatted['Customer'])) {
            $this->writeln(sprintf('Customer: Inserting %s customers.', count($formatted['Customer'])));
            $collection = $this->database->selectCollection('Customer');
            $collection->batchInsert($formatted['Customer']);
        }

    }

    protected function loadGroups()
    {
        $sql = 'SELECT groupName as name, gid, smgid from groups ORDER BY gid DESC';
        $results = db_query($sql);
        $out = [];
        while ($row = db_fetch_array($results)) {
            $row['gid'] = (int) $row['gid'];
            $row['smgid'] = (int) $row['smgid'];
            $row['users'] = [];
            $sql = 'SELECT uid from groupValues WHERE gid = '.$row['gid'] .';';
            $users = db_query($sql);
            while($r = db_fetch_array($users)) {
                $row['users'][] = (int) $r['uid'];
            }
            $out[] = $row;
        }
        return $out;
    }

    protected function importGroups()
    {
        $this->writeln('Importing Groups.', false, true);
        $groups = $this->loadGroups();
        $collection = $this->database->selectCollection('Groups');
        if (!empty($groups)) {
            $this->writeln(sprintf('Inserting %s groups.', count($groups)), false, true);
            $collection->batchInsert($groups);
        }
    }

    protected function loadOrders()
    {
        // auxFieldTransactionLog - binary BLOB
        // auxFieldNodeId - mostly blank, but if exists has node id to regulary content, article, etc - reference for page subscripion link was clicked? - only 330 invoices for all time
        // auxFieldStartDate / auxFieldEndDate are blank often - only present for 1/2/3 year subscriptions?  only after a certain point in website history - useable? never present when auxNodeFieldId is
        // smid - mystery field again (internal drupal?)
        // auxFieldProfileId, auxFieldPaymentProfileId, auxFieldShippingAddressId - purpose clear from name but no idea where data is - id to be used within authorize.net not drupal?
        $sql = "
            SELECT invoiceId, invoicing.uid, users.mail as email, invoicing_type.name AS invoiceType, invoicing_paymentType.name AS paymentType, amount, description, invoicing.created, auxField_StartDate AS startdate,
            auxField_EndDate AS enddate, profile_values.value as role_expire, auxField_TransactionId, auxField_NodeId, auxField_CustomerProfileId, auxField_CustomerPaymentProfileId, auxField_CustomerShippingAddressId
            FROM invoicing, invoicing_type, invoicing_paymentType, users, profile_values
            WHERE invoicing.typeId = invoicing_type.typeId AND invoicing.paymentTypeId = invoicing_paymentType.paymentTypeId AND users.uid = invoicing.uid AND  profile_values.uid = users.uid AND profile_values.fid=9
            ORDER BY invoicing.invoiceId ASC
        ";

        $results = db_query($sql);
        while ($row = db_fetch_array($results)) {
            $invoices[] = $row;
        }
        return $invoices;
    }

    /**
     * Iterates over users and exports them.
     */
    protected function importOrders()
    {
        $this->writeln('Importing Orders.', false, true);
        $orders = $this->loadOrders();

        $collection = $this->database->selectCollection('Orders');
        $formatted = [];
        foreach ($orders as $order) {
            switch ($order['invoiceType']) {
                case "1 Year":
                    $order['interval'] = 'year';
                    $order['interval_value'] = 1;
                    break;
                case "1 Year":
                    $order['interval'] = 'year';
                    $order['interval_value'] = 1;
                    break;
                case "1 Year (Standard)":
                    $order['interval'] = 'year';
                    $order['interval_value'] = 1;
                    break;
                case "1 Year (Introductory)":
                    $order['interval'] = 'year';
                    $order['interval_value'] = 1;
                    break;
                case "2 Year":
                    $order['interval'] = 'year';
                    $order['interval_value'] = 2;
                    break;
                case "3 Year":
                    $order['interval'] = 'year';
                    $order['interval_value'] = 3;
                    break;
                case "Other":
                    $order['interval'] = 'day';
                    $order['interval_value'] = 30;
                    break;
                case "Article":  // wtf is this - bad data on their end?
                    $order['interval'] = 'day';
                    $order['interval_value'] = 30;
                    break;
                default:
                    $order['interval'] = 'second';
                    $order['interval_value'] = 1;
                    break;
            }
            $formatted[] = $order;
        }
        if (!empty($formatted)) {
            $this->writeln(sprintf('Orders: Inserting %s Orders.', count($formatted)));
            $collection->batchInsert($formatted);
        }
    }

}
