<?php

namespace Cygnus\DrushExport;

use InvalidArgumentException;
use MongoClient;
use DateTimeZone;
use DateTime;

/**
 * Wrapper for core logic in Drupal imports
 */
abstract class Export
{
    /**
     * @var array
     * The currently active import configuration.
     */
    protected $map = [];

    /**
     * @var string
     * The active import string (e.g; `stlouis`)
     */
    protected $key;

    /**
     * @var array
     * Available configurations for import
     */
    protected $configs = [];

    /**
     * @var MongoClient
     * The connected MongoClient instance.
     */
    protected $mongo;

    /**
     * @var MongoDatabase
     * The connected MongoDatabase instance.
     */
    protected $database;

    /**
     * Constructor. Sets up and clears existing import data
     *
     * @param string $key The config key
     * @param string $dsn The mongo DSN
     */
    public function __construct($key, $dsn)
    {
        $this->key = $key;
        if (!isset($this->configs[$key])) {
            throw new InvalidArgumentException(sprintf('Invalid config key specified. Valid keys: `%s`.', implode(',', array_keys($this->configs))));
        }
        $this->map = $this->configs[$this->key];
        $this->mongo = new MongoClient($dsn);
        $db = $this->mongo->selectDb($this->map['database']);
        $db->drop();
        $this->database = $this->mongo->selectDb($this->map['database']);
    }

    /**
     * Main export function.
     */
    public function execute()
    {
        $this->writeln(sprintf('Starting import for %s', $this->key), true, true);

        $this->importUsers();
        $this->importTaxonomies();
        $this->importNodes();

        $this->writeln('Import complete.', true, true);
    }

    /**
     * Handles output sanitization.
     *
     * @final
     * @access protected
     *
     * @param string $text The text to output
     * @param boolean $breakAfter Add a linebreak after the text
     * @param boolean $breakBefore Add a linebreak before the text
     */
    final protected function writeln($text, $breakAfter = false, $breakBefore = false)
    {
        // Enforce a line break on all lines.
        $text = sprintf("%s\r\n", $text);

        if (true === $breakAfter) {
            $text = sprintf("%s\r\n", $text);
        }

        if (true == $breakBefore) {
            $text = sprintf("\r\n%s", $text);
        }
        echo $text;
    }

    /**
     * Iterates over users and exports them.
     */
    protected function importUsers()
    {
        $this->writeln('Importing Users.', false, true);
        $users = $this->loadUsers();

        $collection = $this->database->selectCollection('User');
        $formatted = [];
        foreach ($users as $user) {
            if ((int) $user->uid === 0) {
                continue;
            }
            $formatted[] = [
                '_id'       => (int) $user->uid,
                'username'  => $user->name,
                'password'  => $user->pass,
                'email'     => $user->mail
            ];
        }
        if (!empty($formatted)) {
            $this->writeln(sprintf('Users: Inserting %s users.', count($formatted)));
            $collection->batchInsert($formatted);
        }
    }

    /**
     * Iterates over taxonomies and exports them.
     */
    protected function importTaxonomies()
    {
        $this->writeln('Importing Taxonomy.', false, true);
        $taxonomies = taxonomy_get_vocabularies();

        if (!isset($this->map['Taxonomy']) || empty($this->map['Taxonomy'])) {
            $this->writeln(sprintf('You must set the taxonomy map for %s:', $this->key), true, true);

            $types = [];
            foreach ($taxonomies as $taxonomy) {
                $types[] = $taxonomy->name;
            }

            $this->writeln(sprintf('Valid types: %s', implode(', ', $types)), true, true);
            die();
        }

        foreach ($taxonomies as $vocab) {
            if (isset($this->map['Taxonomy'][$vocab->name])) {
                $this->importTaxonomy($vocab);
            }
        }
    }

    /**
     * Iterates over nodes and exports them.
     */
    protected function importNodes()
    {
        $this->writeln('Importing Nodes.', false, true);

        $this->importWebsiteSectionNodes();
        $this->importMagazineIssueNodes();
        $this->importContentNodes();

    }

    /**
     * Returns loaded resource objects.
     *
     * @param mixed     $resource   A database resource for Drupal DBAL
     * @param string    $type       type of object to load
     *
     * @return array    An array of StdClass objects.
     */
    abstract protected function getObjects($resource, $type = 'node');

    /**
     * Returns raw resource results.
     *
     * @param mixed     $resource   A database resource for Drupal DBAL
     *
     * @return array    An array of StdClass objects.
     */
    abstract protected function getRaw($resource);

    /**
     * Formats generic array data for PDO IN(?) query
     *
     * @param array     $values   A database resource for Drupal DBAL
     *
     * @return array|string Formatted values for PDO query
     */
    abstract protected function formatValues(array $values = []);

    /**
     * Returns a count of available nodes based on passed types.
     *
     * @param array     $types   Types to use in query
     *
     * @return int
     */
    abstract protected function countNodes(array $types = []);

    /**
     * Pages through nodes based on types, limit, skip.
     *
     * @param array     $types   Types to use in query
     * @param int       $limit   # results to return
     * @param int       $skip    # results to start from
     *
     * @return int
     */
    abstract protected function queryNodes(array $types = [], $limit = 100, $skip = 0);


    protected function loadUsers()
    {
        $resource = db_query('select uid from {users} order by uid asc');
        $users = $this->getObjects($resource, 'user');
        return $users;
    }

    protected function loadNodes()
    {
        $resource = db_query('select nid from {node} order by nid asc');
        $nodes = $this->getObjects($resource, 'node');
        return $nodes;
    }

    protected function loadWebsiteSectionNodes()
    {

    }

    protected function convertTaxonomy(&$node)
    {
        $taxonomy = [];
        if (null === $node->taxonomy) {
            unset($node->taxonomy);
            return;
        }

        foreach ($node->taxonomy as $tax) {
            $v = taxonomy_vocabulary_load($tax->vid);
            $tid = (int) $tax->tid;

            if (null !== ($type = (isset($this->map['Taxonomy'][$v->name])) ? $this->map['Taxonomy'][$v->name] : null)) {
                $type = str_replace('Taxonomy\\', '', $type);
                $taxonomy[] = [
                    'id'    => $tid,
                    'type'  => $type
                ];
            }

        }
        $node->taxonomy = $taxonomy;

        if (isset($node->field_special_focus)) {
            // Handle tagging/primary tag/primary section nonsense
        }
    }

    protected function convertScheduling(&$node)
    {
        // Convert to Magazine scheduling
        if (isset($node->field_issue)) {
            foreach ($node->field_issue as $link) {
                if (array_key_exists('value', $link) && null == $link['value']) {
                    continue;
                }
                $value = (isset($link['nid'])) ? $link['nid'] : $link['value'];
                if ($value == 0) {
                    continue;
                }
                $issue = node_load($value);
                $this->addMagazineSchedule($node, $issue);
            }
        }
        unset($node->field_issue);
    }

    protected function addMagazineSchedule($node, $issue)
    {
        $collection = $this->database->selectCollection('ScheduleMagazine');
        $type = (isset($this->map['Content'][$node->type])) ? $this->map['Content'][$node->type] : null;
        $type = str_replace('Website\\Content\\', '', $type);
        $kv = [
            'content'   => [
                '$id'   => (int) $node->nid,
                'type'  => $type
            ],
            'issue'     => (int) $issue->nid,
            'section'   => null
        ];
        $collection->insert($kv);
    }

    protected function convertFields(&$node)
    {
        $nid = (int) $node->nid;
        if (null !== ($type = (isset($this->map['Content'][$node->type])) ? $this->map['Content'][$node->type] : null)) {
            $node->_id = $nid;
            unset($node->nid);
            unset($node->type);
            $node->type = str_replace('Website\\Content\\', '', $type);

            $node->name = $node->title;
            unset($node->title);

            $node->status = (int) $node->status;

            $node->createdBy = $node->updatedBy = (int) $node->uid;
            unset($node->uid);

            $node->created = (int) $node->created;
            $node->published = $node->updated = (int) $node->changed;
            unset($node->changed);

            $node->mutations = [];

            if (isset($node->path)) {
                $node->mutations['Website']['aliases'][] = $node->path;
                unset($node->path);
            }

            if (isset($node->field_byline)) {
                $values = $node->field_byline;
                foreach ($values as $key => $value) {
                    if (isset($value['value']) && $value['value'] == null) {
                        continue;
                    }
                    $node->byline = $value['value'];
                }
                unset($node->field_byline);
            }

            if (isset($node->field_deck)) {
                $values = $node->field_deck;
                foreach ($values as $key => $value) {
                    if (isset($value['value']) && $value['value'] == null) {
                        continue;
                    }
                    $node->mutations['Magazine']['deck'] = $value['value'];
                }
                unset($node->field_deck);
            }

            if (isset($node->field_special_focus)) {
                foreach ($node->field_special_focus as $link) {
                    if (array_key_exists('value', $link) && null == $link['value']) {
                        continue;
                    }

                    $node->specialFocus = $link['value'];
                    break;
                }
            }
            unset($node->field_special_focus);

            $this->convertFeedData($node);
            $this->buildRelationships($node);
            $this->removeCrapFields($node);

        }
    }

    protected function convertFeedData(&$node)
    {
        if (isset($node->feedapi_node)) {
            $node->feedData = [
                'source'    => $node->feedapi_node->url,
                'published' => (int) $node->feedapi_node->timestamp
            ];
        }
        unset($node->feedapi_node);
    }

    protected function buildRelationships(&$node)
    {
        // Handle 'picture' field
        if (!empty($node->picture)) {
            var_dump($node->picture);
            die();
        }
        unset($node->picture);

        // Handle 'files' field
        if (!empty($node->files)) {
            var_dump($node->files);
            die();
        }
        unset($node->files);

        if (isset($node->field_image)) {
            $images = $node->field_image;
            foreach ($images as $key => $value) {
                if (isset($value['value']) && $value['value'] == null) {
                    continue;
                }
                $ref = [
                    'id'    => (int) $value['fid'],
                    'type'  => 'Image'
                ];
                $node->relatedMedia[] = $ref;

                $caption = null;
                if (isset($node->field_image_caption)) {
                    $val = reset($node->field_image_caption);
                    $caption = $val['value'];
                    unset($node->field_image_caption);
                }

                $this->createImage($value, $caption);

                if (!isset($node->primaryImage)) {
                    $node->primaryImage = $ref['id'];
                }
            }
        }
        unset($node->field_image);
    }

    protected function createImage(array $img, $caption = null)
    {
        if ((int) $img['fid'] === 0) {
            return;
        }
        $collection = $this->database->selectCollection('Image');
        $kv = [
            '_id'       => (int) $img['fid'],
            'fileName'  => $img['filename'],
            'filePath'  => $img['filepath'],
            'createdBy' => (int) $img['uid'],
            'created'   => date('c', $img['timestamp']),
            'type'      => 'Image'
        ];
        if (null !== $caption) {
            $kv['caption'] = $caption;
        }
        $collection->insert($kv);
    }

    protected function removeCrapFields(&$node)
    {
        unset($node->language, $node->comment, $node->promote, $node->moderate, $node->sticky, $node->tnid);
        unset($node->translate, $node->format, $node->revision_timestamp, $node->log, $node->feed);
        unset($node->last_comment_timestamp, $node->last_comment_name, $node->comment_count, $node->field_priority);
        unset($node->revision_uid, $node->data, $node->vid, $node->field_legacy_id);
    }

    protected function importTaxonomy($vocab)
    {
        $collection = $this->database->selectCollection('Taxonomy');
        $terms = taxonomy_get_tree($vocab->vid);
        $type = str_replace('Taxonomy\\', '', $this->map['Taxonomy'][$vocab->name]);
        $formatted = [];
        foreach ($terms as $term) {
            if ((int) $term->tid === 0) {
                continue;
            }
            $formatted[] = [
                '_id'   => (int) $term->tid,
                'name'  => $term->name,
                'description'   => $term->description,
                'type'  => $type
            ];
        }

        if (!empty($formatted)) {
            $this->writeln(sprintf('Vocabulary: Inserting %s %s terms.', count($formatted), $type));
            $collection->batchInsert($formatted);
        }
    }

    protected function getTypes()
    {
        $query = sprintf('select DISTINCT(type) from {node}');
        $resource = db_query($query);

        $types = [];
        $rows = $this->getRaw($resource);
        foreach ($rows as $type) {
            $types[] = $type->type;
        }
        return $types;
    }

    protected function importWebsiteSectionNodes()
    {
        if (!isset($this->map['Section']) || empty($this->map['Section'])) {
            $this->writeln(sprintf('You must set the section map for %s:', $this->key), false, true);
            $types = $this->getTypes();
            $this->writeln(sprintf('Valid types: %s', implode(', ', $types)), true, true);
            die();
        }

        $collection = $this->database->selectCollection('Section');
        $types = array_keys($this->map['Section']);

        $count = $total = (int) $this->countNodes($types);

        $this->writeln(sprintf('Nodes: Importing %s Website Sections.', $count));

        $nodes = $this->queryNodes($types);

        $formatted = [];
        foreach ($nodes as $node) {
            $formatted[] = [
                '_id'           => (int) $node->nid,
                'name'          => $node->title,
                'description'   => $n
            ];
        }

        if (!empty($formatted)) {
            $this->writeln(sprintf('Nodes: Inserting %s Website Sections.', count($formatted)));
            $collection->batchInsert($formatted);
        }
    }

    protected function importMagazineIssueNodes()
    {
        if (!isset($this->map['Issue']) || empty($this->map['Issue'])) {
            $this->writeln(sprintf('You must set the issue map for %s:', $this->key), false, true);
            $types = $this->getTypes();
            $this->writeln(sprintf('Valid types: %s', implode(', ', $types)), true, true);
            die();
        }

        $collection = $this->database->selectCollection('Issue');
        $types = array_keys($this->map['Issue']);

        $count = $total = (int) $this->countNodes($types);

        $this->writeln(sprintf('Nodes: Importing %s Magazine Issues.', $count));

        $nodes = $this->queryNodes($types);
        $tz = new DateTimeZone('America/Chicago');

        $formatted = [];
        foreach ($nodes as $node) {
            $mailDate = new DateTime(date('c', strtotime($node->title)), $tz);
            $shortName = strtolower($mailDate->format('My'));

            $kv = [
                '_id'               => (int) $node->nid,
                'name'              => $node->title,
                // 'description'       => $node->body,
                'mailDate'          => $mailDate->format('c'),
                'status'            => (int) $node->status,
                'legacy'            => [
                    'shortName'     => $shortName
                ]
            ];

            if (isset($node->field_nxtbook_link)) {
                foreach ($node->field_nxtbook_link as $link) {
                    if (array_key_exists('value', $link) && null === $link['value']) {
                        continue;
                    }
                    $kv['digitalEditionUrl'] = rtrim($link['value']);
                }
            }

            if (isset($node->field_issue_link)) {
                foreach ($node->field_issue_link as $link) {
                    if (array_key_exists('value', $link) && null === $link['value']) {
                        continue;
                    }
                    $kv['digitalEditionUrl'] = rtrim($link['value']);
                }
            }

            $formatted[] = $kv;
        }

        if (!empty($formatted)) {
            $this->writeln(sprintf('Nodes: Inserting %s Magazine Issues.', count($formatted)));
            $collection->batchInsert($formatted);
        }
    }

    protected function importContentNodes($limit = 100)
    {
        if (!isset($this->map['Content']) || empty($this->map['Content'])) {
            $this->writeln(sprintf('You must set the content map for %s:', $this->key), false, true);
            $types = $this->getTypes();
            $this->writeln(sprintf('Valid types: %s', implode(', ', $types)), true, true);
            die();
        }

        $collection = $this->database->selectCollection('Content');
        $types = array_keys($this->map['Content']);

        $count = $total = (int) $this->countNodes($types);

        $this->writeln(sprintf('Nodes: Importing %s documents.', $count));

        $n = $inserted = 0;

        while ($count >= 0) {
            $skip = $limit * $n;
            $nodes = $this->queryNodes($types, $limit, $skip);
            $formatted = [];
            foreach ($nodes as &$node) {
                if (0 === $node->nid) {
                    continue;
                }

                $this->convertTaxonomy($node);
                $this->convertScheduling($node);
                $this->convertFields($node);

                $formatted[] = json_decode(json_encode($node), true);
            }

            $inserted += count($formatted);
            $percentage = ($total == 0) ? 0 : round($inserted / $total * 100, 2);

            if (!empty($formatted)) {
                $collection->batchInsert($formatted);
            }

            $this->writeln(sprintf('Nodes: Inserted %s/%s documents (%s%%).', str_pad($inserted, strlen($total), ' ', STR_PAD_LEFT), $total, $percentage));
            $n++;
            $count -= $limit;
        }
    }
}
