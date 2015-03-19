<?php

namespace Cygnus\DrushExport;

/**
 * Provides support for exporting from Drupal 7
 */
class ExportD7 extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'arkansas'      => [
            'database'          => 'import_mni_arkansas'
        ],
        'easttn'        => [
            'database'          => 'import_mni_easttn'
        ],
        'mississippi'   => [
            'database'          => 'import_mni_mississippi'
        ],
        'orlando'       => [
            'Taxonomy'  => [
                'Focus Sections'    => 'Taxonomy\\Category',
                'Tags'              => 'Taxonomy\\Tag',
                'Section'           => 'Taxonomy\\Section',
            ],
            'Content'   => [
                'story'         => 'Website\\Content\\Article',
                'manatee_story' => 'Website\\Content\\Article',
                'feed_content'  => 'Website\\Content\\News',
                // 'feed'          => 'Website\\Content\\News',
            ],
            'Section'   => [
                'page'          => 'Website\\Section',
            ],
            'Issue'     => [
                'issue'         => 'Magazine\\Issue',
                'manatee_issue' => 'Magazine\\Issue',
            ],
            'database'          => 'import_mni_orlando'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function getObjects($resource, $type = 'node')
    {
        $results = [];
        foreach ($resource as $row) {
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
        return $resource->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function formatValues(array $types = [])
    {
        return $types;
    }

    /**
     * {@inheritdoc}
     */
    protected function countNodes(array $types = [])
    {
        $types = $this->formatValues($types);
        $inQuery = implode(',', array_fill(0, count($types), '?'));
        $query = 'SELECT count(*) as count from {node} where type in ('.$inQuery.')';
        $resource = db_query($query, $types);
        $count = reset($this->getRaw($resource));
        return (int) $count->count;
    }

    /**
     * {@inheritdoc}
     */
    protected function queryNodes(array $types = [], $limit = 100, $skip = 0)
    {
        $types = $this->formatValues($types);
        $inQuery = implode(',', array_fill(0, count($types), '?'));
        $query = 'SELECT nid, type from {node} where type in ('.$inQuery.') ORDER BY nid asc';
        $resource = db_query_range($query, $skip, $limit, $types);
        $nodes = $this->getObjects($resource, 'node');
        // $this->writeln(sprintf('DEBUG: `%s` with `%s` returned %s results.', $query, $types, count($nodes)));
        return $nodes;
    }
}
