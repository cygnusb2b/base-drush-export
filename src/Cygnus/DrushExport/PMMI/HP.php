<?php

namespace Cygnus\DrushExport\PMMI;

/**
 * Healthcare Packaging customizations
 *
 */
class HP extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'hp'  => [
            'name'      => 'Healthcare Packaging',
            'database'  => 'drupal_pmmi_hp',
            'uri'       => 'https://www.healthcarepackaging.com',
            'Issue'     => [
                'magazine' => 'Magazine\\Issue',
            ],
            'Taxonomy'  => [
                // Shared/common
                'Company Type'                  => 'Taxonomy\Bin',
                'Media Property'                => 'Taxonomy\Bin',
                'Source type'                   => 'Taxonomy\Bin',
                'Subtype'                       => 'Taxonomy\Bin',
                'Coverage type'                 => 'Taxonomy\Coverage',
                'Tags'                          => 'Taxonomy\Tag',
                'Machinery'                     => 'Taxonomy\Machinery',
                'Material Type'                 => 'Taxonomy\Material',
                'Controls'                      => 'Taxonomy\Controls',
                'Package Component'             => 'Taxonomy\PackageComponent',
                'Package Feature'               => 'Taxonomy\PackageFeature',
                'Package Type'                  => 'Taxonomy\PackageType',
                // HP-specfific
                'Contract Packaging'            => 'Taxonomy\Bin',
                'Line speed'                    => 'Taxonomy\Bin',
                'Machine attributes'            => 'Taxonomy\Bin',
                'Expert - Areas of Expertise'   => 'Taxonomy\Bin',
                'Leadership Session'            => 'Taxonomy\Bin',
                'Premier Categories'            => 'Taxonomy\Bin',
                'Package design'                => 'Taxonomy\Bin',
                'Sustainability'                => 'Taxonomy\Bin',
                'Venue'                         => 'Taxonomy\Bin',
                'Logistics'                     => 'Taxonomy\Bin',
                'Applications'                  => 'Taxonomy\Category',
                'Trends and Issues'             => 'Taxonomy\Topic',
            ],
            'Content'   => [
                '360_package_spin_rotate'   => 'Website\Content\Product',
                'article'                   => 'Website\Content\Article',
                'page'                      => 'Website\Content\Page',
                'company'                   => 'Website\Content\Company',
                'expert'                    => 'Website\Content\Contact',
                'html_content'              => 'Website\Content\Page',
                'registration_form'         => 'Website\Content\Document',
                'video'                     => 'Website\Content\Video',
                'webinar_registration'      => 'Website\Content\Webinar',
                'white_paper'               => 'Website\Content\Whitepaper',
                'playbook'                  => 'Website\Content\Document',
                'podcast'                   => 'Website\Content\Podcast',
                // 'leadership_data_card'
                // 'leadership_online_profile'
                // 'leadership_print_profile'
                // 'premier_supplier_data_card',
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
