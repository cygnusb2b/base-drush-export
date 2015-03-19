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
            'database'          => 'import_mni_stlouis'
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
            'database'          => 'import_mni_tampabay'
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
}
