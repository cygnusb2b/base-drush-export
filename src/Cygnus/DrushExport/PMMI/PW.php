<?php

namespace Cygnus\DrushExport\PMMI;

/**
 * Healthcare Packaging customizations
 *
 */
class PW extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'pw'  => [
            'name'      => 'Packaging World',
            'database'  => 'drupal_pmmi_pw',
            'uri'       => 'https://www.packworld.com',
            'Issue'     => [
                'magazine_covers' => 'Magazine\\Issue',
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
                // PW-specific
                'Contract Packaging'            => 'Taxonomy\Bin',
                'Expo Pack Session'             => 'Taxonomy\Bin',
                'Gallery Features'              => 'Taxonomy\Bin',
                'Gallery Industry'              => 'Taxonomy\Bin',
                'Gallery Type'                  => 'Taxonomy\Bin',
                'Leadership Main Categories'    => 'Taxonomy\Bin',
                'Leadership Session'            => 'Taxonomy\Bin',
                'Line speed'                    => 'Taxonomy\Bin',
                'Machine attributes'            => 'Taxonomy\Bin',
                'Package design'                => 'Taxonomy\Bin',
                'Sustainability'                => 'Taxonomy\Bin',
                'Venue'                         => 'Taxonomy\Bin',
                'Applications'                  => 'Taxonomy\Category',
                'Expo Pack Showcase Categories' => 'Taxonomy\ExpoPackShowcaseCategory',
                'Leadership Categories'         => 'Taxonomy\LeadershipCategory',
                'Trends and Issues'             => 'Taxonomy\Topic',
            ],
            'Content'   => [
                '360_package_spin_rotate'   => 'Website\Content\Product',
                'article'                   => 'Website\Content\Article',
                'page'                      => 'Website\Content\Page',
                'company'                   => 'Website\Content\Company',
                'registration_form'         => 'Website\Content\Document',
                'video'                     => 'Website\Content\Video',
                'webinar_registration'      => 'Website\Content\Webinar',
                'whitepaper'                => 'Website\Content\Whitepaper',
                'playbook'                  => 'Website\Content\Document',
                'podcast'                   => 'Website\Content\Podcast',
                'blog'                      => 'Website\Content\Blog',
                'mini_bant'                 => 'Website\Content\TextAd',
                'bi_library_article'        => 'Website\Content\Document',  // Gated download+form
                // 'bi_library_form',
                'webcast'                   => 'Website\Content\Webinar',
                // 'leadership_data_card',
                // 'leadership_online_profile',
                // 'leadership_print_profile',
                // 'showcase_expo_pack_profile',
                'gallery_item'              => 'Website\Content\Product',
                'photo'                     => 'Website\Content\Product',
                'slideshow'                 => 'Website\Content\MediaGallery',
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
