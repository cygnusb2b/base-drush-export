<?php

namespace Cygnus\DrushExport;

/**
 * Provides support for exporting from Drupal 6
 */
class ExportD6 extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'stlouis'       => [
            'Taxonomy'  => [
                'Focus Sections'    => 'Taxonomy\\Category',
                'Tags'              => 'Taxonomy\\Tag',
                'Publication'       => 'Taxonomy\\Publication',
                'Section'           => 'Taxonomy\\Section',
                'Form Group'        => 'Taxonomy\\FormGroup'
            ],
            'Content'   => [
                'story'         => 'Website\\Content\\Article',
                'manatee_story' => 'Website\\Content\\Article',
                'feed_content'  => 'Website\\Content\\News',
                'feed'          => 'Website\\Content\\News',
            ],
            'Section'   => [
                'page'          => 'Website\\Section',
            ],
            'Issue'     => [
                'issue'         => 'Magazine\\Issue',
                'manatee_issue' => 'Magazine\\Issue',
            ],
            'publication'       => 'stlouis',
            'database'          => 'import_mni_stlouis',
            'host'              => 'saintlouismedicalnews.com'
        ],
        'tampabay'      => [
            'Taxonomy'  => [
                'Focus Sections'    => 'Taxonomy\\Category',
                'Tags'              => 'Taxonomy\\Tag',
                'Publication'       => 'Taxonomy\\Publication',
                'Section'           => 'Taxonomy\\Section',
                'Form Group'        => 'Taxonomy\\FormGroup'
            ],
            'Content'   => [
                'story'         => 'Website\\Content\\Article',
                'manatee_story' => 'Website\\Content\\Article',
                'feed_content'  => 'Website\\Content\\News',
                'feed'          => 'Website\\Content\\News',
            ],
            'Section'   => [
                'page'          => 'Website\\Section',
            ],
            'Issue'     => [
                'issue'         => 'Magazine\\Issue',
                'manatee_issue' => 'Magazine\\Issue',
            ],
            'database'          => 'import_mni_tampabay',
            'host'              => 'tampabaymedicalnews.com'
        ],
         'nashvillepost'      => [
            'Taxonomy'  => [
                'Companies'         => 'Taxonomy\\Organization', // no company, use organization or override?
                'County'            => 'Taxonomy\\Region',  // Location data but much more specific than locations data - use in region or put into Location with other data?
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

    /**
     * {@inheritdoc}
     */
    protected function getObjects($resource, $type = 'node')
    {
        $results = [];
        while ($row = db_fetch_object($resource)) {
            switch ($type) {
                case 'user':
                    $object = user_load($row->uid);
                    break;

                default:
                    $object = node_load($row->nid);
                    break;
            }
            if (is_object($object)) {
                $results[] = $object;
            }
        }
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRaw($resource)
    {
        $results = [];
        while ($row = db_fetch_object($resource)) {
            $results[] = $row;
        }
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatValues(array $types = [])
    {
        $q = "";
        foreach ($types as &$type) {
            $type = sprintf('"%s"', $type);
        }
        return implode(',', $types);
    }

    /**
     * {@inheritdoc}
     */
    protected function countNodes(array $types = [])
    {
        $types = $this->formatValues($types);
        if (!function_exists('db_fetch_object')) {
            $inQuery = implode(',', array_fill(0, count($types), '?'));
            $query = 'SELECT count(*) as count from {node} where type in ('.$inQuery.')';
            $resource = db_query($query, $types);
        } else {
            $query = sprintf('select count(*) as count from {node} where type in (%s)', $types);
            $resource = db_query($query);
        }
        $count = reset($this->getRaw($resource));
        return (int) $count->count;
    }

    /**
     * {@inheritdoc}
     */
    protected function queryNodes(array $types = [], $limit = 100, $skip = 0)
    {
        $types = $this->formatValues($types);
        if (!function_exists('db_fetch_object')) {
            $inQuery = implode(',', array_fill(0, count($types), '?'));
            $query = 'SELECT nid, type from {node} where type in ('.$inQuery.') ORDER BY nid asc';
            $resource = db_query_range($query, $skip, $limit, $types);
        } else {
            $query = sprintf('select nid, type from {node} where type in (%s) order by nid asc', $types);
            $resource = db_query_range($query, $skip, $limit);
        }

        $nodes = $this->getObjects($resource, 'node');
        // $this->writeln(sprintf('DEBUG: `%s` with `%s` returned %s results.', $query, $types, count($nodes)));
        return $nodes;
    }

    /**
     * {@inheritdoc}
     */
    protected function createImage(array $img, $caption = null)
    {
        if ((int) $img['fid'] === 0) {
            return;
        }

        $filePath = sprintf('http://%s/%s', $this->getHost(), $img['filepath']);

        $collection = $this->database->selectCollection('Image');
        $kv = [
            '_id'       => (int) $img['fid'],
            'fileName'  => $img['filename'],
            'filePath'  => $filePath,
            'createdBy' => (int) $img['uid'],
            'created'   => date('c', $img['timestamp']),
            'type'      => 'Image'
        ];
        if (null !== $caption) {
            $kv['caption'] = $caption;
        }

        // only found one so far - fix in source data, leave this, or check inside insert method
        try {
            $collection->insert($kv);
        } catch (\MongoDuplicateKeyException $e) {
            $kv['_id'] = $kv['_id'].'_'.microtime(true);
            $kv['dupe'] = TRUE;
            $collection->insert($kv);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createDocument(array $file)
    {

        $filePath = sprintf('http://%s/%s', $this->getHost(), $file['filepath']);

        $collection = $this->database->selectCollection('Document');
        $kv = [
            '_id'       => (int) $file['fid'],
            'fileName'  => $file['filename'],
            'filePath'  => $filePath,
            'type'      => 'Document'
        ];
        if (null !== $caption) {
            $kv['caption'] = $caption;
        }

        try {
            $collection->insert($kv);
        } catch (\MongoDuplicateKeyException $e) {
            $kv['_id'] = $kv['_id'].'_'.microtime(true);
            $kv['dupe'] = TRUE;
            $collection->insert($kv);
        }

    }

}
