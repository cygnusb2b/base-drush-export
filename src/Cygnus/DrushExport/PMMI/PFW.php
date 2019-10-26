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
            'Content'   => [
                'article'                   => 'Website\Content\Article',
                'page'                      => 'Website\Content\Page',
                'company'                   => 'Website\Content\Company',
                'registration_form'         => 'Website\Content\Document',
                'video'                     => 'Website\Content\Video',
                'webinar_registration'      => 'Website\Content\Webinar',
                'playbook'                  => 'Website\Content\Document',
                'person'                    => 'Website\Content\Contact',

                'bi_library_article'        => 'Website\Content\Document',  // Gated download+form
                // 'bi_library_form',
                'event'                     => 'Website\Content\Event',
                // 'leadership_data_card',
                // 'leadership_online_profile',
                // 'leadership_print_profile',
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
                    'categories',
                ],
                'map'   => [
                    '29_2218' => '33436',
                    '29_1163' => '33437',
                    '29_1196' => '33438',
                    '29_1179' => '33439',
                    '29_1161' => '33440',
                    // '29_3826' => '',
                    '29_1164' => '33441',
                    '29_1187' => '33442',
                    '29_1165' => '33443',
                    // '29_2219' => '',
                    '29_2215' => '33444',
                    '29_1167' => '33445',
                    '29_1168' => '33446',
                    // '29_1205' => '',
                    '29_1169' => '33447',
                    '29_1170' => '33448',
                    '29_1171' => '33449',
                    '29_1199' => '33450',
                    '29_1172' => '33451',
                    '29_1173' => '33452',
                    // '29_2213' => '',
                    '29_2212' => '33453',
                    '29_1197' => '33454',
                    '29_1174' => '33455',
                    '29_2211' => '33456',
                    '29_1198' => '33457',
                    '29_1175' => '33458',
                    '29_1180' => '33459',
                    '29_1176' => '33460',
                    '29_1177' => '33461',
                    '29_1208' => '33462',
                    '29_3796' => '33463',
                    '29_1178' => '33464',
                    '29_1181' => '33465',
                    '29_1182' => '33466',
                    '29_1192' => '33467',
                    '29_1183' => '33468',
                    '29_1184' => '33469',
                    '29_3801' => '35141',
                    '29_2207' => '33470',
                    '29_1195' => '33471',
                    '29_1186' => '33472',
                    '29_1204' => '35142',
                    '29_2214' => '33473',
                    '29_2217' => '33474',
                    '29_2209' => '33475',
                ]
            ]
        ],
    ];
}
