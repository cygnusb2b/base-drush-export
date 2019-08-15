<?php

namespace Cygnus\DrushExport\PMMI;

/**
 * Healthcare Packaging customizations
 *
 */
class OEM extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'oem'  => [
            'name'      => 'OEM Magazine',
            'database'  => 'drupal_pmmi_oem',
            'uri'       => 'https://www.oemmagazine.org',
            'Issue'     => [
                'magazine' => 'Magazine\\Issue',
            ],
            'Taxonomy'  => [
                // Shared/common
                'Company Type'                  => 'Taxonomy\Bin',
                'Media Property'                => 'Taxonomy\Bin',
                'Source'                        => 'Taxonomy\Bin',
                'Subtype'                       => 'Taxonomy\Bin',
                'Coverage Type'                 => 'Taxonomy\Coverage',
                'Tags'                          => 'Taxonomy\Tag',
                // OEM-specific
                'Technology Topics'             => 'Taxonomy\Bin',
                'OEM Topics'                    => 'Taxonomy\Bin',
                'Sub-assembly'                  => 'Taxonomy\Bin',
                'Components'                    => 'Taxonomy\Bin',
                'Machine Type'                  => 'Taxonomy\Bin',
                'Products'                      => 'Taxonomy\Category',
                'Trend Setter Session'          => 'Taxonomy\Topic',
            ],
            'Content'   => [],
            'Section'   => [],
            'structure' =>  [
                'stripFields'   => [],
                '_id'       => 'nid',
                'name'      => 'title',
                'status'    => 'status',
                'createdBy' => 'uid',
                'updatedBy' => 'revision_uid',
                'created'   => 'created',
                'updated'   => 'changed',
                'published' => 'created',
            ],
        ],
    ];
}
