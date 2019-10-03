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
                'stripFields'   => [
                    // These fields will be removed from nodes before being written to mongo
                    'vid',
                    'log',
                    'uuid',
                    'tnid',
                    'cid',                          // comment id
                    'translate',
                    'print_html_display',
                    'print_html_display_comment',
                    'print_html_display_urllist',
                    'last_comment_uid',
                    'last_comment_name',
                    'last_comment_timestamp',
                    'revision_timestamp',           // looks to be same as 'changed' which we are using
                    'revision_uid',
                    'language',                     // language value, (und default)
                    'comment',
                    'comment_count',
                    'promote',
                    'sticky',
                    'vuuid',
                    'rdf_mapping',
                    'path',
                    'metatags',
                    'data',
                    'picture',
                    //'premium_content','comment_count','picture','data'  // other fields I see but leaving for now
                    //'moderate','format','feed','field_priority','field_leagcy_id','rdf_mapping','metatags','field_legacy_article_id');  // fields in old code, but not seen so far in hci
                    'field_360_multi_upload',
                    'field_360_images_reverse',
                    'field_360_magic_plugin',
                    'field_360_magic_plugin_columns',
                    'field_360_magic_plugin_rows',
                    'field_360_images',
                    'field_360_fc_large',
                    'field_gallery_360_field_location',
                    'field_allterms',   // @todo handle in taxonomy segment
                    'field_related',
                    'field_pop_up_registration',
                    'field_joomla_id',
                    'field_accela_id',
                    'field_duplicate',
                    'field_accelaworks_billcode',
                    'field_article_length',
                    'field_app_sponsor_news',
                    'field_brand_override',
                    'field_master_accelaworks_id',
                    'field_master_leadworks_id',
                    'field_form_header',

                    'field_post_launch_banner',
                    'field_pre_launch_banner',
                    'field_promoted',
                    'field_show_site_logo',
                    'field_silverpop_program_id',
                    'field_smg_pop_viddler',
                    'field_social_media_watch',
                    'field_sponsor_links',
                    'field_sponsor_logo',
                    'field_stage_one_form',
                    'field_vocab_primary_industry',
                    'field_vocab_primary',

                    'field_waywire_playlist_id',
                    'field_waywire_tag',
                    'field_waywire_video',
                    'field_webtracking_name',
                    'field_updated_on',
                    'field_top_copy',
                    'field_legacy',
                    'field_crosspost_to_pfw',
                    'field_reporting_from',
                    'webform',
                    'field_sales_reps', // no data

                    // Fields that may be important later, but exist in `legacy.raw`
                    'field_viddler_id',
                    'field_sponsor_expiration',
                    'field_omeda_behavior_ids',
                    'field_omeda_deployment_type_ids',
                    'field_omeda_download_attr_id',
                    'field_omeda_product_ids',
                    'field_omeda_promo_code',
                    'field_pull_quote',
                    'field_pullquote',
                    'field_top_copy',
                    'field_sub_title',
                    'field_app_more_information_link',
                    'field_application_case_history',
                    'field_platforms_os',
                    'field_video_resources', // Exist only on apps, part of the custom data presentation

                    'field_issue_date', // Issue date + page num appear to be references to print scheduling
                    'field_page_num',   // but don't contain enough data to map.


                    // 'field_wir_sponsor',
                    'field_youtube_uploads_id',
                    // 'field_youtube_username',
                    'field_youtube_username_override',

                    // Renamed fields, remove to cleanup
                    'nid',
                    'uid',
                    'title',
                    'field_deckhead',
                    'field_image',
                    'field_article_images',
                    'changed',
                    'field_byline',
                    'field_author_title',
                    // 'field_sub_podcasts',
                    // 'field_whitepaper',
                    // 'field_white_paper',     // Exist on Videos, contain youtube links??
                ],
                '_id'       => 'nid',
                'name'      => 'title',
                'status'    => 'status',
                'createdBy' => 'uid',
                'updatedBy' => 'revision_uid',
                'created'   => 'created',
                'updated'   => 'changed',
                'published' => 'created',
            ],
            'leadership'    => [
                'term_fields'  => [
                    'categories'
                ],
                'map'   => [
                    '32_6619' => 33365,
                    '32_6620' => 33364,
                    // '32_7094' => ,
                    '32_6618' => 33363,
                    '32_6617' => 33362,
                    '32_6616' => 33361,
                    '32_6611' => 33354,
                    '32_6610' => 33353,
                    '32_6609' => 33352,
                    '32_6608' => 33351,
                    '32_6802' => 33355,
                    '32_6612' => 33356,
                    '32_6615' => 33360,
                    '32_6614' => 33359,
                    '32_6613' => 33358,
                    '32_6606' => 33357,
                    '32_6607' => 33350,
                ]
            ]
        ],
    ];
}
