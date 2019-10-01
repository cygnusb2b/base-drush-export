<?php

namespace Cygnus\DrushExport\PMMI;

/**
 * Provides support for exporting from Drupal 7.5x
 *
 * Needs working (as in you can login to admin) site for drush to work
 * from drush repo root: php -d phar.readonly=0 compile
 * from drupal site root (ie /sites/heathcare-informatics/): drush scr $pathToPhar $mongoIp $importConfigKey
 * ie: drush scr ~/environment/base-drush-export-master/build/export.phar [MONGO_IP] [IMPORT_KEY]
 *
 */
class AW extends Export
{
    /**
     * {@inheritdoc}
     */
    protected $configs = [
        'aw'  => [
            'name'      => 'Automation World',
            'database'  => 'drupal_pmmi_aw',
            'uri'       => 'https://www.automationworld.com',
            'Issue'     => [
                'magazine_covers' => 'Magazine\\Issue',                         // magazine cover image, digital edition url
            ],
            'Taxonomy'  => [
                // Shared/common
                'Company Type'              => 'Taxonomy\Bin',          // ~ 5 items
                'Source Type'               => 'Taxonomy\Bin',          // 5 items, used to denote UGC and/or display concerns
                'Subtype'                   => 'Taxonomy\Bin',          // 12 items, used to determine article sub type
                'Coverage Type'             => 'Taxonomy\Coverage',     // 20+ items, Similar to website sections?
                'Tags'                      => 'Taxonomy\Tag',
                // AW-specific
                'App Platforms/OS'          => 'Taxonomy\Bin',          // ~5 items
                'Blog Beat'                 => 'Taxonomy\Bin',          // ~ 20 items
                'Column Type'               => 'Taxonomy\Bin',          // ~ 10 items
                'Download Subtype'          => 'Taxonomy\Bin',          // 2 items (Tactical Brief, Whitepaper)
                'Industry Type'             => 'Taxonomy\Bin',          // 4 items
                'Leadership Session'        => 'Taxonomy\Bin',          // 5 items, years 2015-2019
                'Automation Strategies'     => 'Taxonomy\Category',     // 100+ items
                'Topics'                    => 'Taxonomy\Topic',        // 11 items
                // New hierarchical types
                'Industries'                => 'Taxonomy\Industries',   // 20+ items (top categories?)
                'Technologies'              => 'Taxonomy\Technology',   // ~50 items, similar to `Industries` taxonomy (if kept, create Technologies instead of Market)
            ],
            'Content'   => [
                '360_package_spin_rotate'   => 'Website\Content\Product',   // Needs some custom field handling for the 3D display
                'apps'                      => 'Website\Content\Product',   // Custom field handling
                'around_the_world'          => 'Website\Content\Article',   // Around the world section/blog
                'article'                   => 'Website\Content\Article',   // Make all Articles by default
                'page'                      => 'Website\Content\Page',
                'blog'                      => 'Website\Content\Blog',
                'company'                   => 'Website\Content\Company',
                'download'                  => 'Website\Content\Document',
                // 'leadership_data_card'                                   // Additional information about companies, unsure where used.
                // 'leadership_online_profile'                              // More info,
                // 'leadership_print_profile'                               // More info, print revision??
                'mini_bant'                 => 'Website\Content\TextAd',    // Sponsored content, gated video/whitepaper landing page
                'playbook'                  => 'Website\Content\Document',  // May necessitate a custom content type, but a gated landing page for a PDF download
                'podcast'                   => 'Website\Content\Podcast',
                'registration_form'         => 'Website\Content\Document',  // Majority appear to be PDF gates (some weird ones like ENL signups)
                'video'                     => 'Website\Content\Video',
                'webinar'                   => 'Website\Content\Webinar',   // All old, currently unpublished
                'webinar_registration'      => 'Website\Content\Webinar',   // Current webinar landing form
                'week_in_review'            => 'Website\Content\News',      // News/sponsored review, "Beyond the Factory Walls" primary section??
                'whitepaper'                => 'Website\Content\Whitepaper',
            ],
            'Section'   => [
                // 'page' => 'Website\\Section',
                // 'page2' => 'Website\\Section',
            ],
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
                'updatedBy' => 'revision_uid',  // was using uid for both but I changed - ok, or less reliable?  @jpdev
                'created'   => 'created',
                'updated'   => 'changed',
                'published' => 'created',
                'mutations' => 'path',
                'type'      => 'field_term_subtype.und.0.tid',
                'body'      => 'body.und.0.value',
                'teaser'    => 'field_deckhead.und.0.value',
                // 'authors'   => 'field_byline.und.0.value',
                // 'deck'      => 'field_deck.und.0.value',
                'images'    => ['field_image.und', 'field_article_images.und'],
                // // existing fields (used mainly by top100)
                // 'phone'     => 'field_phone.und.0.value',
                // 'city'      => 'field_hci100_location.und.0.value',
                // 'website'   => 'field_website.und.0.value',
                // 'socialLinks'       => ['field_twitter.und.0.value', '	field_linkedin.und.0.value', 'field_facebook.und.0.value'],
                // //'taxonomy'  => 'taxonomy',  // moving taxonomy refs in to legacy.refs from the outset, so not needed here (unless I move convertTaxonomy code into the convertFields method, which might be good)
                // // these are for the new top100 content type
                // 'rank'              => 'field_hci100_rank.und.0.value',
                // 'previousRank'      => 'field_hci100_previous_rank.und.0.value',
                // 'founded'           => 'field_hci100_founded.und.0.value',
                // 'companyType'       => 'field_hci100_company_type.und.0.value',
                // 'employees'         => 'field_hci100_employees.und.0.value',
                // 'revenueCurrent'    => 'field_hci100_revenue_current.und.0.value',
                // 'revenuePrior1'     => 'field_hci100_revenue_prior1.und.0.value',
                // 'revenuePriorYear1' => 'field_hci100_revenue_prior1_yyyy.und.0.value',
                // 'revenuePrior2'     => 'field_hci100_revenue_prior2.und.0.value',
                // 'revenuePriorYear2' => 'field_hci100_revenue_prior2_yyyy.und.0.value',
                // 'companyExecutives' => 'field_hci100_company_executives.und.0.value',
                // 'majorRevenue'      => 'field_hci100_major_revenue.und.0.value',
                // 'productCategories' => 'field_hci100_product_categories.und.0.value',
                // 'marketsServing'    => 'field_hci100_markets_serving.und.0.value',
                // 'linkUrl'           => 'function_getRedirects',
            ],
            'leadership'    => [
                'term_fields'  => [
                    'categories_technologies'
                ],
                'map'   => [
                    '13_2685' => 33199,
                    '13_2686' => 33200,
                    '13_2687' => 33201,
                    '13_2688' => 33203,
                    '13_2755' => 33202,
                    '13_3992' => 33204,
                    '13_2689' => 33205,
                    '13_2690' => 33207,
                    '13_2691' => 33208,
                    '13_2692' => 33206,
                    '13_2693' => 33209,
                    '13_3997' => 33211,
                    '13_2694' => 33212,
                    '13_2695' => 33213,
                    '13_2696' => 33214,
                    '13_2756' => 33215,
                    '13_2697' => 33210,
                    '13_2699' => 33216,
                    '13_2700' => 33217,
                    '13_2698' => 33218,
                    '13_2701' => 33219,
                    '13_2702' => 33220,
                    '13_2703' => 33221,
                    '13_4002' => 33222,
                    '13_2704' => 33224,
                    '13_2705' => 33223,
                    '13_2706' => 33225,
                    '13_2707' => 33226,
                    '13_2708' => 33227,
                    '13_2709' => 33228,
                    '13_2727' => 33228,
                    '13_2728' => 33228,
                    '13_2729' => 33228,
                    '13_2730' => 33228,
                    '13_2733' => 33228,
                    '13_2731' => 33228,
                    '13_2732' => 33228,
                    '13_2734' => 33228,
                    '13_2735' => 33228,
                    '13_2736' => 33228,
                    '13_2737' => 33228,
                    '13_2738' => 33228,
                    '13_2739' => 33228,
                    '13_2740' => 33228,
                    '13_2741' => 33228,
                    '13_2710' => 33229,
                    '13_2711' => 33230,
                    '13_2712' => 33231,
                    '13_2713' => 33232,
                    '13_2714' => 33233,
                    '13_2715' => 33235,
                    '13_2716' => 33234,
                    '13_2718' => 33237,
                    '13_2719' => 33238,
                    '13_2717' => 33236,
                    '13_2720' => 33239,
                    '13_2721' => 33240,
                    '13_2722' => 33241,
                    '13_2723' => 33242,
                    '13_2724' => 33243,
                    '13_2725' => 33244,
                    '13_2726' => 33245,
                    '13_2754' => 33246,
                    '13_3367' => 33247,
                ]
            ]
        ],
    ];
}
