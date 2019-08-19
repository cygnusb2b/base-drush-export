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
            'Content'   => [
                '360_package_spin_rotate'   => 'Website\Content\Product',
                'article'                   => 'Website\Content\Article',
                'page'                      => 'Website\Content\Page',
                'company'                   => 'Website\Content\Company',
                'registration_form'         => 'Website\Content\Document',
                'video'                     => 'Website\Content\Video',
                'whitepaper'                => 'Website\Content\Whitepaper',
                // 'leadership_data_card'
                // 'leadership_online_profile'
                // 'leadership_print_profile'
                // 'calendar'               // One item, test event, not enabled in drupal UI
                // 'featured_article'       // One item, user-submitted photo gallery from 2016, not published
                // 'magazine'               // Subset of issues, no additional data
            ],
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
