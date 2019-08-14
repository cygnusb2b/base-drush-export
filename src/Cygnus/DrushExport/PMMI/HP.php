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
            'database'  => 'drupal_pmmi_hp',
            'uri'       => 'https://www.healthcarepackaging.com',
            'Taxonomy'  => [
                // Static lists (prefix with key aka `Column Type: Feed Forward`)
                'Company Type'                  => 'Taxonomy\Bin', //
                'Contract Packaging'            => 'Taxonomy\Bin', //
                'Line speed'                    => 'Taxonomy\Bin', //
                'Machine attributes'            => 'Taxonomy\Bin', //
                'Expert - Areas of Expertise'   => 'Taxonomy\Bin', //
                'Leadership Session'            => 'Taxonomy\Bin', //
                'Premier Categories'            => 'Taxonomy\Bin', //
                'Media Property'                => 'Taxonomy\Bin', //
                'Package design'                => 'Taxonomy\Bin', //
                'Source type'                   => 'Taxonomy\Bin', //
                'Subtype'                       => 'Taxonomy\Bin', //
                'Sustainability'                => 'Taxonomy\Bin', //
                'Venue'                         => 'Taxonomy\Bin', //
                'Logistics'                     => 'Taxonomy\Bin', //

                // Tags
                'Tags'                          => 'Taxonomy\Tag', //

                // Hierarchical
                'Applications'                  => 'Taxonomy\Category', //
                'Trends and Issues'             => 'Taxonomy\Topic', //
                'Coverage type'                 => 'Taxonomy\Coverage', // from pmmi/aw

                // New Hierarchy
                'Controls'                      => 'Taxonomy\Controls', //
                'Machinery'                     => 'Taxonomy\Machinery', //
                'Material Type'                 => 'Taxonomy\Material', //
                'Package Component'             => 'Taxonomy\PackageComponent', //
                'Package Feature'               => 'Taxonomy\PackageFeature', //
                'Package Type'                  => 'Taxonomy\PackageType', //

                // Unused
                // 'DFP Ad Categories'             => '', //

            ],
            'Content'   => [
            ],
            'Section'   => [],
            'Issue'     => [
                'magazine' => 'Magazine\\Issue',
            ],
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
