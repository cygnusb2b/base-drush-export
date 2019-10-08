<?php

namespace Cygnus\DrushExport\PMMI;

/**
 * Healthcare Packaging customizations
 *
 */
class LSL extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'lsl'  => [
            'name'      => 'Logistics for the Life Sciences',
            'database'  => 'drupal_pmmi_hp',
            'uri'       => 'https://www.logisticsforthelifesciences.com',
            'Issue'     => [
                'magazine_covers' => 'Magazine\\Issue',
            ],
            'Taxonomy'  => [
                'dont'  => 'map',
                // // Shared/common
                // 'Company Type'                  => 'Taxonomy\Bin',
                // 'Media Property'                => 'Taxonomy\Bin',
                // 'Source type'                   => 'Taxonomy\Bin',
                // 'Subtype'                       => 'Taxonomy\Bin',
                // 'Coverage type'                 => 'Taxonomy\Coverage',
                // // 'Tags'                          => 'Taxonomy\Tag',
                // 'Machinery'                     => 'Taxonomy\Machinery',
                // 'Material Type'                 => 'Taxonomy\Material',
                // 'Controls'                      => 'Taxonomy\Controls',
                // 'Package Component'             => 'Taxonomy\PackageComponent',
                // 'Package Feature'               => 'Taxonomy\PackageFeature',
                // 'Package Type'                  => 'Taxonomy\PackageType',
                // // PW-specific
                // 'Contract packaging'            => 'Taxonomy\Bin',
                // 'Expo Pack Session'             => 'Taxonomy\Bin',
                // 'Gallery Features'              => 'Taxonomy\Bin',
                // 'Gallery Industry'              => 'Taxonomy\Bin',
                // 'Gallery Type'                  => 'Taxonomy\Bin',
                // 'Leadership Main Categories'    => 'Taxonomy\Bin',
                // 'Leadership Session'            => 'Taxonomy\Bin',
                // 'Line speed'                    => 'Taxonomy\Bin',
                // 'Machine attributes'            => 'Taxonomy\Bin',
                // 'Package design'                => 'Taxonomy\Bin',
                // 'Sustainability'                => 'Taxonomy\Bin',
                // 'Venue'                         => 'Taxonomy\Bin',
                // 'Applications'                  => 'Taxonomy\Category',
                // 'Expo Pack Showcase Categories' => 'Taxonomy\ExpoPackShowcaseCategory',
                // 'Leadership Categories'         => 'Taxonomy\LeadershipCategory',
                // 'Trends and Issues'             => 'Taxonomy\Topic',
            ],
            'Content'   => [
                'article'                   => 'Website\Content\Article',
                'company'                   => 'Website\Content\Company',
                'registration_form'         => 'Website\Content\Document',
                'video'                     => 'Website\Content\Video',
                'white_paper'               => 'Website\Content\Whitepaper',
                'page'                      => 'Website\Content\Page',
                'playbook'                  => 'Website\Content\Document',
                'podcast'                   => 'Website\Content\Podcast',
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
                'term_fields'  => [],
                'map'   => []
            ]
        ],
    ];
}
