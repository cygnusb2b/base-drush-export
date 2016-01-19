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
                'Compaines'         => 'Taxonomy\\Organization', // no company, use organization or override?
                'Companies'         => 'Taxonomy\\Organization', // no company, use organization or override?
                'County'            => 'Taxonomy\\Location',  // Location data but much more specific than locations data - use in region or put into Location with other data?
                'Locations'         => 'Taxonomy\\Location',
                'Main'              => 'Taxonomy\\Category', // category or topic?
                'People'            => 'Taxonomy\\Person',
                'Subjects'          => 'Taxonomy\\Tag',  // topic or tag?
                // 'Image Galleries'   => 'Taxonomy\\Tag',  // topic or tag?
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
        $users = $this->loadUsers();
        $this->writeln(sprintf('Importing %s Customers.', count($users)), false, true);

        $formatted = [];
        foreach ($users as $user) {

            if ((int) $user->uid === 0) {
                continue;
            }
            $type = "Customer";

            $formatted_user = null;
            $formatted_user = [
                '_id'       => (int) $user->uid,
                'type'      => 'Customer',
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
                foreach ($user->roles as $roleId => $role) {
                    $formatted_user['roles'][] = $role;
                }
            } else {
                $formatted_user['roles'][] = 'registered';
            }
            if ($user->profile_subscription == '1') {
                $formatted_user['roles'][] = 'paid';
            }


            if (!empty($user->profile_sitemasonid)) $formatted_user['leagacy']['sitemasonid'] = $user->profile_sitemasonid;

            if (empty($formatted_user['email'])) $formatted_user['email'] = $formatted_user['firstName'].'_'.$formatted_user['lastName'].'@fakedata.com';

            $formatted[] = $formatted_user;
        }

        if (!empty($formatted)) {
            $this->writeln(sprintf('Customer: Inserting %s customers.', count($formatted)));
            $collection = $this->database->selectCollection('Customer');
            $collection->batchInsert($formatted);
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
        $sql = "SELECT invoiceId, uid, typeId, paymentTypeId, amount, description, created, auxField_StartDate as startdate, auxField_EndDate as enddate, auxField_TransactionId, auxField_NodeId, auxField_CustomerProfileId, auxField_TransactionId, auxField_TransactionLog, auxField_CustomerPaymentProfileId, auxField_CustomerShippingAddressId FROM invoicing ORDER BY invoicing.invoiceId ASC";
        $paymentTypes = [
            "Other",
            "Credit Card",
            "Check",
            "Complimentary: Promo",
            "Complimentary: Gift",
            "Complimentary: Subscription Prospect",
            "Complimentary: Advertising Prospect",
            "Complimentary: Other",
            "Complimentary: Promo 14-Day",
            "Complimentary: Promo 60-Day",
            "Complimentary: Promo 30-Day"
        ];

        $results = db_query($sql);
        while ($row = db_fetch_array($results)) {
            $row['paymentTypeId'] = $paymentTypes[$row['paymentTypeId']];
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
                case 1:     // 1 Year (Standard)
                case 4:     // 1 Year (Introductory)
                    $order['interval'] = 'year';
                    $order['interval_value'] = 1;
                    break;
                case 2:
                    $order['interval'] = 'year';
                    $order['interval_value'] = 2;
                    break;
                case 3:
                    $order['interval'] = 'year';
                    $order['interval_value'] = 3;
                    break;
                case 0:     // "Other"
                case 5:     // wtf is this - bad data on their end?
                default:
                    $order['interval'] = 'day';
                    $order['interval_value'] = 30;
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
