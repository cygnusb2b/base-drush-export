<?php

namespace Cygnus\DrushExport\PMMI;

/**
 * Healthcare Packaging customizations
 *
 */
class PFW extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'pfw'  => [
            'name'      => 'ProFood World',
            'database'  => 'drupal_pmmi_pfw',
            'uri'       => 'https://www.profoodworld.com',
            'Issue'     => [
                'magazine' => 'Magazine\\Issue',
            ],
            'Taxonomy'  => [
                // Shared/common
                'Company type'                  => 'Taxonomy\Bin',
                'Media Property'                => 'Taxonomy\Bin',
                'Source type'                   => 'Taxonomy\Bin',
                'Subtype'                       => 'Taxonomy\Bin',
                'Coverage type'                 => 'Taxonomy\Coverage',
                'Tags'                          => 'Taxonomy\Tag',
                'Machinery'                     => 'Taxonomy\Machinery',
                'Materials'                     => 'Taxonomy\Material',
                // PFW-specific
                'Department'                    => 'Taxonomy\Bin',
                'Article subtype'               => 'Taxonomy\Bin',
                'Leadership Categories'         => 'Taxonomy\Bin',
                'Leadership Session'            => 'Taxonomy\Bin',
                'Global Company Brands'         => 'Taxonomy\Bin',
                'Global Company Industries'     => 'Taxonomy\Bin',
                'Advertiser product'            => 'Taxonomy\Category',
                'Topic'                         => 'Taxonomy\Topic',
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
