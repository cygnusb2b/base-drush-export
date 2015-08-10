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

    protected function importMagazineIssueNodes()
    {
        /*
        if (!isset($this->map['Issue']) || empty($this->map['Issue'])) {
            $this->writeln(sprintf('You must set the issue map for %s:', $this->key), false, true);
            $types = $this->getTypes();
            $this->writeln(sprintf('Valid types: %s', implode(', ', $types)), true, true);
            die();
        }
        */

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
            //$this->addMagazineSchedule($node,$publication, $issueId);
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

}
